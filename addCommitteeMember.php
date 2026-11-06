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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include_once('database/dbQuestions.php');
        include_once('database/dbPersons.php');

        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= mt_rand(0, 9);
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $success = add_cm($firstname, $lastname, $email, $phone);
    }


?>

<!DOCTYPE html>
<html>
    <head>
        <?php require('universal.inc'); ?>
        <title>Add Committee Member - Leadership Fauquier</title>
    </head>
    <body>
        <h1>Add Committee Member</h1>
        <main>
            <?php if ($success):
                echo $password;?>
            
            <a href="index.php" class="navigation_button"> Home</a>
            <?php else:?>
            <div class="question-container">
                <form action='addCommitteeMember.php' method='POST'>
                    <label for="firstname">First Name:</label>
                    <input type="text" id="firstname" name="firstname" required/>
                    <label for='lastname'>Last Name:</label>
                    <input type='text' id='lastname' name='lastname' requried/>
                    <label for='email'>Email:</label>
                    <input type='email' id='email' name='email'/>
                    <label for='phone'>Phone:</label>
                    <input type='tel' id='phone' name='phone'/>
                    <button type='submit'>Add Committee Member</button>
                </form>
                <a href="index.php" class="navigation_button"> Home</a>
            </div>
            <?php endif;?>
        </main>
    </body>
</html>
