<?php
//Add version, date, author, and purpose of the file

session_cache_expire(30);
session_start();

// Ensure user is logged in
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    header('Location: login.php');
    die();
}

require_once('include/input-validation.php');
$args = sanitize($_GET);
if (isset($args["id"])) {
    $id = $args["id"];
} else {
    header('Location: calendar.php');
    die();
}

include_once('database/dbEvents.php');
include_once('database/dbCourses.php'); // Only keep if you still need it for other logic

// Retrieve the event from dbEvents
$event_info = retrieve_event($id);
if ($event_info == NULL) {
    // No event found
    echo 'bad event id';
    die();
}

include_once('database/dbPersons.php');
$access_level = $_SESSION['access_level'];
$user = retrieve_person($_SESSION['_id']);
$active = $user->get_status() == 'Active';

ini_set("display_errors",1);
error_reporting(E_ALL);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $args = sanitize($_POST);
    $get = sanitize($_GET);
    // Attach media, etc. (unchanged logic)
    // ...
} else {
    // Handle GET actions like add self, add another, remove
    if (isset($args["request_type"])) {
        $request_type = $args['request_type'];
        if (!valueConstrainedTo($request_type, 
                array('add self', 'add another', 'remove'))) {
            echo "Bad request";
            die();
        }
        $eventID = $args["id"];

        if ($request_type == 'add self' && $access_level >= 1) {
            // Only active volunteers
            if (!$active) {
                echo 'forbidden';
                die();
            }
            $volunteerID = $args['selected_id'];
            $person = retrieve_person($volunteerID);
            $name = htmlspecialchars_decode($person->get_first_name() . ' ' . $person->get_last_name());

            // This updates dbEventVolunteers table
            update_event_volunteer_list($eventID, $volunteerID);
            require_once('database/dbMessages.php');
            require_once('include/output.php');
            // Example call (but be sure it matches your actual logic/columns):
            $events = fetch_course_by_eventId($eventID);
        

        } else if ($request_type == 'add another' && $access_level > 1) {
            $volunteerID = strtolower($args['selected_id']);
            if ($volunteerID == 'vmsroot') {
                echo 'invalid user id';
                die();
            }
            update_event_volunteer_list($eventID, $volunteerID);
            // ...
            // More logic with dbCourses or dbMessages
        } else if ($request_type == 'remove' && $access_level > 1) {
            $volunteerID = $args['selected_removal_id'];
            remove_volunteer_from_event($eventID, $volunteerID);
        } else {
            header('Location: event.php?id=' . $eventID);
            die();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Empowerhouse VMS | View Event: <?php echo $event_info['eventname'] ?></title>
    <link rel="stylesheet" href="css/event.css" type="text/css" />
    <?php if ($access_level >= 2) : ?>
        <script src="js/event.js"></script>
    <?php endif ?>
</head>

<body>
    <?php if ($access_level >= 2) : ?>
    <div id="delete-confirmation-wrapper" class="hidden">
        <div id="delete-confirmation">
            <p>Are you sure you want to delete this event?</p>
            <p>This action cannot be undone</p>
            <form method="post" action="deleteEvent.php">
                <!-- Use `id` instead of `eventId` -->
                <input type="hidden" name="id" value="<?= $event_info['id'] ?>">
                <input type="submit" value="Delete Event">
            </form>
            <button id="delete-cancel">Cancel</button>
        </div>
    </div>
    <?php endif ?>

    <?php require_once('header.php') ?>
    <h1>View Event</h1>
    <main class="event-info">
        <?php
            require_once('include/output.php');
            require_once('include/time.php');

            // Use eventdate, starttime, endtime, eventname, etc.
            $event_name       = $event_info['eventname'];
            $event_date       = date('l, F j, Y', strtotime($event_info['eventdate']));
            $event_startTime  = time24hto12h($event_info['starttime']);
            $event_endTime    = time24hto12h($event_info['endtime']);
            $event_location   = $event_info['location'];
            $event_description= $event_info['description'];

            // Check if event is in the past:
            // Compare today's date (Y-m-d) to the eventdate (also Y-m-d).
            $event_in_past = strcmp(date('Y-m-d'), $event_info['eventdate']) > 0;

            // Calculate duration in hours
            $event_duration = calculateHourDuration($event_info['starttime'], $event_info['endtime']);
            $event_duration = floatPrecision($event_duration, 2);
            if ($event_duration == floor($event_duration)) {
                $event_duration = intval($event_duration);
            }

            echo '<h2 class="centered">'.$event_name.'</h2>';
        ?>
        <div id="table-wrapper">
            <table class="centered">
                <tbody>
                    <tr>
                        <td class="label">Date </td>
                        <td><?php echo $event_date ?></td>
                    </tr>
                    <tr>
                        <td class="label">Time </td>
                        <td><?php echo $event_startTime.' - '.$event_endTime ?></td>
                    </tr>
                    <tr>
                        <td class="label">Duration</td>
                        <td><?php echo $event_duration . ' hours' ?></td>
                    </tr>
                    <tr>
                        <td class="label">Location </td>
                        <td><?php echo $event_location ?></td>
                    </tr>
                    <tr>
                        <td class="label">Description </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td id="description-cell" colspan="2"><?php echo $event_description ?></td>
                    </tr>
                    <?php if ($access_level >= 2): ?>
                    <tr>
                        <td colspan="2">
                            <!-- Use `id` instead of `eventId` -->
                            <a href="editEvent.php?id=<?php echo $event_info['id'] ?>" class="button">
                                Edit Event Details
                            </a>
                        </td>
                    </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>

        <h2 class="centered">Event Volunteers</h2>
        <div class="standout">
            <ul class="centered">
                <?php
                    // getvolunteers_byevent($id) should look up in dbEventVolunteers
                    $event_persons = getvolunteers_byevent($id);

                    // capacity is stored in $event_info['capacity']
                    $capacity = intval($event_info['capacity']);
                    $num_persons = count($event_persons);
                    $user_id = $_SESSION['_id'];

                    $remaining_slots = $capacity - $num_persons;
                    $already_assigned = false;

                    // Display how many slots remain
                    if ($remaining_slots > 0) {
                        echo '<li class="centered">' . $remaining_slots . ' / ' . $capacity . ' Slots Remaining</li>';
                    } else {
                        echo '<li class="centered">This event is fully booked!</li>';
                    }

                    // List assigned volunteers
                    for ($x = 0; $x < $num_persons; $x++) {
                        $person = $event_persons[$x];
                        if ($person->get_id() == $user_id) {
                            $already_assigned = true;
                        }
                        // allow admins/super admins to remove assigned volunteers
                        if ($access_level > 1) {
                            echo '<li class="centered remove-person">'
                                . '<span>'
                                . $person->get_first_name() . ' ' . $person->get_last_name()
                                . '</span>'
                                . '<form class="remove-person" method="GET">'
                                . '<input type="hidden" name="request_type" value="remove" />'
                                . '<input type="hidden" name="id" value="'.$id.'">'
                                . '<input type="hidden" name="selected_removal_id" value="'.$person->get_id().'" />'
                                . '<input class="stripped" type="submit" value="Remove" />'
                                . '</form></li>';
                        } else {
                            echo '<li class="centered">'
                                . $person->get_first_name() . ' ' . $person->get_last_name()
                                . '</li>';
                        }
                    }

                    // Show empty slots
                    for ($x = 0; $x < $remaining_slots; $x++) {
                        echo '<li class="centered empty-slot">-Empty Slot-</li>';
                    }
                ?>
            </ul>

            <?php
                // If there are remaining slots & user is not root & event is not in past
                if ($remaining_slots > 0 && $user_id != 'vmsroot' && !$event_in_past) {
                    if (!$already_assigned) {
                        if ($active) {
                            // Sign up button
                            echo '
                            <form method="GET">
                                <input type="hidden" name="request_type" value="add self">
                                <input type="hidden" name="id" value="'.$id.'">
                                <input type="hidden" name="selected_id" value="'.$_SESSION['_id'].'">
                                <input type="submit" value="Sign Up">
                            </form>
                            ';
                        } else {
                            echo '<div class="centered">As an inactive volunteer, you are ineligible to sign up for events.</div>';
                        }
                    } else {
                        echo '<div class="centered">You are signed up for this event!</div>';
                    }
                } else if ($already_assigned) {
                    if ($event_in_past) {
                        echo '<div class="centered">You attended this event!</div>';
                    } else {
                        echo '<div class="centered">You are signed up for this event!</div>';
                    }
                }

                // If admin or super admin wants to see roster
                if ($access_level >= 2 && $num_persons > 0) {
                    echo '<br/><a href="roster.php?id='.$id.'" class="button">View Event Roster</a>';
                }
            ?>
        </div>

        <?php
            // Optionally allow admin to "Assign Volunteer"
            if ($remaining_slots > 0 && $access_level >= 2) {
                if ($event_in_past) {
                    echo '<div id="assign-volunteer" class="standout"><label>Assign Volunteer</label><p>This event is archived. Volunteers cannot be assigned.</p></div>';
                } else {
                    // Example: $all_volunteers = get_unassigned_available_volunteers($id);
                    // For now, we set $all_volunteers = NULL just as placeholder
                    $all_volunteers = NULL;

                    if ($all_volunteers) {
                        echo '<form method="GET" id="assign-volunteer" class="standout">';
                        echo '<input type="hidden" name="request_type" value="add another">';
                        echo '<input type="hidden" name="id" value="'.$id.'">';
                        echo '<label for="volunteer-select">Assign Volunteer:</label>';
                        echo '<div class="pair"><select name="selected_id" id="volunteer-select" required>';
                        // Build volunteer dropdown
                        for ($x = 0; $x < count($all_volunteers); $x++) {
                            $v = $all_volunteers[$x];
                            echo '<option value="'.$v->get_id().'">'
                                 . $v->get_last_name() . ', ' . $v->get_first_name()
                                 . '</option>';
                        }
                        echo '</select>';
                        echo '<input type="submit" value="Assign" /></div>';
                        echo '</form>';
                    } else {
                        echo '<div id="assign-volunteer" class="standout"><label>Assign Volunteer</label><p>There are currently no volunteers available to assign to this event.</p></div>';
                    }
                }
            }
        ?>

        <?php if ($access_level >= 2): ?>
            <button onclick="showDeleteConfirmation()">Delete Event</button>
        <?php endif ?>

        <!-- Link back to calendar, using `eventdate` -->
        <a href="calendar.php?month=<?php echo substr($event_info['eventdate'], 0, 7) ?>"
           class="button cancel"
           style="margin-top: -.5rem">
           Return to Calendar
        </a>
    </main>
</body>
</html>
