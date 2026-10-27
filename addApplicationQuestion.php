<?php
// Template for new VMS pages. Base your new page on this one

// Make session information accessible, allowing us to associate
// data with the logged-in user.
session_cache_expire(30);
session_start();
ini_set("display_errors", 1);
error_reporting(E_ALL);

$loggedIn = false;
$accessLevel = 0;
$userID = null;
if (isset($_SESSION['_id'])) {
    $loggedIn = true;
    // 0 = not logged in, 1 = standard user, 2 = manager (Admin), 3 = super admin (TBI)
    $accessLevel = $_SESSION['access_level'];
    $userID = $_SESSION['_id'];
} 
// Require admin privileges
if ($accessLevel < 2) {
    header('Location: login.php');
    echo 'bad access level';
    die();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once('database/dbQuestions.php');
    $question = $_POST['question'];
    $type = $_POST['type'];
    $success = addQuestion($question, $type,'sampleapplicatio');
    if ($success) {
        header("Location: editApplicationQuestions.php");
        exit;
    }
}
?>

<DOCTYPE html>
<html>
    <head>
        <?php require('universal.inc'); ?>
        <title>Edit Application Questions | Fauquier Leadership</title>
    </head>
    <main>
        <h1>Add Question</h1>
    </main>
        <form action='addApplicationQuestion.php' method='POST'>
            <label for="question">Question:</label>
            <input type="text" id="question" name="question" required>
            <label for="type">Answer Type:</label>
            <select id='type' name='type'>
                <option value='text'>Short Text</option>
                <option value='textarea'> Long Text</option>
                <option value='multipleChoice'>Multiple Choice</option>
                <option value='date'>Date</option>
                <option value='checkbox'>Checkbox</option>
                <option value='email'>Email</option>
                <option value='tel'>Phone Number</option>
            </select>
            <button type='submit'>Add Question</button>
        </form>
    </main>
</html>
