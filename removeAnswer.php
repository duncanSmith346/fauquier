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
require_once('database/dbQuestions.php');


$success = removeAnswer($_GET['question'],$_GET['answer'],$_GET['form']);
if ($success) {
    if ($_GET['form'] === 'sampleapplicatio') {
        header("Location: editApplicationQuestions.php");
    } elseif ($_GET['form'] === 'samplescholarshi') {
        header("Location: editScholarshipQuestions.php");
    } elseif ($_GET['form'] === 'samplecommitment') {
        header("Location: editCommitmentQuestions.php");
    }
    exit;
}
?>