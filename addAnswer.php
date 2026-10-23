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
$success = 0;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    include_once('database/dbQuestions.php');
    $question = $_GET['number'];
    if (isset($_GET['answer1'])) {
        if ($_GET['answer1'] !== "" && $_GET['answer1'] !== NULL) {
            $answer1 = $_GET['answer1'];
            $success = addAnswer($question, $answer1);    
        }
    }
    if (isset($_GET['answer1'])) {
        if ($_GET['answer2'] !== "" && $_GET['answer2'] !== NULL) {
            $answer2 = $_GET['answer2'];
            $success = addAnswer($question, $answer2);    
        }
    }
    if (isset($_GET['answer1'])) {
        if ($_GET['answer3'] !== "" && $_GET['answer3'] !== NULL) {
            $answer3 = $_GET['answer3'];
            $success = addAnswer($question, $answer3);    
        }
    }
    if (isset($_GET['answer1'])) {
        if ($_GET['answer4'] !== "" && $_GET['answer4'] !== NULL) {
            $answer4 = $_GET['answer4'];
            $success = addAnswer($question, $answer4);    
        }
    }
    if (isset($_GET['answer1'])) {
        if ($_GET['answer5'] !== "" && $_GET['answer5'] !== NULL) {
            $answer5 = $_GET['answer5'];
            $success = addAnswer($question, $answer5);    
        }
    }

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
        <h1>Add Ansers</h1>
    </main>
        <h2><?php echo "" ?>
        <form action='addAnswer.php' method='GET'>
            <label for="answer1">Answer 1:</label>
            <input type='text' name="answer1">
            <label for="answer2">Answer 2:</label>
            <input type='text' name="answer2">
            <label for="answer3">Answer 3:</label>
            <input type='text' name="answer3">
            <label for="answer4">Answer 4:</label>
            <input type='text' name="answer4">
            <label for="answer5">Answer 5:</label>
            <input type='text' name="answer5">
            <input type='hidden' name='number' value='<?php echo $question; ?>'/>
            <button type='submit'>Add Answers</button>
        </form>
    </main>
</html>