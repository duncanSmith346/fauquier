<?php
session_start();

require_once('database/dbPersons.php');
require_once('database/dbApplication.php');
require_once('database/dbQuestions.php');



function get_uploaded_files($field = 'files') {
    $out = [];
    if (empty($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])) {
        return $out;
    }
    foreach ($_FILES[$field]['name'] as $number => $name) {
        $out[(int) $number] = [
            'name'     => $name,
            'tmp_name' => $_FILES[$field]['tmp_name'][$number],
            'error'    => $_FILES[$field]['error'][$number],
            'size'     => $_FILES[$field]['size'][$number],
        ];
    }
    return $out;
} 

// True if any required file question has neither a new upload nor a saved file.
function missing_required_file($files, $saved_answers) {
    foreach (getQuestions('samplecommitment') as $q) {
        if ($q['type'] !== 'file') continue;
        $n = (int) $q['number'];
        $hasNew   = isset($files[$n]) && $files[$n]['error'] === UPLOAD_ERR_OK;
        $hasSaved = !empty($saved_answers[$n]);
        if (!$hasNew && !$hasSaved) return true;
    }
    return false;
}


if (!isset($_SESSION['_id'])) {
    header('Location: login.php');
    exit;
}

$person = retrieve_person($_SESSION['_id']);
if (!$person) {
    header('Location: login.php');
    exit;
}

$types = (array) $person->get_type();
$admin = in_array('admin', $types, true);
$cohort = in_array('cohort', $types, true);

if (!$admin && !$cohort) {
    header('Location: index.php');
    exit;
}

$cycle_id = '2026';
$error = null;
$existing_commitment = null;
$answers = [];
$questions = null;
$admin_commitment = null;
$admin_answers = [];

if ($cohort && !$admin) {
    $existing_commitment = get_applicant_commitment($_SESSION['_id'], $cycle_id);
    $questions = getQuestions('samplecommitment');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $files = get_uploaded_files();
        if ($existing_commitment) {
            $error = 'You have already submitted a commitment form for this cycle.';
        } else {
            $answers = $_POST['answers'] ?? [];
            if (!is_array($answers) || empty($answers)) {
                $error = 'Please complete the commitment form.';
            } elseif (missing_required_file($files, [])) {
                $error = "Please upload all required files.";
            } else {
                $valid = true;
                foreach ($answers as $answer) {
                    if (is_array($answer)) {
                        $filled = array_filter($answer, static function ($value) {
                            return is_scalar($value) && trim((string) $value) !== '';
                        });
                        if (count($filled) === 0) {
                            $valid = false;
                            break;
                        }
                    } elseif (!is_scalar($answer) || trim((string) $answer) === '') {
                        $valid = false;
                        break;
                    }
                }

                if (!$valid) {
                    $error = 'Please answer every question.';
                } elseif (!function_exists('submit_commitment_with_answers')) {
                    $error = 'Commitment submission is not configured yet.';
                } else {
                    $commitment_id = bin2hex(random_bytes(16));
                    $success = submit_commitment_with_answers(
                        $commitment_id,
                        $_SESSION['_id'],
                        $cycle_id,
                        $answers,
                        $files
                    );
                    if ($success) {
                        header('Location: Commitment.php?submitted=1');
                        exit;
                    }
                    $error = 'Unable to submit commitment form.';
                }
            }
        }
    }
}

if ($admin) {
    $id = $_GET['id'] ?? null;
    if (is_string($id) && $id !== '') {
        $con = connect();
        $sql = 'SELECT * FROM dbCommitment WHERE commitment_id = ?';
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin_commitment = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($stmt);
        mysqli_close($con);

        if ($admin_commitment) {
            $admin_answers = getQuestions($id);
        } else {
            $error = 'No commitment form was found for that ID.';
        }
    }
}

function commitment_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/base.css" type="text/css">
    <link rel="icon" type="image/x-icon" href="https://leadershipfauquier.org/wp-content/uploads/2022/09/LeadershipFauquierLogo-404x236-1.png">
    <title>Commitment Form - Leadership Fauquier</title>
