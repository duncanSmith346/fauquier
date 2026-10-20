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
?>
<DOCTYPE html>
<html>
    <head>
        <?php require('universal.inc'); ?>
        <title>Edit Application Questions | Fauquier Leadership</title>
    </head>
    <main>
        <h1>Edit Application</h1>
        <div class="forms_dashboard">
            <div class="navigation">
                <a href="Forms.php" class="navigation_button"> Forms</a>
                <a href="index.php" class="navigation_button"> Committee Members</a>
                <a href="personSearch.php" class="navigation_button"> Person Search</a>
                <a href="logout.php" class="navigation_button"> logout</a>
            </div>
        </div>
        <form action="addApplicationQuestion.php">
            <button type='submit'>Add Question</button>
        </form>
    </main>
</html>