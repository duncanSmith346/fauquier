<?php

session_start();

require_once('database/dbPersons.php');
require_once('database/dbApplication.php');
require_once('database/dbQuestions.php');

// Verify that the user is logged in.
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

// Supported cohort forms.
$form_types = [
    'sampleallergy' => 'Allergy Information',
    'sampleemergencycontact' => 'Emergency Contact',
    'sampleselfassessment' => 'Self-Assessment',
    'sampleonboardingappointment' => 'Onboarding Appointment'
];

$form_type = $_GET['type'] ?? '';


//if (!is_string($form_type) || !isset($form_types[$form_type])) {
//    die('Invalid form type.');
//}

if (!is_string($form_type) || !isset($form_types[$form_type])) {
    echo '<pre>';
    var_dump($form_type);
    var_dump($form_types);
    echo '</pre>';
    die('Invalid form type.');
}

$title = $form_types[$form_type];
$cycle_id = '2026';
$error = null;
$answers = [];
$questions = [];
$admin_answers = [];
$existing_form = null;

$person_id = $admin ? ($_GET['id'] ?? '') : $_SESSION['_id'];

if (!is_string($person_id) || $person_id === '') {
    die('Invalid person ID.');
}

$existing_form = get_cohort_form(
    $person_id,
    $cycle_id,
    $form_type
);

function cohort_escape($value) {
    return htmlspecialchars(
        is_scalar($value) ? (string) $value : '',
        ENT_QUOTES,
        'UTF-8'
    );
}

// Administrator viewing a submitted form.
if ($admin) {
    if ($existing_form) {
        $admin_answers = getQuestions($existing_form['form_id']);
    }
}

