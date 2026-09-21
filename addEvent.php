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

require_once('include/input-validation.php');
require_once('database/dbEvents.php');
require_once('database/dbCourses.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize data
    $args = sanitize($_POST, null);

    // 2. Make sure required fields are present
    $required = ["eventname", "date", "start-time", "end-time", "trainer", "description", "location", "capacity"];
    if (!wereRequiredFieldsSubmitted($args, $required)) {
        echo 'bad form data';
        die();
    }

    // 3. Validate and convert times FIRST
   $validated = validate12hTimeRangeAndConvertTo24h($args['start-time'], $args['end-time']);
if (!$validated) {
    echo 'bad time range';
    die();
}

    $start24 = $validated[0];
    $end24   = $validated[1];



    // 4. Validate date
    $date = validateDate($args['date']);
    if (!$date) {
        echo 'bad date';
        die();
    }

    // 5. Get capacity
    $capacity = intval($args['capacity']);
    if ($capacity < 1 || $capacity > 20) {
        echo 'Capacity must be between 1 and 20';
        die();
    }

    // 6. Now we can create the event, since we have validated data
    $id = create_event(
    $args['eventname'],
    $date,
    $start24,
    $end24,
    $args['trainer'],
    $args['description'],
    $args['location'],
    $capacity
);

    if (!$id) {
        echo "Failed to create event!";
        die();
    }

    // 7. Then create course
    $course     = $args['eventname'];
    $courseAbbr = strtoupper(substr(preg_replace('/\s+/', '', $course), 0, 3));

    $courseArgs = [
        $course,                // 0: Course Name
        $courseAbbr,            // 1: Abbrev
        $args['trainer'],       // 2: Trainer
        $id,                    // 3: Event ID (from create_event)
        $date,                  // 4: Date
        $args['start-time'],    // 5: Original posted times for the course table
        $args['end-time'],      // 6:
        $args['description'],   // 7:
        $args['location'],      // 8:
        $capacity,              // 9:
        ''                      // 10: (additional if needed)
    ];
    create_course($courseArgs);

    // 8. Redirect
    header("Location: calendar.php?event-filter=$id&createSuccess");
    die();
}


// Validate GET parameter "date" for pre-filling form fields if present.
$date = null;
if (isset($_GET['date'])) {
    $date = $_GET['date'];
    $datePattern = '/[0-9]{4}-[0-9]{2}-[0-9]{2}/';
    $timeStamp = strtotime($date);
    if (!preg_match($datePattern, $date) || !$timeStamp) {
        header('Location: calendar.php');
        die();
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <?php require_once('universal.inc'); ?>
        <title>Empowerhouse VMS | Create Event</title>
    </head>
    <body>
        <?php require_once('header.php'); ?>
        <h1>Create Event</h1>
        <main class="date">
            <h2>New Event Form</h2>
            <form id="new-event-form" method="post">
                <label for="eventname">Event Name </label>
                <input type="text" id="eventname" name="eventname" required placeholder="Enter name">
                <fieldset>
                    <legend>Event Info</legend>

                    <label for="date">Date</label>
                    <input 
                        type="date" 
                        id="date" 
                        name="date" 
                        min="<?php echo date('Y-m-d'); ?>" 
                        <?php if($date) echo 'value="'. $date .'"'; ?> 
                        required
                    >

                    <label for="start-time">Start Time</label>
                    <input 
                        type="text" 
                        id="start-time" 
                        name="start-time" 
                        pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])" 
                        placeholder="Ex. 12:00 PM" 
                        required
                    >

                    <label for="end-time">End Time</label>
                    <input 
                        type="text" 
                        id="end-time" 
                        name="end-time" 
                        pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])" 
                        placeholder="Ex. 4:00 PM" 
                        required
                    >

                    <p id="date-range-error" class="error hidden">
                        Start time must come before end time
                    </p>

                    <label for="trainer">Taught By</label>
                    <input 
                        type="text" 
                        id="trainer" 
                        name="trainer" 
                        placeholder="Enter trainer name" 
                        required
                    >

                    <label for="description">Description</label>
                    <input 
                        type="text" 
                        id="description" 
                        name="description" 
                        placeholder="Enter description" 
                        required
                    >

                    <label for="location">Location</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        placeholder="Enter location" 
                        required
                    >

                    <label for="capacity">Volunteer Slots</label>
                    <input 
                        type="text" 
                        id="capacity" 
                        name="capacity" 
                        pattern="([1-9])|([01][0-9])|(20)" 
                        placeholder="Enter a number" 
                        required
                    >
                </fieldset>
                <input type="submit" value="Create Event">
            </form>
            <?php if ($date): ?>
                <a class="button cancel" href="calendar.php?month=<?php echo substr($date, 0, 7); ?>" style="margin-top: -.5rem">Return to Calendar</a>
            <?php else: ?>
                <a class="button cancel" href="index.php" style="margin-top: -.5rem">Return to Dashboard</a>
            <?php endif; ?>
        </main>
    </body>
</html>
