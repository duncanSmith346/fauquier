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
    require_once('database/dbApplication.php');
?>
<!DOCTYPE html>
<html>
    <head>
        <?php require('universal.inc'); ?>
        <title>Edit Scoring - Leadership Fauquier</title>
    </head>
    <body>
        <h1>Edit Scoring</h1>
        <main class='dashboard'>
            <div class="forms_dashboard">
                <div class="navigation">
                    <a href="addScoreCategory.php" class='navigation_button'>Add Scoring Category</a>
                    <a href='index.php' class='navigation_button'> Home</a>
                    <a href="forms.php" class="navigation_button"> Forms</a>
                    <a href="index.php" class="navigation_button"> Committee Members</a>
                    <a href="personSearch.php" class="navigation_button"> Person Search</a>
                    <a href="logout.php" class="navigation_button"> Log out</a>
                </div>
                <div class='question_list'>
                <?php $categories = getScoringCategoiesAll(); 
                foreach($categories as $category): ?>
                    <div class='question_card'>
                        <?php
                        
                            echo $category['category'];
                            echo ", Max Score: ";
                            echo $category['score'];
                            echo ", Weight: ";
                            echo $category['weight'];
                        ?>
                        <form action="removeScoringCategory.php" Method='GET'>
                            <button class="removeButton" type='submit' name='category' value='<?php echo $category['category']; ?>'>Remove</button>
                        </form>
                    </div>
                <?php endforeach ?>
                </div>
            </div>
        </main>
    </body>
</html>