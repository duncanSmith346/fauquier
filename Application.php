
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

$saved_answers = [];

if ($applicant && $existing_application &&
    $existing_application['submittedAt'] === null) {

    $saved_questions = getQuestions($existing_application['id']);

    foreach ($saved_questions as $question) {
        $saved_answers[$question['number']] = $question['answer'];
    }
}

$id = NULL;
$choice = NULL;
$success = NULL;
// Process the application when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Save an applicant's draft without submitting it
    if ($applicant && ($_POST['action'] ?? '') === 'save') {
        $answers = $_POST['answers'] ?? [];       

        $success = save_draft_application(
            $_SESSION['_id'],
            $cycle_id,
            $answers
        );

        if ($success) {
            header("Location: Application.php?saved=1");
            exit;
        } else {
            $error = "Unable to save application.";
        }
    }

    // Stop applicants from submitting twice in the same cycle.
    if ($applicant && ($_POST['action'] ?? '') === 'save') {
    // Save was already handled above.
    } elseif ($existing_application && $existing_application['submittedAt'] !== null) {
        $error = "You have already submitted an application for this cycle.";

    } else {

    if ($existing_application && $existing_application['submittedAt'] === null) {
        $answers = $_POST['answers'] ?? [];

        if (save_draft_application($_SESSION['_id'], $cycle_id, $answers)
            && submit_saved_draft($existing_application['id'])) {
            header("Location: Application.php?submitted=1");
            exit;
    } else {
        $error = "Unable to submit application.";
    }
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

            if ($cm) {
                if(isset($_POST['answers']))
                {
                    $application_id = $_POST['application_id'];

                    foreach($_POST['answers'] as $number => $comment)
                    {
                        add_comment(
                            trim($comment),
                            $number,
                            $application_id
                        );
                    }
                }
            }
    }

    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if($admin) {
        $success = 0;
        $id = $_GET['id'] ?? null;
        $choice = null;

        if (isset($_GET['reject'])) {
            $choice = 'reject';
        } elseif (isset($_GET['accept'])) {
            $choice = 'accept';
        }

        if ($id !== null && $choice !== null) {
            $success = update_choice($choice, $id);

            if ($choice == 'accept') {
                $application = retrieve_application($id);
                update_type($application['applicant_id'], 'cohort');
            }
            
        }
        if ($success) {
            header("Location: index.php");
            exit;
        }
        if ($success) {
            header("Location: index.php");
            exit;
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
            if ($applicant || $cohort) :
            if (isset($error)):
        ?>
        <!-- Display an error if the application could not be submitted. -->
        <p style="color: red;">
            <?php echo htmlspecialchars($error); ?>
        </p>
        <?php endif; ?>

    <?php if ($existing_application && $existing_application['submittedAt'] !== null): ?>

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

            <?php if ($question['type'] === 'textarea'): ?>
                <textarea
                    id="question_<?php echo $question['number']; ?>"
                    name="answers[<?php echo $question['number']; ?>]"
                    rows="4"
                    required
                ><?php echo htmlspecialchars($saved_answers[$question['number']] ?? ''); ?></textarea>

            <?php elseif($question['type'] === 'checkbox'): 
                $ans = getAnswers($question['number'],'sampleapplicatio');
            ?>   
                <input type="hidden" name="answers[<?php echo $question['number']; ?>][]" value="">
            <?php foreach($ans as $an):
                ?>
                    <br>
                    <input type="checkbox"
                        id="question_<?php echo $question['number']; ?>"
                        name="answers[<?php echo $question['number']; ?>][]"
                        value="<?php echo htmlspecialchars($an['answer']); ?>"
                        <?php echo in_array($an['answer'], explode(', ', $saved_answers[$question['number']] ?? '')) ? 'checked' : ''; ?>>
                    <?php echo htmlspecialchars($an['answer']); ?>

                <?php endforeach; ?>
            <?php elseif($question['type'] === 'multipleChoice'):
                $ans = getAnswers($question['number'],'sampleapplicatio'); ?>
                <Select id="question_<?php echo $question['number']; ?>" name="answers[<?php echo $question['number']; ?>]">
                    <?php foreach($ans as $an): ?>
                        <option value="<?php echo htmlspecialchars($an['answer']); ?>"
                                 <?php echo (($saved_answers[$question['number']] ?? '') === $an['answer']) ? 'selected' : ''; ?>>
                                 <?php echo htmlspecialchars($an['answer']); ?>
                        </option>
                    <?php endforeach; ?>
                </Select>
            <?php else: ?>
                <input type="<?php echo $question['type'];?>" 
                id="question_<?php echo $question['number'];?>"
                name="answers[<?php echo $question['number'];?>]"
                value="<?php echo htmlspecialchars($saved_answers[$question['number']] ?? '');?>"
                ></input>
            <?php endif; ?>
            <br><br>
        <?php endwhile; ?>
        <br><br>

        <button type="submit" name="action" value="save" formnovalidate>
              Save Application
        </button>

        <button type="submit" name="action" value="submit">
              Submit Application
        </button>
    </form>
 <?php 
    endif;
    elseif($admin):
        $id = $_GET['id'];
        $result = retrieve_application($id);

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
    <p> 
        comment: <?php echo htmlspecialchars($answer['comment']); ?>
    </p>
<?php endforeach; ?>
    <p>reviewer: <?php echo $result['reviewer_id'] ?></p>
    <p>decision Status: <?php echo $result['decisionStatus'] ?></p>
    <form action="Application.php" method="GET">
        <input type='hidden' name='id' value='<?php echo $result['id'] ?>' />
        <button type='submit' name='reject' value='reject'>Reject</button>
        <button type='submit' name='accept' value='accept'>Accept</button>
    </form>
    <?php
        $scholarship = get_applicant_scholarship($result['id'], $cycle_id);
        if ($scholarship !== NULL): 
    ?>
    <form action='Scholarship.php' method='GET'>
        <button type='submit' name='id' value='<?php echo $scholarship['scholarship_id'] ?>'>view Scholarship</button>
    </form>

<?php     
        endif;
    elseif($cm):
        $id = 0;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['application_id'];
        } else {
            $id = $_GET['id'];
        }
        $result = retrieve_application($id);
        $questions = getQuestions($id);

    ?>
        <!-- Display each question currently defined by the administrator. -->
        <form action="Application.php" method='POST'>
        <?php while ($question = mysqli_fetch_assoc($questions)):
            ?>
                <p><?php echo htmlspecialchars($question['question']) . ": ".  htmlspecialchars($question['answer']); ?> </p>
                <label for="question_<?php echo $question['number']; ?>">
                comment:
                </label>
                
            <textarea
                id="question_<?php echo $question['number']; ?>"
                name="answers[<?php echo $question['number']; ?>]"
                rows="4"
                required
            ><?php echo $question['comment']; ?></textarea>

            <br><br>
            <!-- <h3><?php echo htmlspecialchars($question['question']); ?></h3> -->

            <?php
            /*
            if (isset($result[$answerField])) {
            echo "<p>" . nl2br(htmlspecialchars($result[$answerField])) . "</p>";
            }
            */
                ?>

                <br><br>

                <?php
            if($_SESSION['type'] == 'cm' || $_SESSION['type'] == 'Admin')
            {
            ?>

                <label for="comments"><strong>Comment:</strong></label>
                <br>
                <textarea
                    name="comments[<?php echo $question['number']; ?>"
                    rows="4"
                    cols="80"><?php
                        echo htmlspecialchars($question['comment'] ?? '');
                    ?>
                </textarea>
            <?php
            }
            ?>

            <?php
            if($_SESSION['type'] == 'Applicant' && !empty($question['comment']))
            {
            ?>
                <label for="comments"><strong>Comment:</strong></label><br>

            <?php echo nl2br(htmlspecialchars($question['comment'])); ?>
                
            <?php
            }
            ?>

        <?php endwhile; ?>
            <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($id); ?>">
            <?php
            
                if($_SESSION['type'][0] == 'cm' || $_SESSION['type'] == 'Admin'):
            ?>
                <button type="submit"> Save Comments </button>
            <?php endif;
            ?>
        </form>
    <?php endif;?>
        <p><a href="index.php">Back to Dashboard</a></p>

    </body>
</html>