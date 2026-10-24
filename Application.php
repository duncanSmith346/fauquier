
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
$questions = getQuestions('sampleapplicatio');

// Check whether this user already has an application for this cycle.
$existing_application = get_applicant_application(
    $_SESSION['_id'],  // ID of the logged-in user.
    $cycle_id         // Program cycle being checked.
);

$id = NULL;
$choice = NULL;
$success = NULL;
// Process the application when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Stop applicants from submitting twice in the same cycle.
    if ($existing_application) {
        $error = "You have already submitted an application for this cycle.";

    } else {
        // Read the submitted form fields.
            if ($applicant) {
            $answers = $_POST['answers'] ?? [];

            // Make sure the applicant answered the questions.
            if (!is_array($answers) || empty($answers)) {
                $error = "Please complete the application.";

            } elseif (count(array_filter($answers, function ($answer) {
                return is_string($answer) && trim($answer) !== '';
            })) !== count($answers)) {
                $error = "Please answer every question.";

            } else {
                // Create a unique ID for this application.
                $application_id = bin2hex(random_bytes(16));
                $applicant_id = $_SESSION['_id'];

                // Save the application and all answers together.
                $success = submit_application_with_answers(
                    $application_id,
                    $applicant_id,
                    $cycle_id,
                    $answers
                );

                if ($success) {
                    header("Location: Application.php?submitted=1");
                    exit;
                } else {
                    $error = "Unable to submit application.";
                }
            }
        }


        elseif ($cm) {
            $comment = trim($_POST['comment'] ?? '');
            $application_id = $_POST['application_id'] ?? '';

            $success = add_comment($comment, $application_id);

             if ($success) {
                header("Location: Application.php?id=" . urlencode($application_id) . "&commentsubmitted=1");
                exit;
            } else {
                $error = "Unable to submit comment.";
            }
        }
    }


} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if($admin) {
        $id = $_GET['id'] ?? null;
        $choice = null;

        if (isset($_GET['reject'])) {
            $choice = 'reject';
        } elseif (isset($_GET['accept'])) {
            $choice = 'accept';
        }

        if ($id !== null && $choice !== null) {
            $success = update_choice($choice, $id);
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

    <?php if ($existing_application): ?>

    <!-- Show the status of an existing application. -->
     <h2>Application Status</h2>

    <?php
    // Get the decision recorded in the database.
    $status = $existing_application['decisionStatus'];

    // Translate database values into readable statuses.
    if ($status === 'accept') {
      echo "<p>Accepted</p>";
    } elseif ($status === 'reject') {
        echo "<p>Rejected</p>";
    } else {
       echo "<p>Pending Review</p>";
    }
    ?>

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

           <textarea
               id="question_<?php echo $question['number']; ?>"
               name="answers[<?php echo $question['number']; ?>]"
               rows="4"
               required
           ></textarea>

           <br><br>

        <?php endwhile; ?>
        <br><br>

        <button type="submit">Submit Application</button>
    </form>
 <?php 
    endif;
    elseif($admin):
        $id = $_GET['id'];
        $result = retrieve_application($id);

?>
    
    <p>Full name: <?php echo $result['answer1'] ?> </p>
    <p>Tell us about yourself: <?php echo $result['answer2'] ?></p>
    <p>comment: <?php echo $result['comments'] ?></p>
    <p>reviewer: <?php echo $result['reviewer_id'] ?></p>
    <p>decision Status: <?php echo $result['decisionStatus'] ?></p>
    <form action="Application.php" method="GET">
        <input type='hidden' name='id' value='<?php echo $result['id'] ?>' />
        <button type='submit' name='reject' value='reject'>Reject</button>
        <button type='submit' name='accept' value='accept'>Accept</button>
    </form>

<?php     
    elseif($cm):
        $id = $_GET['id'];
        $result = retrieve_application($id);

?>
    // Display each question currently defined by the administrator.
    <?php while ($question = mysqli_fetch_assoc($questions)):
        ?>
            <label for="question_<?php echo $question['number']; ?>">
               <?php echo htmlspecialchars($question['question']); ?>
            </label>

           <textarea
               id="question_<?php echo $question['number']; ?>"
               name="answers[<?php echo $question['number']; ?>]"
               rows="4"
               required
           ></textarea>

           <?php echo <h3> . htmlspecialchars($question['question']) . </h3>; ?>

           <?php if (isset($result[$answerField])) {
                echo "<p>" . nl2br(htmlspecialchars($result[$answerField])) . "</p>";
            } ?>

        <?php endwhile; ?>

    <form action="Application.php" method='POST'>
        <input type="text" name='comment'>
        <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($id); ?>">
        <button type='submit'>Submit Comment</button>
    </form>
<?php     
    elseif($cohort): 
?>
    cohort view
<?php endif; ?>
    <p><a href="index.php">Back to Dashboard</a></p>

</body>
</html>