</head>
<body>
    <nav class="nav_application"></nav>
    <h2 class="application_header">
        <img src="https://leadershipfauquier.org/wp-content/uploads/2022/09/LeadershipFauquierLogo-404x236-1.png" alt="Leadership Fauquier" class="application_header_img">
        COHORT COMMITMENT FORM
    </h2>
    <main>
        <div class="application_main" style="max-width:100%;">
            <?php if ($error): ?>
                <p class="error" role="alert"><?= commitment_escape($error) ?></p>
            <?php endif; ?>

            <?php if ($admin): ?>
                <?php if ($admin_commitment): ?>
                    <h2>Commitment Form</h2>
                    <p><strong>Status:</strong> <?= commitment_escape($admin_commitment['commitmentStatus'] ?? 'Submitted') ?></p>
                    <p><strong>Submitted:</strong> <?= commitment_escape($admin_commitment['submittedDate'] ?? '') ?></p>
                    <?php foreach ($admin_answers as $answer): ?>
                        <p><strong><?= commitment_escape($answer['question'] ?? ('Question ' . $answer['number'])) ?></strong></p>
                        <p class="application_responses">
                            <?php if (($answer['type'] ?? '') === 'file' && $answer['answer'] !== ''): ?>
                                <a href="downloadFile.php?file=<?php echo urlencode($answer['answer']); ?>">Download file</a>
                            <?php else: ?>
                                <?= nl2br(commitment_escape($answer['answer'] ?? '')) ?>
                            <?php endif; ?>
                        </p>
                        <br>
                    <?php endforeach; ?>
                <?php elseif (!$error): ?>
                    <p>Open a submitted commitment from the admin dashboard to review it.</p>
                <?php endif; ?>
            <?php elseif ($existing_commitment): ?>
                <h2 style="text-align:center;">Commitment Status</h2>
                <p style="text-align:center;">Submitted</p>
            <?php else: ?>
                <form method="POST" action="Commitment.php" enctype='multipart/form-data'>
                    <?php while ($question = mysqli_fetch_assoc($questions)): ?>
                        <?php $number = (int) $question['number']; ?>
                        <label for="question_<?= $number ?>"><?= commitment_escape($question['question']) ?></label>

                        <?php if ($question['type'] === 'textarea'): ?>
                            <textarea id="question_<?= $number ?>" name="answers[<?= $number ?>]" rows="4" required><?= commitment_escape($answers[$number] ?? '') ?></textarea>
                        <?php elseif ($question['type'] === 'checkbox'): ?>
                            <?php $options = getAnswers($number, 'samplecommitment'); ?>
                            <input type="hidden" name="answers[<?= $number ?>][]" value="">
                            <?php foreach ($options as $option): ?>
                                <?php $value = (string) $option['answer']; ?>
                                <div class="checkbox-pair">
                                    <input type="checkbox" id="question_<?= $number ?>_<?= md5($value) ?>" name="answers[<?= $number ?>][]" value="<?= commitment_escape($value) ?>" <?= in_array($value, (array) ($answers[$number] ?? []), true) ? 'checked' : '' ?>>
                                    <label class="checkbox-label" for="question_<?= $number ?>_<?= md5($value) ?>"><?= commitment_escape($value) ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif ($question['type'] === 'multipleChoice'): ?>
                            <?php $options = getAnswers($number, 'samplecommitment'); ?>
                            <select id="question_<?= $number ?>" name="answers[<?= $number ?>]" required>
                                <option value="">Select an answer</option>
                                <?php foreach ($options as $option): ?>
                                    <option value="<?= commitment_escape($option['answer']) ?>" <?= (($answers[$number] ?? '') === $option['answer']) ? 'selected' : '' ?>><?= commitment_escape($option['answer']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($question['type'] === 'file'): ?>
                            <input type="file"
                                id="question_<?php echo $question['number']; ?>"
                                name="files[<?php echo $question['number']; ?>]"
                                accept=".jpg,.jpeg,.png,.pdf,.docx">
                            <?php if (!empty($saved_answers[$question['number']])): ?>
                                <br><small>A file is already saved. Choose a new one only to replace it.</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php $type = in_array($question['type'], ['text', 'email', 'tel', 'date', 'number'], true) ? $question['type'] : 'text'; ?>
                            <input type="<?= commitment_escape($type) ?>" id="question_<?= $number ?>" name="answers[<?= $number ?>]" value="<?= commitment_escape($answers[$number] ?? '') ?>" required>
                        <?php endif; ?>
                        <br><br>
                    <?php endwhile; ?>
                    <button class="application_buttons" type="submit">Submit Commitment Form</button>
                </form>
            <?php endif; ?>
            <p><a href="index.php">Back to Dashboard</a></p>
        </div>
    </main>
</body>
</html>
