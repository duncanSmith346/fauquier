<?php
// Template for new Cohort pages. Base your new page on this one

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

<!DOCTYPE html>
<html>
    <head>
        <?php require('universal.inc'); ?>
        <title>Forms - Leadership Fauquier</title>
    </head>
    <body>
        <?php require('header.php'); ?>
        <h1>Forms</h1>
        <main class='dashboard'>
            <div class="admin_dashboard">
                <div class="navigation">
                    <a href="index.php" class="navigation_button"> Home</a>
                    <a href="manageCommitteeMembers.php" class="navigation_button"> Committee Members</a>
                    <a href="personSearch.php" class="navigation_button"> Person Search</a>
                    <a href="logout.php" class="navigation_button"> Log out</a> 
                </div>
                <a href="editApplicationQuestions.php">
                    <div class="form_dashboard-item">
                        <img src="images/document.svg">
                        <span>Application</span>
                    </div>
                </a>
                <a href="editScholarshipQuestions.php">
                    <div class="form_dashboard-item">
                        <img src="images/document.svg">
                        <span>Scholarship</span>
                    </div>
                </a>
                <a href="editCommitmentQuestions.php">
                    <div class="form_dashboard-item">
                        <img src="images/document.svg">
                        <span>Onboarding</span>
                    </div>
                </a>
            </div>
        </main>
    </body>
</html>