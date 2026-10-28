
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
$questions = getQuestions('samplecommitment');

// Check whether this user already has an application for this cycle.
$applicant_id = $_SESSION['_id'];
if ($applicant) {
    $existing_commitment = get_applicant_commitment(
    $_SESSION['_id'],  // ID of the logged-in user.
    $cycle_id         // Program cycle being checked.
    );
}

$id = NULL;
$choice = NULL;
$success = NULL;
// Process the application when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Stop applicants from submitting twice in the same cycle.
    if ($existing_commitment) {
        $error = "You have already submitted an application for this cycle.";

    } else {
        // Read the submitted form fields.
        if ($applicant) {
            $answers = $_POST['answers'] ?? [];

            // Make sure the applicant answered the questions.
            if (!is_array($answers) || empty($answers)) {
                $error = "Please complete the commitment form.";

            } elseif (count(array_filter($answers, function ($answer) {
                return is_string($answer) && trim($answer) !== '';
            })) !== count($answers)) {
                $error = "Please answer every question.";

            } else {
                // Create a unique ID for this application.
                $commitment_id = bin2hex(random_bytes(16));
                $applicant_id = $_SESSION['_id'];
                // Save the application and all answers together.
                $success = submit_commitment_with_answers(
                    $commitment_id,
                    $applicant_id,
                    $cycle_id,
                    $answers
                );

                if ($success) {
                    header("Location: Commitment.php?submitted=1");
                    exit;
                } else {
                    $error = "Unable to submit Commitment Form.";
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
    <title>Manage Application - Leadership Fauquier</title>
</head>
<body>

    <h1>Leadership Fauquier Application</h1>
    <?php
        if ($applicant) :
        if (isset($error)):
    ?>
     <!-- Display an error if the application could not be submitted. -->
     <p style="color: red;">
        <?php echo htmlspecialchars($error); ?>
     </p>
    <?php endif; ?>
    <?php if ($existing_commitment): ?>

    <!-- Show the status of an existing application. -->
     <h2>Application Status</h2>
     <p>submitted</p>

    <?php else: ?>

    <!-- No application exists yet, so show the form. -->

    <form method="POST">
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
                $ans = getAnswers($question['number'],'samplecommitment');
                foreach($ans as $an):
                ?>
                    <br>
                    <input type="checkbox" id="question_<?php echo $question['number']; ?>" name="answers[<?php echo $question['number']; ?>]" value='<?php echo $an['answer'];?>'><?php echo $an['answer'];?>
                <?php endforeach; ?>
            <?php elseif($question['type'] === 'multipleChoice'):
                $ans = getAnswers($question['number'],'samplecommitment'); ?>
                <Select id="question_<?php echo $question['number']; ?>" name="answers[<?php echo $question['number']; ?>]">
                    <?php foreach($ans as $an): ?>
                        <option type="select" id="question_<?php echo $question['number']; ?>" value='<?php echo $an['answer'];?>'><?php echo $an['answer'];?></option>
                    <?php endforeach; ?>
                </Select>
            <?php else: ?>
                <input type="<?php echo $question['type']; ?>" id="question_<?php echo $question['number']; ?>" name="answers[<?php echo $question['number']; ?>]"></input>
            <?php endif; ?>
            <br><br>
        <?php endwhile; ?>
        <br><br>

        <button type="submit">Submit Application</button>
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
        <?php echo htmlspecialchars($answer['answer']); ?>
    </p>

<?php endforeach; ?>
<?php endif; ?>
    <p><a href="index.php">Back to Dashboard</a></p>

</body>
</html>
