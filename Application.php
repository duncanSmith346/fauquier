
<?php
session_start();

require_once('database/dbPersons.php');
require_once('database/dbApplication.php');

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
// Check whether this user already has an application for this cycle.
$existing_application = get_applicant_application(
    $_SESSION['_id'],  // ID of the logged-in user.
    $cycle_id         // Program cycle being checked.
);


// Process the application when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Stop applicants from submitting twice in the same cycle.
    if ($existing_application) {
        $error = "You have already submitted an application for this cycle.";

    } else {
        // Read the submitted form fields.
        if ($applicant){
            $name = trim($_POST['name'] ?? '');
            $message = trim($_POST['message'] ?? '');

            // Make sure both fields contain information.
            if ($name === '' || $message === '') {
                $error = "Please complete both fields.";

            } else {
                // Create a unique ID for this application.
                $application_id = bin2hex(random_bytes(16));

                // Get the logged-in applicant's ID.
                $applicant_id = $_SESSION['_id'];

                // Save the application to MySQL.
                $success = add_application(
                    $application_id,
                    $applicant_id,
                    $cycle_id,
                    $name,
                    $message
                );

                // Return to this page after saving.
                if ($success) {
                    header("Location: manageApplication.php?submitted=1");
                    exit;
                } else {
                    $error = "Unable to submit application.";
                }
            }
        }
        elseif($cm) {
            $comment = trim($_POST['comment'] ?? '');
            $applicant_id = $_POST['$applicant_id'];
            $success = add_comment($comment,$applicant_id);
            if ($success) {
                header("Location: manageApplication.php?commentsubmitted=1");
                exit;
            } else {
                $error = "Unable to submit comment.";
            }
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
    if ($status === 'yes') {
        echo "<p>Accepted</p>";
    } elseif ($status === 'no') {
        echo "<p>Rejected</p>";
    } else {
        echo "<p>Pending Review</p>";
    }
    ?>

    <?php else: ?>

    <!-- No application exists yet, so show the form. -->

    <form method="POST">
        <label for="name">Full Name:</label>
        <input type="text" id="name" name="name" required>

        <br><br>

        <label for="message">Tell us about yourself:</label>
        <textarea id="message" name="message" rows="6" required></textarea>

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

<?php     
    elseif($cm):
        $id = $_GET['id'];
        $result = retrieve_application($id);

?>
    
    <p>Full name: <?php echo $result['answer1'] ?> </p>
    <p>Tell us about yourself: <?php echo $result['answer2'] ?>

    <form action="Application.php" method='POST'>
        <input type="text" name='comment'>
        <input type='hidden' name='application_id' value=<?php echo $id ?>/>
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
