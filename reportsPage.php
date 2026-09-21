<?php 
/**
* @version April 30, 2024
* @authors 
*   Alip Yalikun, Alex Keehan, 
*   Diana Guzman, & Tubba Noor
*/

session_cache_expire(30);
session_start();
ini_set("display_errors",1);
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

require_once('include/input-validation.php');
require_once('database/dbPersons.php');
require_once('database/dbEvents.php');
require_once('include/output.php');
  
$get = sanitize($_GET);
$indivID = @$get['indivID'];
$role = @$get['role'];
$indivStatus = @$get['status'];
$type = $get['report_type'];
$dateFrom = $get['date_from'];
$dateTo = $get['date_to'];
$lastFrom = strtoupper($get['lname_start']);
$lastTo = strtoupper($get['lname_end']);
@$stats = $get['statusFilter'];
$today = date('Y-m-d');

$export_array = array();
$totHours = array();

// If only one side of the date range is given, fill in the other
if ($dateFrom != NULL && $dateTo == NULL) {
    $dateTo = $today;
}
if ($dateFrom == NULL && $dateTo != NULL) {
    $dateFrom = date('Y-m-d', strtotime('-1 year'));
}

// If only one side of the name range is given, fill in the other
if ($lastFrom != NULL && $lastTo == NULL) {
    $lastTo = 'Z';
}
if ($lastFrom == NULL && $lastTo != NULL) {
    $lastFrom = 'A';
} 

// Is user authorized to view this page?
if ($accessLevel < 2) {
    header('Location: index.php');
    die();
}

/**
 * Return array of all dates (in Y-m-d format) between $startDate and $endDate (inclusive).
 */
