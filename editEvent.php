<?php
    // Make session information accessible, allowing us to associate
    // data with the logged-in user.
    session_cache_expire(30);
    session_start();

    ini_set("display_errors",1);
    error_reporting(E_ALL);

    $loggedIn = false;
    $accessLevel = 0;
    $userID = null;
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
        // 0 = not logged in, 1 = standard user, 2 = manager (Admin), 3 super admin (TBI)
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
    $errors = '';
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $args = sanitize($_POST, null);
        $required = array(
            "id", "eventname", "eventdate", "starttime", "endtime", "description", "location", "capacity", "trainer"
        );
        if (!wereRequiredFieldsSubmitted($args, $required)) {
            echo 'bad form data';
            die();
        } else {
            require_once('database/dbPersons.php');
            $id = $args['id'];
            $validated = validate12hTimeRangeAndConvertTo24h($args["starttime"], $args["endtime"]);
            if (!$validated) {
                $errors .= '<p>The provided time range was invalid.</p>';
            }
            $starttime = $args['starttime'] = $validated[0];
            $endtime = $args['endtime'] = $validated[1];
            $date = $args['eventdate'] = validateDate($args["eventdate"]);
            $capacity = intval($args["capacity"]);
            $assignedVolunteerCount = count(getvolunteers_byevent($id));
            $difference = $assignedVolunteerCount - $capacity;
            if ($capacity < $assignedVolunteerCount) {
                $errors .= "<p>There are currently $assignedVolunteerCount volunteers assigned to this event. The new capacity must not exceed this number. You must remove $difference volunteer(s) from the event to reduce the capacity to $capacity.</p>";
            }
            if (!$starttime || !$endtime || !$date || $capacity < 1 || $capacity > 20){
                $errors .= '<p>Your request was missing arguments.</p>';
            }
            if (!$errors) {
                $success = update_event(
                $id,
                $args['eventname'],
                $args['eventdate'],
                $args['starttime'],
                $args['endtime'],
                $args['description'],
                $args['location'],
                $args['capacity'],
                $args['trainer']
                );

                if (!$success){
                    echo "Oopsy!";
                    die();
                }
                header('Location: event.php?id=' . $id . '&editSuccess');
            }
        }
    }
    if (!isset($_GET['id'])) {
        // uhoh
        die();
    }
    $args = sanitize($_GET);
    $id = $args['id'];
    $event = fetch_event_by_id($id);
    if (!$event) {
        echo "Event does not exist";
        die();
    }
    require_once('include/output.php');
?>
<!DOCTYPE html>
<html>
    <head>
        <?php require_once('universal.inc') ?>
        <title>FUMC VMS | Edit Event</title>
    </head>
    <body>
        <?php require_once('header.php') ?>
        <h1>Modify Event</h1>
        <main class="date">
        <?php if ($errors): ?>
            <div class="error-toast"><?php echo $errors ?></div>
        <?php endif ?>
            <h2>Event Details</h2>
            <form id="new-event-form" method="post">
                <label for="eventname">Event Name </label>
                <input type="hidden" name="id" value="<?php echo $id ?>"/> 
                <input type="text" id="name" name="eventname" value="<?php echo $event['eventname'] ?>" required placeholder="Enter name"> 
                <label for="name">Date </label>
                <input type="date" id="date" name="eventdate" value="<?php echo $event['date'] ?>" min="<?php echo date('Y-m-d'); ?>" required>
                <label for="name">Start Time </label>
                <input type="text" id="starttime" name="starttime" value="<?php echo time24hto12h($event['starttime']) ?>" pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])" required placeholder="Enter start time. Ex. 12:00 PM">
                <label for="name">End Time </label>
                <input type="text" id="endtime" name="endtime" value="<?php echo time24hto12h($event['endtime']) ?>" pattern="([1-9]|10|11|12):[0-5][0-9] ?([aApP][mM])" required placeholder="Enter end time. Ex. 4:00 PM">
                <p id="date-range-error" class="error hidden">Start time must come before end time</p>
                <label for="name">Description </label>
                <input type="text" id="description" name="description" value="<?php echo $event['description'] ?>" required placeholder="Enter description">
                <label for="name">Location </label>
                <input type="text" id="location" name="location" value="<?php echo $event['location'] ?>" required placeholder="Enter location">
                <label for="name">Taught by </label>
                <input type="text" id="trainer" name="trainer" value="<?php echo $event['trainer'] ?>" required placeholder="Enter trainer">
                <label for="name">Volunteer Slots</label>
                <input type="text" id="capacity" name="capacity"  value="<?php echo $event['capacity'] ?>"pattern="([1-9])|([01][0-9])|(20)" required placeholder="Enter a number up to 20">   
                <input type="submit" value="Update Event">
                <a class="button cancel" href="event.php?id=<?php echo htmlspecialchars($_GET['id']) ?>" style="margin-top: .5rem">Cancel</a>
            </form>
        </main>
    </body>
</html>
