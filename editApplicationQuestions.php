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
                <a href="addQuestion.php?form=sampleapplicatio" class='navigation_button'> Add Question</a>
                <a href='index.php' class='navigation_button'> Home</a>
                <a href="Forms.php" class="navigation_button"> Forms</a>
                <a href="index.php" class="navigation_button"> Committee Members</a>
                <a href="personSearch.php" class="navigation_button"> Person Search</a>
                <a href="logout.php" class="navigation_button"> logout</a>
            </div>
            <div class='question_list'>
                <?php
                    $questions = getQuestions('sampleapplicatio');
                    foreach($questions as $question):
                ?>
                <div class='question_card'>
                    <?php
                    echo $question['number'];
                    echo " ";
                    echo $question['question'];
                    echo " ";
                    echo $question['type'];
                    if ($question['type'] === "multipleChoice" || $question['type'] === "checkbox"):
                    ?>
                    <p>Possible answers include:</p>
                    <ul>
                        <?php
                        $answers = getAnswers($question['number'],'sampleapplicatio');
                        foreach($answers as $answer):
                        ?>
                        <li>
                            <a class='remove_answer_link' href='removeAnswer.php?question=<?php echo $answer['number']; ?>
                            &answer=<?php echo $answer['answer']; ?>
                            &form=sampleapplicatio'>
                                <?php echo $answer['answer'] ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <form action="AddAnswer.php" Method='GET'>
                        <input type='hidden' name='form' value='sampleapplicatio'/>
                        <button type='submit' name='number' value='<?php echo $question['number']; ?>'>Add Answers</button>
                    </form>
                    <?php endif; ?>
                    <form action="removeQuestion.php" Method='GET'>
                        <input type='hidden' name='form' value='sampleapplicatio'/>
                        <button type='submit' name='number' value='<?php echo $question['number']; ?>'>Remove</button>
                    </form>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </main>
</html>