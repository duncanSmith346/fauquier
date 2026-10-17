
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

// Set the current application cycle.
$cycle_id = '2026';
// Check whether this user already has an application for this cycle.
$existing_application = get_applicant_application(
    $_SESSION['_id'],  // ID of the logged-in user.
    $cycle_id         // Program cycle being checked.
);

// Only applicants can access this page.
if (!$person || !in_array('applicant', $person->get_type(), true)) {
    http_response_code(403);
    exit("Access denied.");
}

// Process the application when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Stop applicants from submitting twice in the same cycle.
    if ($existing_application) {
        $error = "You have already submitted an application for this cycle.";

    } else {
        // Read the submitted form fields.
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
 <?php endif; ?>

    <p><a href="index.php">Back to Dashboard</a></p>

</body>
</html>