// Cohort member completing a form.
if (!$admin && $cohort && !$existing_form) {

    $questions = mysqli_fetch_all(
        getQuestions($form_type),
        MYSQLI_ASSOC
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $answers = $_POST['answers'] ?? [];
        $files = [];

        if (!is_array($answers)) {
            $answers = [];
        }

        // Collect uploaded files.
        foreach ($_FILES['files']['name'] ?? [] as $number => $name) {
            $files[(int) $number] = [
                'name' => $name,
                'tmp_name' => $_FILES['files']['tmp_name'][$number],
                'error' => $_FILES['files']['error'][$number],
                'size' => $_FILES['files']['size'][$number]
            ];
        }

        // Validate every question.
        $valid = !empty($questions);
        $validated_answers = [];
        $validated_files = [];

        foreach ($questions as $question) {
            $number = (int) $question['number'];
            $type = $question['type'];

            if ($type === 'file') {

                if (!isset($files[$number]) ||
                    $files[$number]['error'] !== UPLOAD_ERR_OK) {
                    $valid = false;
                    break;
                }

                $validated_files[$number] = $files[$number];

            } else {

                $answer = $answers[$number] ?? '';

                if ($type === 'checkbox') {

                    if (!is_array($answer)) {
                        $valid = false;
                        break;
                    }

                    $answer = array_filter(
                        $answer,
                        static function ($value) {
                            return is_string($value)
                                && trim($value) !== '';
                        }
                    );

                    if (empty($answer)) {
                        $valid = false;
                        break;
                    }

                } elseif (!is_string($answer) ||
                          trim($answer) === '') {
                    $valid = false;
                    break;
                }

                // Verify selected answers.
                if ($type === 'checkbox' ||
                    $type === 'multipleChoice' ||
                    $type === 'onboardingDateTime') {

                    $allowed = [];

                    foreach (getAnswers($number, $form_type) as $option) {
                        $allowed[] = $option['answer'];
                    }

                    foreach ((array) $answer as $value) {
                        if (!in_array($value, $allowed, true)) {
                            $valid = false;
                            break 2;
                        }
                    }
                }

                $validated_answers[$number] = $answer;
            }
        }

        if (!$valid) {
            $error = 'Please complete every question and required file upload.';
        } else {

            $form_id = bin2hex(random_bytes(16));

            $success = submit_cohort_form(
                $form_id,
                $person_id,
                $cycle_id,
                $form_type,
                $validated_answers,
                $validated_files
            );

            if ($success) {
                header(
                    'Location: CohortForm.php?type=' .
                    urlencode($form_type)
                );
                exit;
            }

            $error = 'Unable to submit form.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/base.css" type="text/css">
    <link rel="icon" type="image/x-icon" href="https://leadershipfauquier.org/wp-content/uploads/2022/09/LeadershipFauquierLogo-404x236-1.png">
    <title><?= cohort_escape($title) ?> - Leadership Fauquier</title>
</head>
<body>
    <nav class="nav_application"></nav>

    <h2 class="application_header">
        <img
            src="https://leadershipfauquier.org/wp-content/uploads/2022/09/LeadershipFauquierLogo-404x236-1.png"
            alt="Leadership Fauquier"
            class="application_header_img">

        <?= cohort_escape(strtoupper($title)) ?> FORM
    </h2>

    <main>
        <div class="application_main" style="max-width:100%;">

            <?php if ($error): ?>
                <p class="error" role="alert">
                    <?= cohort_escape($error) ?>
                </p>
            <?php endif; ?>

            <?php if ($admin): ?>

                <?php if ($existing_form): ?>
                    <p style="text-align:center;"><strong>Status:</strong> Submitted</p>

                    <p style="text-align:center;">
                        <strong>Submitted:</strong>
                        <?= cohort_escape($existing_form['submittedDate']) ?>
                    </p>
                    <br>
                    <?php foreach ($admin_answers as $answer): ?>

                        <p style="padding: 0.3rem;">
                            <strong>
                                <?= cohort_escape($answer['question']) ?>
                            </strong>
                        </p>

                        <p class="application_responses">

                            <?php if ($answer['type'] === 'file' &&
                                      !empty($answer['answer'])): ?>

                                <a href="downloadFile.php?file=<?= urlencode($answer['answer']) ?>">
                                    Download file
                                </a>

                            <?php else: ?>

                                <?= nl2br(cohort_escape($answer['answer'])) ?>

                            <?php endif; ?>

                        </p>

                        <br>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>This form has not been submitted.</p>

                <?php endif; ?>

            <?php elseif ($existing_form): ?>

                <h2 style="text-align:center;">
                    <?= cohort_escape($title) ?> Status
                </h2>
                <br>
                <p style="text-align:center;">Submitted</p>

            <?php elseif (empty($questions)): ?>

                <p>This form is not available yet.</p>

            <?php else: ?>

                <form method="POST"
                      action="CohortForm.php?type=<?= urlencode($form_type) ?>"
                      enctype="multipart/form-data">

                    <?php foreach ($questions as $question): ?>

                        <?php $number = (int) $question['number']; ?>

                        <label for="question_<?= $number ?>">
                            <?= cohort_escape($question['question']) ?>
                        </label>

                        <?php if ($question['type'] === 'textarea'): ?>

                            <textarea
                                id="question_<?= $number ?>"
                                name="answers[<?= $number ?>]"
                                rows="4"
                                required><?= cohort_escape($answers[$number] ?? '') ?></textarea>

                        <?php elseif ($question['type'] === 'checkbox'): ?>

                            <?php $options = getAnswers($number, $form_type); ?>

                            <input type="hidden"
                                   name="answers[<?= $number ?>][]"
                                   value="">

                            <?php foreach ($options as $option): ?>

                                <?php $value = (string) $option['answer']; ?>

                                <div class="checkbox-pair">

                                    <input type="checkbox"
                                           id="question_<?= $number ?>_<?= md5($value) ?>"
                                           name="answers[<?= $number ?>][]"
                                           value="<?= cohort_escape($value) ?>"
                                           <?= in_array($value, (array) ($answers[$number] ?? []), true) ? 'checked' : '' ?>>

                                    <label class="checkbox-label" style="padding: 0.1rem;"
                                           for="question_<?= $number ?>_<?= md5($value) ?>">
                                        <?= cohort_escape($value) ?>
                                    </label>

                                </div>

                            <?php endforeach; ?>

                            <?php elseif (
                                 $question['type'] === 'multipleChoice' ||
                                 $question['type'] === 'onboardingDateTime'
                            ): ?>

                            <?php $options = getAnswers($number, $form_type); ?>

                            <select id="question_<?= $number ?>"
                                    name="answers[<?= $number ?>]"
                                    required>

                                <option value="">Select an answer</option>

                                <?php foreach ($options as $option): ?>

                                    <option
                                        value="<?= cohort_escape($option['answer']) ?>"
                                        <?= ($answers[$number] ?? '') === $option['answer'] ? 'selected' : '' ?>>

                                        <?= cohort_escape($option['answer']) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        <?php elseif ($question['type'] === 'file'): ?>

                            <input type="file"
                                   id="question_<?= $number ?>"
                                   name="files[<?= $number ?>]"
                                   accept=".jpg,.jpeg,.png,.pdf,.docx"
                                   required>

                        <?php elseif ($question['type'] === 'link'): ?>
                            <?php $ans = getAnswers($question['number'], $form_type); ?>
                            <?php foreach($ans as $an): ?>
                                <a href="<?php echo $an['answer']; ?>"><?php echo $an['answer']; ?></a>
                            <?php endforeach; ?>
                        <?php else: ?>

                            <?php
                            $type = in_array(
                                $question['type'],
                                ['text', 'email', 'tel', 'date', 'number'],
                                true
                            ) ? $question['type'] : 'text';
                            ?>

                            <input type="<?= cohort_escape($type) ?>"
                                   id="question_<?= $number ?>"
                                   name="answers[<?= $number ?>]"
                                   value="<?= cohort_escape($answers[$number] ?? '') ?>"
                                   required>

                        <?php endif; ?>

                        <br><br>

                    <?php endforeach; ?>

                    <button class="application_buttons" type="submit">
                        Submit <?= cohort_escape($title) ?> Form
                    </button>

                </form>

            <?php endif; ?>

            <div class="application_returnLink">
                <?php if ($admin): ?>
                    <p><strong>
                        <a href="CohortProfile.php?id=<?= urlencode($person_id) ?>">
                            Back to Cohort Profile
                        </a></strong>
                    </p>
                <?php else: ?>
                    <p><strong><a href="index.php">Back to Dashboard</a></strong></p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>