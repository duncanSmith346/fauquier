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
        include_once('database/dbApplication.php');
        $name = $_POST['name'];
        $maxScore = $_POST['maxScore'];
        $weight = $_POST['weight'];
        $success = addScoringCategory($name, $maxScore, $weight);
        if ($success) {
            header("Location: editScoring.php");
            exit;
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <?php require('universal.inc'); ?>
        <title>Add Scoring - Leadership Fauquier</title>
    </head>
    <body>
        <h1>Add Scoring</h1>
        <main>
            <div class="question-container">
                <form action='addScoreCategory.php' method='POST'>
                    <label for="question">Category Name:</label>
                    <input type="text" id="name" name="name" required/>
                    <label for="maxScore">Max Score:</label>
                    <input type='number' id='maxScore' name='maxScore' required/>
                    <label for='weight'>Weight:</label>
                    <input type='number' id='weight' name='weight' required/>
                    <button type='submit'>Add Score Category</button>
                </form>
            </div>
        </main>
    </body>
</html>