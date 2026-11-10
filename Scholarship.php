
<?php
session_start();

require_once('database/dbPersons.php');
require_once('database/dbApplication.php');
require_once('database/dbQuestions.php');
// Require the user to be logged in.
if (!isset($_SESSION['_id'])) {
    header("Location: login.php");
    exit;
}

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
    foreach (getQuestions('samplescholarshi') as $q) {
        if ($q['type'] !== 'file') continue;
        $n = (int) $q['number'];
        $hasNew   = isset($files[$n]) && $files[$n]['error'] === UPLOAD_ERR_OK;
        $hasSaved = !empty($saved_answers[$n]);
        if (!$hasNew && !$hasSaved) return true;
    }
    return false;
}


$person = retrieve_person($_SESSION['_id']);

$notRoot = $person->get_id() != 'vmsroot';
$admin = $person->get_type()[0] == 'admin';
$applicant = in_array('applicant', $person->get_type(), true);
$cm = $person->get_type()[0] == 'cm';
$cohort = $person->get_type()[0] == 'cohort';

// Set the current application cycle.
$cycle_id = '2026';


// Retrieve the current application questions from the database,
// including any questions added or removed by the administrator.
$questions = getQuestions('samplescholarshi');

// Check whether this user already has an application for this cycle.
$applicant_id = $_SESSION['_id'];
if ($applicant) {
    $application_id = get_applicant_application($applicant_id, $cycle_id);

    // Require an application before accessing scholarships.
    if (!$application_id) {
        header("Location: index.php");
        exit;
    }

    $existing_Scholarship = get_applicant_scholarship(
        $application_id['id'],
        $cycle_id
    );
}

$id = NULL;
$choice = NULL;
$success = NULL;
// Process the application when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $files = get_uploaded_files();

    // Stop applicants from submitting twice in the same cycle.
    if ($existing_Scholarship) {
        $error = "You have already submitted an scholarship for this cycle.";

    } else {
        // Read the submitted form fields.
        if ($applicant) {
            $answers = $_POST['answers'] ?? [];

            // Make sure the applicant answered the questions.
            if (!is_array($answers) || empty($answers)) {
                $error = "Please complete the scholarship.";

            } elseif (count(array_filter($answers, function ($answer) {
                return is_string($answer) && trim($answer) !== '';
            })) !== count($answers)) {
                $error = "Please answer every question.";

            } elseif (missing_required_file($files, [])) {
                $error = "Please upload all required files.";
            } else {
                // Create a unique ID for this application.
                $scholarship_id = bin2hex(random_bytes(16));
                $applicant_id = $_SESSION['_id'];
                $application_id = get_applicant_application($applicant_id, $cycle_id);
                // Save the application and all answers together.
                $success = submit_Scholarship_with_answers(
                    $scholarship_id,
                    $application_id['id'],
                    $cycle_id,
                    $answers,
                    $files
                );

                if ($success) {
                    header("Location: Scholarship.php?submitted=1");
                    exit;
                } else {
                    $error = "Unable to submit Scholarship.";
                }
            }
        }
        if ($cm) {
            header("Location: index.php");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/base.css" type="text/css" />
        <link rel="icon" type="image/x-icon" href="https://leadershipfauquier.org/wp-content/uploads/2022/09/LeadershipFauquierLogo-404x236-1.png">
        <title>Manage Scholarship - Leadership Fauquier</title>
    </head>
    <body>
        <nav class="nav_application"></nav>
        <h2 class="application_header">
            <img src="https://leadershipfauquier.org/wp-content/uploads/2022/09/LeadershipFauquierLogo-404x236-1.png" alt="leadership fauquier" class="application_header_img"> COHORT SCHOLARSHIP
        </h2>
        <main>
            <div class="application_main">
                <?php
                    if ($applicant) :
                    if (isset($error)):
                ?>
                <!-- Display an error if the application could not be submitted. -->
                <p style="color: red;">
                    <?php echo htmlspecialchars($error); ?>
                </p>
                <?php endif; ?>
                <?php if ($existing_Scholarship): ?>

                <!-- Show the status of an existing application. -->
                <h2>Application Status</h2>
                <p>submitted</p>

                <?php else: ?>

                <!-- No application exists yet, so show the form. -->

                <form method="POST" enctype='multipart/form-data'>
                    <?php
                // Display each question currently defined by the administrator.
                    while ($question = mysqli_fetch_assoc($questions)):
                    ?>
                        <label for="question_<?php echo $question['number']; ?>">
                        <?php echo htmlspecialchars($question['question']); ?>
                        </label>

                        <?php if ($question['type'] === 'textarea'): ?>
                            <textarea
                                id="question_<?php echo $question['number']; ?>"
                                name="answers[<?php echo $question['number']; ?>]"
                                rows="4"
                                required
                            ></textarea>
                        <?php elseif($question['type'] === 'checkbox'): 
                            $ans = getAnswers($question['number'],'samplescholarshi');
                            foreach($ans as $an):
                            ?>
                                <br>
                                <input type="checkbox" id="question_<?php echo $question['number']; ?>" name="answers[<?php echo $question['number']; ?>]" value='<?php echo $an['answer'];?>'><?php echo $an['answer'];?>
                            <?php endforeach; ?>
                        <?php elseif($question['type'] === 'multipleChoice'):
                            $ans = getAnswers($question['number'],'samplescholarshi'); ?>
                            <Select id="question_<?php echo $question['number']; ?>" name="answers[<?php echo $question['number']; ?>]">
                                <?php foreach($ans as $an): ?>
                                    <option type="select" id="question_<?php echo $question['number']; ?>" value='<?php echo $an['answer'];?>'><?php echo $an['answer'];?></option>
                                <?php endforeach; ?>
                            </Select>
                        <?php elseif ($question['type'] === 'file'): ?>
                            <input type="file"
                                id="question_<?php echo $question['number']; ?>"
                                name="files[<?php echo $question['number']; ?>]"
                                accept=".jpg,.jpeg,.png,.pdf,.docx">
                            <?php if (!empty($saved_answers[$question['number']])): ?>
                                <br><small>A file is already saved. Choose a new one only to replace it.</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <input type="<?php echo $question['type']; ?>" id="question_<?php echo $question['number']; ?>" name="answers[<?php echo $question['number']; ?>]"></input>
                        <?php endif; ?>
                        <br><br>
                    <?php endwhile; ?>
                    <br><br>

                    <button class="application_buttons" type="submit">Submit Application</button>
                </form>
                <?php 
                    endif;
                    elseif($admin):
                        $id = $_GET['id'];

                ?>
                
                <?php
                $answers = getQuestions($id);

                foreach ($answers as $answer):
                ?>
                <p>
                    Question <?php echo htmlspecialchars($answer['number']); ?>:
                    <?php echo htmlspecialchars($answer['question']); ?>
                </p>
                <p>
                    <?php if (($answer['type'] ?? '') === 'file' && $answer['answer'] !== ''): ?>
                        <a href="downloadFile.php?file=<?php echo urlencode($answer['answer']); ?>">Download file</a>
                    <?php else: ?>
                        <?php echo htmlspecialchars($answer['answer']); ?>
                    <?php endif; ?>
                </p>

                <?php endforeach; ?>
                <?php endif; ?>
                <p><a href="index.php">Back to Dashboard</a></p>
            </div>
        </main>
    </body>
</html>
