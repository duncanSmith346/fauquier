
<?php
session_start();

require_once('database/dbApplication.php');
require_once('database/dbPersons.php');

if (!isset($_SESSION['_id'])) {
    header('Location: login.php');
    exit;
}

$person = retrieve_person($_SESSION['_id']);

if (!$person || !in_array('admin', (array)$person->get_type(), true)) {
    http_response_code(403);
    exit('Access denied.');
}

$form_id = $_GET['id'] ?? $_POST['id'] ?? '';

if (!is_string($form_id) || $form_id === '') {
    exit('No onboarding form specified.');
}

if (!isset($_SESSION['onboarding_csrf'])) {
    $_SESSION['onboarding_csrf'] = bin2hex(random_bytes(32));
}

$con = connect();

$sql = "SELECT
            dbCohortForms.*,
            dbPersons.first_name,
            dbPersons.last_name
        FROM dbCohortForms
        INNER JOIN dbCohort
            ON dbCohortForms.cohort_id = dbCohort.id
        INNER JOIN dbPersons
            ON dbCohort.person_id = dbPersons.id
        WHERE dbCohortForms.form_id = ?
          AND dbCohortForms.form_type = 'sampleonboardingappointment'
          AND dbCohortForms.submittedDate IS NOT NULL";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $form_id);
mysqli_stmt_execute($stmt);
$form = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$form) {
    mysqli_close($con);
    http_response_code(404);
    exit('Onboarding form not found.');
}

// Mark onboarding complete.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['onboarding_csrf'], $token)) {
        mysqli_close($con);
        http_response_code(403);
        exit('Invalid request.');
    }

    if (($_POST['action'] ?? '') === 'complete') {
        $sql = "UPDATE dbCohortForms
                SET completedAt = NOW()
                WHERE form_id = ?
                  AND form_type = 'sampleonboardingappointment'
                  AND submittedDate IS NOT NULL
                  AND completedAt IS NULL";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $form_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_close($con);

        header('Location: AdminOnboarding.php?id=' . urlencode($form_id));
        exit;
    }
}

// Retrieve submitted answers.
$sql = "SELECT number, question, answer, type
        FROM dbQuestions
        WHERE application_id = ?
        ORDER BY number";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $form_id);
mysqli_stmt_execute($stmt);
$answers = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
mysqli_close($con);

function onboarding_escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/base.css" type="text/css">
    <link rel="icon" type="image/x-icon"
          href="https://leadershipfauquier.org/wp-content/uploads/2022/09/LeadershipFauquierLogo-404x236-1.png">
    <title>Onboarding Review - Leadership Fauquier</title>
</head>

<body>
    <nav class="nav_application"></nav>

    <h2 class="application_header">
        <img
            src="https://leadershipfauquier.org/wp-content/uploads/2022/09/LeadershipFauquierLogo-404x236-1.png"
            alt="Leadership Fauquier"
            class="application_header_img">
        COHORT ONBOARDING FORM
    </h2>

    <main>
        <div class="application_main" style="max-width:100%;">

            <h2>Onboarding Review</h2>

            <p>
                <strong>Cohort Member:</strong>
                <?= onboarding_escape($form['first_name'] . ' ' . $form['last_name']) ?>
            </p>

            <p>
                <strong>Submitted:</strong>
                <?= onboarding_escape($form['submittedDate']) ?>
            </p>

            <p>
                <strong>Status:</strong>
                <?= $form['completedAt'] === null ? 'Unfinished' : 'Finished' ?>
            </p>

            <?php foreach ($answers as $answer): ?>
                <p>
                    <strong>
                        <?= onboarding_escape(
                            $answer['question'] ?? ('Question ' . $answer['number'])
                        ) ?>
                    </strong>
                </p>

                <p class="application_responses">
                    <?php if (($answer['type'] ?? '') === 'file'
                              && !empty($answer['answer'])): ?>
                        <a href="downloadFile.php?file=<?= urlencode($answer['answer']) ?>">
                            Download file
                        </a>
                    <?php else: ?>
                        <?= nl2br(onboarding_escape($answer['answer'] ?? '')) ?>
                    <?php endif; ?>
                </p>
                <br>
            <?php endforeach; ?>

            <?php if ($form['completedAt'] === null): ?>
                <form method="POST"
                      action="AdminOnboarding.php?id=<?= urlencode($form_id) ?>">

                    <input type="hidden" name="id"
                           value="<?= onboarding_escape($form_id) ?>">

                    <input type="hidden" name="csrf_token"
                           value="<?= onboarding_escape($_SESSION['onboarding_csrf']) ?>">

                    <input type="hidden" name="action" value="complete">

                    <button class="application_buttons" type="submit">
                        Mark Onboarding Complete
                    </button>
                </form>
            <?php else: ?>
                <p><strong>Onboarding completed.</strong></p>
            <?php endif; ?>

            <p><a href="index.php">Back to Dashboard</a></p>

        </div>
    </main>
</body>
</html>