function getBetweenDates($startDate, $endDate)
{
    $dateRange = array();
    $oneDay = 86400;  // 60*60*24

    $start = strtotime($startDate);
    $end   = strtotime($endDate);
           
    for ($current = $start; $current <= $end; $current += $oneDay) {
        $dateRange[] = date('Y-m-d', $current);
    }
    return $dateRange;
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Empowerhouse VMS | Report Result</title>
    <style>
        table {
            margin-top: 1rem;
            margin-left: auto;
            margin-right: auto;
            border-collapse: collapse;
            width: 80%;
        }
        td {
            border: 1px solid #333333;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: var(--main-color);
            color: var(--button-font-color);
            border: 1px solid #333333;
            text-align: left;
            padding: 8px;
            font-weight: 500;
        }
        tr:nth-child(even) {
            background-color: #f0f0f0;
        }

        @media print {
            tr:nth-child(even) {
                background-color: white;
            }
            button, header {
                display: none;
            }
            :root {
                font-size: 10pt;
            }
            label {
                color: black;
            }
            table {
                width: 100%;
            }
            a {
                color: black;
            }
        }
        .theB {
            width: auto;
            font-size: 15px;
        }
        .center_a {
            margin-top: 0;
            margin-bottom: 3rem;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .8rem;
        }
        .center_b {
            margin-top: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .8rem;
        }
        #back-to-top-btn {
            bottom: 20px;
        }
        .back-to-top:visited {
            color: white;  
        }
        .back-to-top {
            color: white; 
        }
        .intro {
            display: flex;
            flex-direction: column;
            gap: .5rem;
            padding: 0;
        }
        @media only screen and (min-width: 1024px) {
            .intro {
                width: 80%;
            }
            main.report {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
        }
        footer {
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php require_once('header.php'); ?>
    <h1>Report Result</h1>
    <main class="report">
        <div class="intro">
            <div>
                <label>Reports Type:</label>
                <span>
                    <?php 
                    echo '&nbsp;&nbsp;&nbsp;'; 
                    if     ($type == "top_perform")         echo "Top Performers";
                    elseif ($type == "general_volunteer_report") echo "General Volunteer Report";
                    elseif ($type == "total_vol_hours")     echo "Total Volunteer Hours";
                    elseif ($type == "indiv_vol_hours")     echo "Individual Volunteer Hours";
                    elseif ($type == "completed_training")  echo "Volunteers Who Completed Training";
                    elseif ($type == "email_volunteer_list")echo "Volunteer Emails";
                    elseif ($type == "missing_paperwork")   echo "Volunteers Missing Paperwork";
                    ?>
                </span>
            </div>

            <div>
            <?php if ($type == "indiv_vol_hours"): ?>
                <label>Name:</label>
                <?php
                    echo '&nbsp;&nbsp;&nbsp;';
                    $con = connect();
                    $query  = "SELECT first_name, last_name 
                               FROM dbPersons 
                               WHERE id='$indivID' ";
                    $result = mysqli_query($con, $query);
                    if ($row = mysqli_fetch_assoc($result)) {
                        echo $row['first_name'] . " " . $row['last_name'];
                    } else {
                        echo "(Not found)";
                    }
                ?>
            <?php else: ?>
                <label>Last Name Range:</label>
                <span>
                    <?php
                        echo '&nbsp;&nbsp;&nbsp;';
                        if ($lastFrom == NULL && $lastTo == NULL) {
                            echo "All last names";
                        } else {
                            echo $lastFrom . " to " . $lastTo;
                        }
                    ?>
                </span>
            <?php endif; ?>
            </div>

            <div>
                <label>Date Range:</label>
                <span>
                <?php 
                    echo '&nbsp;&nbsp;&nbsp;';
                    if (isset($dateFrom) && !isset($dateTo)) {
                        echo $dateFrom . " to Current";
                    } elseif (!isset($dateFrom) && isset($dateTo)) {
                        echo "Every date through " . $dateTo;
                    } elseif ($dateFrom == NULL && $dateTo == NULL) {
                        echo "All dates";
                    } else {
                        echo $dateFrom . " to " . $dateTo;
                    }
                ?>
                </span>
            </div>

            <div>
                <label>Volunteer Status:</label>
                <span>
                    <?php 
                    echo '&nbsp;&nbsp;&nbsp;';
                    // For individual hours, we show $indivStatus; 
                    // otherwise, we show $stats
                    if ($type == 'indiv_vol_hours') {
                        echo $indivStatus;
                    } else {
                        echo $stats;
                    }
                    ?>
                </span>
            </div>

            <div>
                <?php if ($type == "indiv_vol_hours"): ?>
                    <label>Role:</label>
                    <span>
                    <?php
                        echo '&nbsp;&nbsp;&nbsp;';
                        $con   = connect();
                        $query = "SELECT type 
                                  FROM dbPersons 
                                  WHERE id='$indivID' ";
                        $result = mysqli_query($con, $query);
                        if ($r = mysqli_fetch_assoc($result)) {
                            // If you actually want to show the DB type, do this:
                            echo $r['type']; 
                        } else {
                            // fallback to role param or a default
                            echo $role;
                        }
                    ?>
                    </span>
                <?php endif; ?>
            </div>

            <div>
            <?php
            // If the report needs total hours:
            if ($type == "general_volunteer_report" || 
                $type == "total_vol_hours"        || 
                $type == "indiv_vol_hours")
            {
                echo "<label>Total Volunteer Hours:</label> &nbsp;&nbsp;&nbsp;";
                if ($type != 'indiv_vol_hours') {
                    // sums hours for multiple volunteers
                    echo get_tot_vol_hours($type, $stats, $dateFrom, $dateTo, $lastFrom, $lastTo);
                } 
                elseif ($type == 'indiv_vol_hours' && $dateTo == NULL && $dateFrom == NULL) {
                    // sums hours for a single volunteer with no date filter
                    echo get_hours_volunteered_by($indivID);
                } 
                elseif ($type == 'indiv_vol_hours' && $dateTo != NULL && $dateFrom != NULL) {
                    // sums hours for a single volunteer with date filter
                    echo get_hours_volunteered_by_and_date($indivID, $dateFrom, $dateTo);
                }
            }
            ?>
            </div>
        </div>
    </main>

    <div class="center_a">
        <!-- Just placeholders if you want them visible:
        <a href="report.php"><button class="theB">New Report</button></a>
        <a href="index.php"><button class="theB">Home Page</button></a>
        -->
    </div>

    <div class="table-wrapper">
    <?php 
    if ($type == "general_volunteer_report") {
        $sum = 0;
        $totHours = array();
        $con = connect();
        if ($dateFrom == NULL && $dateTo == NULL && $lastFrom == NULL && $lastTo == NULL) {

            if ($stats != "All") {
                $query = "
                  SELECT id, first_name, last_name, phone1, email
                  FROM dbPersons
                  WHERE type='volunteer' AND status='$stats'
                  ORDER BY last_name, first_name
                ";
            } else {
                $query = "
                  SELECT id, first_name, last_name, phone1, email
                  FROM dbPersons
                  WHERE type='volunteer'
                  ORDER BY last_name, first_name
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom != NULL && $lastTo != NULL) {
            // Both date and name range
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name, dbPersons.phone1, dbPersons.email
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate >= '$dateFrom'
                    AND eventDate <= '$dateTo'
                    AND dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbPersons.last_name, dbPersons.first_name
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name, dbPersons.phone1, dbPersons.email
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate >= '$dateFrom'
                    AND eventDate <= '$dateTo'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbPersons.last_name, dbPersons.first_name
                ";
            }
        }
        elseif ($dateFrom == NULL && $dateTo == NULL && $lastFrom != NULL && $lastTo != NULL) {
            // Only name range
            if ($stats != "All") {
                $query = "
                  SELECT id, first_name, last_name, phone1, email
                  FROM dbPersons
                  WHERE type='volunteer' AND status='$stats'
                  ORDER BY last_name, first_name
                ";
            } else {
                $query = "
                  SELECT id, first_name, last_name, phone1, email
                  FROM dbPersons
                  WHERE type='volunteer'
                  ORDER BY last_name, first_name
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom == NULL && $lastTo == NULL) {
            // Only date range
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name, dbPersons.phone1, dbPersons.email
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate >= '$dateFrom'
                    AND eventDate <= '$dateTo'
                    AND dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbPersons.last_name, dbPersons.first_name
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name, dbPersons.phone1, dbPersons.email
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate >= '$dateFrom'
                    AND eventDate <= '$dateTo'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbPersons.last_name, dbPersons.first_name
                ";
            }
        }

        // Execute
        $result = mysqli_query($con, $query);

        if (!$result || mysqli_num_rows($result) == 0) {
            echo '<div class="error-toast">No Results Found</div>';
        } else {
            echo "
              <table>
                <tr>
                  <th>First Name</th>
                  <th>Last Name</th>
                  <th>Phone Number</th>
                  <th>Email Address</th>
                  <th>Volunteer Hours</th>
                </tr>
                <tbody>
            ";
            //Output rows
            while ($row = mysqli_fetch_assoc($result)) {
                $hours = get_hours_volunteered_by($row['id']);
                $phone = $row['phone1'];
                $mail  = $row['email'];

                echo "<tr>
                        <td>{$row['first_name']}</td>
                        <td>{$row['last_name']}</td>
                        <td><a href='tel:$phone'>" . formatPhoneNumber($phone) . "</a></td>
                        <td><a href='mailto:$mail'>{$row['email']}</a></td>
                        <td>$hours</td>
                      </tr>";
                $totHours[] = $hours;

                // For CSV export
                $export_array[] = [
                  $row['first_name'],
                  $row['last_name'],
                  $row['phone1'],
                  $row['email'],
                  $hours
                ];
            }
            // Sum up total
            $sum = array_sum($totHours);

            echo "
              <tr>
                <td style='border: none;' bgcolor='white'></td>
                <td style='border: none;' bgcolor='white'></td>
                <td style='border: none;' bgcolor='white'></td>
                <td style='border: none;' bgcolor='white'></td>
                <td bgcolor='white'><label>Total Hours:</label></td>
                <td bgcolor='white'><label>$sum</label></td>
              </tr>
            </tbody>
            </table>";
        }
    }

    if ($type == "completed_training") {
        $con=connect();

        if ($dateFrom == NULL && $dateTo == NULL && $lastFrom == NULL && $lastTo == NULL) {
            // no date range, no name range
            if ($stats != "All") {
                $query = "
                  SELECT id, first_name, last_name, email, 
                         completedTraining, dateCompletedTraining
                  FROM dbPersons
                  WHERE status='$stats'
                    AND type='volunteer'
                    AND completedTraining='True'
                  GROUP BY first_name, last_name
                ";
            } else {
                $query = "
                  SELECT id, first_name, last_name, email, 
                         completedTraining, dateCompletedTraining
                  FROM dbPersons
                  WHERE type='volunteer' 
                    AND completedTraining='True'
                  GROUP BY last_name
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom == NULL && $lastTo == NULL) {
            // date range only
            if ($stats != "All") {
                $query = "
                  SELECT first_name, last_name, email, 
                         completedTraining, dateCompletedTraining
                  FROM dbPersons
                  WHERE completedTraining='True'
                    AND (dateCompletedTraining BETWEEN '$dateFrom' AND '$dateTo')
                    AND type='volunteer'
                    AND status='$stats'
                  ORDER BY last_name
                ";
            } else {
                $query = "
                  SELECT first_name, last_name, email, 
                         completedTraining, dateCompletedTraining
                  FROM dbPersons
                  WHERE completedTraining='True'
                    AND (dateCompletedTraining BETWEEN '$dateFrom' AND '$dateTo')
                    AND type='volunteer'
                  ORDER BY last_name
                ";
            }
        }
        elseif ($dateFrom == NULL && $dateTo == NULL && $lastFrom != NULL && $lastTo != NULL) {
            // name range only
            if ($stats != "All") {
                $query = "
                  SELECT first_name, last_name, email,
                         completedTraining, dateCompletedTraining
                  FROM dbPersons
                  WHERE completedTraining='True'
                    AND LOWER(LEFT(last_name, 1)) BETWEEN '$lastFrom' AND '$lastTo'
                    AND type='volunteer'
                    AND status='$stats'
                  GROUP BY dateCompletedTraining, last_name
                ";
            } else {
                $query = "
                  SELECT first_name, last_name, email,
                         completedTraining, dateCompletedTraining
                  FROM dbPersons
                  WHERE completedTraining='True'
                    AND LOWER(LEFT(last_name, 1)) BETWEEN '$lastFrom' AND '$lastTo'
                    AND type='volunteer'
                  GROUP BY dateCompletedTraining, last_name
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom != NULL && $lastTo != NULL) {
            // both date & name range
            if ($stats != "All") {
                $query = "
                  SELECT first_name, last_name, email,
                         completedTraining, dateCompletedTraining
                  FROM dbPersons
                  WHERE completedTraining='True'
                    AND LOWER(LEFT(last_name, 1)) BETWEEN '$lastFrom' AND '$lastTo'
                    AND dateCompletedTraining BETWEEN '$dateFrom' AND '$dateTo'
                    AND type='volunteer'
                    AND status='$stats'
                  GROUP BY dateCompletedTraining, last_name
                ";
            } else {
                $query = "
                  SELECT first_name, last_name, email,
                         completedTraining, dateCompletedTraining
                  FROM dbPersons
                  WHERE completedTraining='True'
                    AND LOWER(LEFT(last_name, 1)) BETWEEN '$lastFrom' AND '$lastTo'
                    AND dateCompletedTraining BETWEEN '$dateFrom' AND '$dateTo'
                    AND type='volunteer'
                  GROUP BY dateCompletedTraining, last_name
                ";
            }
        }

        $result = mysqli_query($con, $query);

        if (!$result || mysqli_num_rows($result) == 0) {
            echo '<div class="error-toast">No Results Found</div>';
        } else {
            echo "
            <table>
                <tr>
                  <th>First Name</th>
                  <th>Last Name</th>
                  <th>Email</th>
                  <th>Completed Training</th>
                  <th>Date Completed Training</th>
                </tr>
            <tbody>";
            while($row = mysqli_fetch_assoc($result)) {
                $export_array[] = [
                  $row['first_name'],
                  $row['last_name'],
                  $row['email'],
                  $row['completedTraining'],
                  $row['dateCompletedTraining']
                ];
                echo "<tr>
                        <td>{$row['first_name']}</td>
                        <td>{$row['last_name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['completedTraining']}</td>
                        <td>{$row['dateCompletedTraining']}</td>
                      </tr>";
            }
            echo "</tbody></table>";
        }
    }

    if ($type == "top_perform") {
        $con = connect();
        $sum = 0;

        if ($dateFrom == NULL && $dateTo == NULL && $lastFrom == NULL && $lastTo == NULL) {
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate <= '$today'
                    AND dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate <= '$today'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbEventVolunteers.userID
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom != NULL && $lastTo != NULL) {
            // date range + name range
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         DATEDIFF(minute, dbEvents.startTime, dbEvents.endtime) as dur
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate <= '$dateTo'
                    AND dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dur
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         DATEDIFF(minute, dbEvents.startTime, dbEvents.endtime) as dur
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate <= '$dateTo'
                    AND dbPersons.type='volunteer'
                  GROUP BY dur
                ";
            }
        }
        elseif ($dateFrom == NULL && $dateTo == NULL && $lastFrom != NULL && $lastTo != NULL) {
            // only name range
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         DATEDIFF(minute, dbEvents.startTime, dbEvents.endtime) as dur
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate <= '$today'
                    AND dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dur
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         DATEDIFF(minute, dbEvents.startTime, dbEvents.endtime) as dur
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate <= '$today'
                    AND dbPersons.type='volunteer'
                  GROUP BY dur
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom == NULL && $lastTo == NULL) {
            // only date range
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         DATEDIFF(minute, dbEvents.startTime, dbEvents.endtime) as dur
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate <= '$dateTo'
                    AND dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dur
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         DATEDIFF(minute, dbEvents.startTime, dbEvents.endtime) as dur
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate <= '$dateTo'
                    AND dbPersons.type='volunteer'
                  GROUP BY dur
                ";
            }
        }

        $result = mysqli_query($con, $query);
        if (!$result || mysqli_num_rows($result) == 0) {
            echo '<div class="error-toast">No Results Found</div>';
        } else {
            echo "
              <table>
              <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Volunteer Hours</th>
              </tr>
              <tbody>";
            while ($row = mysqli_fetch_assoc($result)) {
                $hours = get_hours_volunteered_by($row['id']);
                echo "<tr>
                        <td>{$row['first_name']}</td>
                        <td>{$row['last_name']}</td>
                        <td>$hours</td>
                      </tr>";
                $totHours[] = $hours;
            }
            // sum total
            $sum = array_sum($totHours);

            echo "
              <tr>
                <td style='border: none;' bgcolor='white'></td>
                <td style='border: none;' bgcolor='white'></td>
                <td bgcolor='white'><label>Total Hours:</label> $sum</td>
              </tr>
            </tbody>
            </table>";
        }
    }

    if ($type == "indiv_vol_hours") {
        $con = connect();

        if ($dateFrom == NULL && $dateTo == NULL && $lastFrom == NULL && $lastTo == NULL) {
            // all date range, all name range for the single user
            $theEventHrs = get_events_attended_by_desc($indivID);
            $totalHrs    = get_hours_volunteered_by($indivID);

            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbEvents.eventName, dbEvents.eventDate, 
                         dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbEventVolunteers.userID = dbPersons.id
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.id='$indivID'
                    AND dbPersons.status='$stats'
                  ORDER BY dbEvents.eventDate DESC
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbEvents.eventName, dbEvents.eventDate, 
                         dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbEventVolunteers.userID = dbPersons.id
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.id='$indivID'
                  ORDER BY dbEvents.eventDate DESC
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom != NULL && $lastTo != NULL) {
            // date range + name range
            $theEventHrs = get_events_attended_by_and_date($indivID, $dateFrom, $dateTo);
            $totalHrs    = get_hours_volunteered_by_and_date($indivID, $dateFrom, $dateTo);

            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbEvents.eventName, dbEvents.eventDate,
                         dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.id ='$indivID'
                    AND dbPersons.status='$stats'
                    AND LOWER(LEFT(dbPersons.last_name,1)) BETWEEN '$lastFrom' AND '$lastTo'
                    AND dbEvents.eventDate BETWEEN '$dateFrom' AND '$dateTo'
                  GROUP BY dbEvents.eventName
                  ORDER BY dbEvents.eventDate DESC
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbEvents.eventName, dbEvents.eventDate,
                         dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.id ='$indivID'
                    AND LOWER(LEFT(dbPersons.last_name, 1)) BETWEEN '$lastFrom' AND '$lastTo'
                    AND dbEvents.eventDate BETWEEN '$dateFrom' AND '$dateTo'
                  GROUP BY dbEvents.eventName
                  ORDER BY dbEvents.eventDate DESC
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom == NULL && $lastTo == NULL) {
            // only date range
            $theEventHrs = get_events_attended_by_and_date($indivID, $dateFrom, $dateTo);
            $totalHrs    = get_hours_volunteered_by_and_date($indivID, $dateFrom, $dateTo);

            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbEvents.eventName, dbEvents.eventDate,
                         dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.id='$indivID'
                    AND dbPersons.status='$stats'
                    AND dbEvents.eventDate BETWEEN '$dateFrom' AND '$dateTo'
                  ORDER BY dbEvents.eventDate DESC
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbEvents.eventName, dbEvents.eventDate,
                         dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.id='$indivID'
                    AND dbEvents.eventDate BETWEEN '$dateFrom' AND '$dateTo'
                  ORDER BY dbEvents.eventDate DESC
                ";
            }
        }
        elseif ($dateFrom == NULL && $dateTo == NULL && $lastFrom != NULL && $lastTo != NULL) {
            // only name range
            $theEventHrs = get_events_attended_by_desc($indivID);
            $totalHrs    = get_hours_volunteered_by($indivID);

            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbEvents.eventName, dbEvents.eventDate,
                         dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.id='$indivID'
                    AND dbPersons.status='$stats'
                    AND LOWER(LEFT(dbPersons.last_name, 1)) BETWEEN '$lastFrom' AND '$lastTo'
                  ORDER BY dbEvents.eventDate DESC
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbEvents.eventName, dbEvents.eventDate,
                         dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.id='$indivID'
                    AND LOWER(LEFT(dbPersons.last_name, 1)) BETWEEN '$lastFrom' AND '$lastTo'
                  ORDER BY dbEvents.eventDate DESC
                ";
            }
        }

        $result = mysqli_query($con, $query);

        if (!$result || mysqli_num_rows($result) == 0) {
            echo '<div class="error-toast">No Results Found</div>';
        } else {
            echo "
            <table>
              <tr>
                <th>Event Name</th>
                <th>Event Date</th>
                <th>Volunteer Hours</th>
              </tr>
              <tbody>";

            foreach ($theEventHrs as $event) {
                $hours = calculateHourDuration($event['startTime'], $event['endTime']);
                echo "<tr>
                        <td>{$event['eventName']}</td>
                        <td>{$event['eventDate']}</td>
                        <td>$hours</td>
                      </tr>";
            }

            echo "
              <tr>
                <td style='border: none;' bgcolor='white'></td>
                <td style='border: none;' bgcolor='white'></td>
                <td bgcolor='white'><label>Total Hours:</label> $totalHrs</td>
              </tr>
            </tbody>
            </table>";
        }
    }

    if ($type == "total_vol_hours") {
        $sum = 0;
        $con=connect();

        if ($dateFrom == NULL && $dateTo == NULL && $lastFrom == NULL && $lastTo == NULL) {
            // No date range or name range
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         dbEvents.eventName, dbEvents.eventDate, dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbEvents.eventDate DESC, dbPersons.last_name, dbPersons.first_name
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         dbEvents.eventName, dbEvents.eventDate, dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbEvents.eventDate DESC, dbPersons.last_name, dbPersons.first_name
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom != NULL && $lastTo != NULL) {
            // Both name & date
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         dbEvents.eventName, dbEvents.eventDate, dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate >= '$dateFrom'
                    AND eventDate <= '$dateTo'
                    AND dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbEvents.eventDate DESC, dbPersons.last_name, dbPersons.first_name
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         dbEvents.eventName, dbEvents.eventDate, dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate >= '$dateFrom'
                    AND eventDate <= '$dateTo'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbEvents.eventDate DESC, dbPersons.last_name, dbPersons.first_name
                ";
            }
        }
        elseif ($dateFrom == NULL && $dateTo == NULL && $lastFrom != NULL && $lastTo != NULL) {
            // Name range only
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         dbEvents.eventName, dbEvents.eventDate, dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbEvents.eventDate DESC, dbPersons.last_name, dbPersons.first_name
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         dbEvents.eventName, dbEvents.eventDate, dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbEvents.eventDate DESC, dbPersons.last_name, dbPersons.first_name
                ";
            }
        }
        elseif ($dateFrom != NULL && $dateTo != NULL && $lastFrom == NULL && $lastTo == NULL) {
            // Date range only
            if ($stats != "All") {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         dbEvents.eventName, dbEvents.eventDate, dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate BETWEEN '$dateFrom' AND '$dateTo'
                    AND dbPersons.status='$stats'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbEvents.eventDate DESC, dbPersons.last_name, dbPersons.first_name
                ";
            } else {
                $query = "
                  SELECT dbPersons.id, dbPersons.first_name, dbPersons.last_name,
                         dbEvents.eventName, dbEvents.eventDate, dbEvents.startTime, dbEvents.endTime
                  FROM dbPersons
                  JOIN dbEventVolunteers ON dbPersons.id = dbEventVolunteers.userID
                  JOIN dbEvents ON dbEventVolunteers.eventID = dbEvents.id
                  WHERE eventDate BETWEEN '$dateFrom' AND '$dateTo'
                    AND dbPersons.type='volunteer'
                  GROUP BY dbPersons.first_name, dbPersons.last_name
                  ORDER BY dbEvents.eventDate DESC, dbPersons.last_name, dbPersons.first_name
                ";
            }
        }

        $result = mysqli_query($con, $query);

        if (!$result || mysqli_num_rows($result) == 0) {
            echo '<div class="error-toast">No Results Found</div>';
        } else {
            echo "
            <table>
              <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Event</th>
                <th>Event Date</th>
                <th>Volunteer Hours</th>
              </tr>
              <tbody>";
            while ($row = mysqli_fetch_assoc($result)) {
                $hours = get_hours_volunteered_by($row['id']);
                echo "<tr>
                        <td>{$row['first_name']}</td>
                        <td>{$row['last_name']}</td>
                        <td>{$row['eventName']}</td>
                        <td>{$row['eventDate']}</td>
                        <td>$hours</td>
                      </tr>";
                // Add to export array
                $export_array[] = [
                  $row['first_name'],
                  $row['last_name'],
                  $row['eventName'],
                  $row['eventDate'],
                  $hours
                ];
            }
            echo "
              <tr>
                <td style='border: none;' bgcolor='white'></td>
                <td style='border: none;' bgcolor='white'></td>
                <td style='border: none;' bgcolor='white'></td>
                <td style='border: none;' bgcolor='white'></td>
                <td bgcolor='white'><label>Total Hours:</label></td>
                <td bgcolor='white'><label>" . get_tot_vol_hours($type, $stats, $dateFrom, $dateTo, $lastFrom, $lastTo) . "</label></td>
              </tr>
            </tbody>
            </table>";
        }
    }

    // ------------------------------------------------------------------------------------------
    // 6) REPORT: Email Volunteer List
    // ------------------------------------------------------------------------------------------
    if ($type == "email_volunteer_list") {
        $con = connect();
        if ($stats != "All") {
            $query = "
              SELECT *
              FROM dbPersons
              WHERE type='volunteer' 
                AND status='$stats'
              ORDER BY last_name, first_name
            ";
        } else {
            $query = "
              SELECT *
              FROM dbPersons
              WHERE type='volunteer'
              ORDER BY last_name, first_name
            ";
        }
        $result = mysqli_query($con, $query);

        if (!$result || mysqli_num_rows($result) == 0) {
            echo '<div class="error-toast">No Results Found</div>';
        } else {
            echo "
            <table>
              <tr><th>Volunteer Emails</th></tr>
              <tbody>";
            while ($row = mysqli_fetch_assoc($result)) {
                $mail = $row['email'];
                echo "<tr>
                        <td><a href='mailto:$mail'>{$row['email']}</a></td>
                      </tr>";
                $export_array[] = [$row['email']];
            }
            echo "</tbody></table>";
        }
    }


    if ($type == "missing_paperwork") {
        $con = connect();

        $query = "";

        if (!empty($dateFrom) && empty($dateTo)) {
        echo "<div class='error-toast'>Please provide an end date.</div>";
       
    }
        // Date range not needed; only name range
        if ($dateFrom == NULL && $dateTo == NULL && $lastFrom == NULL && $lastTo == NULL) {
            // no name filter
            if ($stats != "All") {
                $query = "
                  SELECT *
                  FROM dbPersons
                  WHERE type='volunteer'
                    AND status='$stats'
                    AND completedPaperwork IS NULL
                  ORDER BY last_name, first_name
                ";
            } else {
                $query = "
                  SELECT *
                  FROM dbPersons
                  WHERE type='volunteer'
                    AND completedPaperwork IS NULL
                  ORDER BY last_name, first_name
                ";
            }
        }
        elseif ($dateFrom == NULL && $dateTo == NULL && $lastFrom != NULL && $lastTo != NULL) {
            // only name filter
            if ($stats != "All") {
                $query = "
                  SELECT *
                  FROM dbPersons
                  WHERE type='volunteer'
                    AND status='$stats'
                    AND completedPaperwork IS NULL
                    AND LOWER(LEFT(last_name,1)) BETWEEN '$lastFrom' AND '$lastTo'
                  ORDER BY last_name, first_name
                ";
            } else {
                $query = "
                  SELECT *
                  FROM dbPersons
                  WHERE type='volunteer'
                    AND completedPaperwork IS NULL
                    AND LOWER(LEFT(last_name,1)) BETWEEN '$lastFrom' AND '$lastTo'
                  ORDER BY last_name, first_name
                ";
            }
        }

if (empty($query)) {
        echo "<div class='error-toast'>No Results Found (or invalid filter options).</div>";
    }
    else {
        $result = mysqli_query($con, $query);

        if (!$result || mysqli_num_rows($result) == 0) {
            echo "No Results Found";
        } else {
            echo "
              <table>
                <tr>
                  <th>First Name</th>
                  <th>Last Name</th>
                  <th>Email</th>
                </tr>
              <tbody>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$row['first_name']}</td>
                        <td>{$row['last_name']}</td>
                        <td>{$row['email']}</td>
                      </tr>";
                $export_array[] = [
                  $row['first_name'],
                  $row['last_name'],
                  $row['email']
                ];
            }
            echo "</tbody></table>";
        }
    }
    ?>
    </div>

    <div class="center_b">
        <a href="report.php">
            <button class="theB">New Report</button>
        </a>
        <a href="index.php">
            <button class="theB">Home Page</button>
        </a>
        <?php
        // Show an Export button only for certain report types
        if (
            $type == "general_volunteer_report" ||
            $type == "email_volunteer_list" ||
            $type == "completed_training"    ||
            $type == "total_vol_hours"       ||
            $type == "missing_paperwork"
        ) {
            // Put $type and the data array into SESSION for reportsExport.php
            $_SESSION['type'] = $type;
            $_SESSION['export_array'] = $export_array;
            // Link to the export
            echo "<a href='reportsExport.php'>
                    <button class='theB'>Export Report</button>
                  </a>";
        }
    }
        ?>
    </div>
</main>

<footer>
    <div class="center_b">
        <button class="theB" id="back-to-top-btn">
            <a href="#" class="back-to-top">Back to top</a>
        </button>
    </div>
</footer>
</body>
</html>
