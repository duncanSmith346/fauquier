<?php
/** 
 * Page to search for and remove multiple courses from the dbCourses simultaneously 
 * @ Author Emily Lambert
 * @ Version April 17 2024
 **/

// Include necessary files
require_once('include/input-validation.php');
require_once('database/dbCourses.php');
require_once('database/dbEvents.php');
require_once('database/dbTrainingPeriods.php');

// Check if user is logged in and has appropriate access level
session_cache_expire(30);
session_start();
$loggedIn = false;
$accessLevel = 0;
$userID = null;
if (isset($_SESSION['_id'])) {
    $loggedIn = true;
    $accessLevel = $_SESSION['access_level'];
    $userID = $_SESSION['_id'];
} 
// Redirect if user does not have sufficient access level
if ($accessLevel < 2) {
    header('Location: login.php');
    die();
}

// Fetch all courses from the database
$courses = fetch_all_courses();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if course names are provided
    if (isset($_POST["course_names"])) {
        $course_names = $_POST["course_names"];
        $failed_courses = [];

        // Remove each course from the database
        foreach ($course_names as $course_name) {
            $success = remove_course_from_courses($course_name);
            if (!$success) {
                $failed_courses[] = $course_name;
            }
        }

        if (empty($failed_courses)) {
            // Redirect to the calendar page upon successful removal
            header("Location: calendar.php?deleteSuccess");
            exit();
        } else {
            $message = "<p>Failed to remove the following courses: " . implode(", ", $failed_courses) . "</p>";
        }
    } else {
        $message = "<p>Please select at least one course to remove</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Empowerhouse VMS | Remove Course</title>
    <style>
        #selected-courses {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php require_once('header.php'); ?>
    <h1>Remove Course</h1>
    <main class="date">
        <h2>Select Courses to Remove</h2>
        <?php if (isset($message)) echo $message; ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <fieldset>
                <legend>Course Selection</legend>

                <!-- Dropdown menu for courses -->
                <label for="course-dropdown">Select Course(s):</label>
                <select id="course-dropdown" name="course_names[]" multiple>
                    <option value="" disabled>Select a course...</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['name']); ?>">
                            <?php echo htmlspecialchars($course['name'] . " - " . $course['date'] . " | " . $course['startTime'] . " - " . $course['endTime']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div id="selected-courses">
                    <h3>Courses to Remove:</h3>
                </div>

                <button type="submit" name="confirm" value="yes">Remove Selected Courses</button>
                <button type="submit" name="confirm" value="no">Cancel</button>
            </fieldset>
        </form>
        <button onclick="window.location.href='index.php';">Back to Dashboard</button>
    </main>
</body>
</html>
