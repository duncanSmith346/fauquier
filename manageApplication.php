
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

// Only applicants can access this page.
if (!$person || !in_array('applicant', $person->get_type(), true)) {
    http_response_code(403);
    exit("Access denied.");
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

    <form method="POST">
        <label for="name">Full Name:</label>
        <input type="text" id="name" name="name" required>

        <br><br>

        <label for="message">Tell us about yourself:</label>
        <textarea id="message" name="message" rows="6" required></textarea>

        <br><br>

        <button type="submit">Submit Application</button>
    </form>

    <p><a href="index.php">Back to Dashboard</a></p>

</body>
</html>
