<?php
/*
 * Copyright 2013 by Jerrick Hoang, Ivy Xing, Sam Roberts, James Cook, 
 * Johnny Coster, Judy Yang, Jackson Moniaga, Oliver Radwan, 
 * Maxwell Palmer, Nolan McNair, Taylor Talmage, and Allen Tucker. 
 * This program is part of RMH Homebase, which is free software.  It comes with 
 * absolutely no warranty. You can redistribute and/or modify it under the terms 
 * of the GNU General Public License as published by the Free Software Foundation
 * (see <http://www.gnu.org/licenses/ for more information).
 * 
 */

/**
 * @version March 1, 2012
 * @author Oliver Radwan and Allen Tucker
 */

/* 
 * Created for Gwyneth's Gift in 2022 using original Homebase code as a guide
 */


include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/Event.php');

/*
 * add an event to dbEvents table: if already there, return false
 */


function add_event($event) {
    if (!$event instanceof Event)
        die("Error: add_event type mismatch");
    $con=connect();
    $query = "SELECT * FROM dbEvents WHERE id = '" . $event->get_id() . "'";
    $result = mysqli_query($con,$query);
    //if there's no entry for this id, add it
    if ($result == null || mysqli_num_rows($result) == 0) {
        mysqli_query($con,'INSERT INTO dbEvents VALUES("' .
                $event->get_id() . '","' .
                $event->get_event_date() . '","' .
                $event->get_venue() . '","' .
                $event->get_event_name() . '","' . 
                $event->get_description() . '","' .
                $event->get_event_id() .            
                '");');							
        mysqli_close($con);
        return true;
    }
    mysqli_close($con);
    return false;
}

/*
 * remove an event from dbEvents table.  If already there, return false
 */

function remove_event($id) {
    $con=connect();
    $query = 'SELECT * FROM dbEvents WHERE id = "' . $id . '"';
    $result = mysqli_query($con,$query);
    if ($result == null || mysqli_num_rows($result) == 0) {
        mysqli_close($con);
        return false;
    }
    $query = 'DELETE FROM dbCourses WHERE id = "' . $id . '"';
    $result = mysqli_query($con,$query);
    mysqli_close($con);
    return true;
}


/*
 * @return an Event from dbEvents table matching a particular id.
 * if not in table, return false
 */

function retrieve_event($id) {
    $con = connect();
    $query = "SELECT * FROM dbEvents WHERE id = '" . mysqli_real_escape_string($con, $id) . "'";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) !== 1) {
        mysqli_close($con);
        return NULL;
    }
    $event = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $event;
}


// not in use, may be useful for future iterations in changing how events are edited (i.e. change the remove and create new event process)
function update_event_date($id, $new_event_date) {
	$con=connect();
	$query = 'UPDATE dbEvents SET eventdate = "' . $new_event_date . '" WHERE id = "' . $id . '"';
	$result = mysqli_query($con,$query);
	mysqli_close($con);
	return $result;
}

function get_all_events() {
    $connection = connect();
    $query = "
        select * from dbEvents
    ";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        return null;
    }
    $all = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($connection);
    return $all;
}

// update event volunteer list
function update_event_volunteer_list($eventID, $volunteerID) {
	$con=connect();
	$check = 'SELECT * FROM dbEventVolunteers WHERE eventID = "'.$eventID.'" AND userID = "'.$volunteerID.'" ';
	$result = mysqli_query($con, $check);
  $result_check = mysqli_fetch_assoc($result);
	if ($result_check > 0) {
			return 0;
	}
	$query = 'INSERT INTO dbEventVolunteers (eventID, userID) VALUES ("'.$eventID.'", "'.$volunteerID.'")';
	$result = mysqli_query($con, $query);
	mysqli_close($con);
	return $result;
}

function remove_volunteer_from_event($eventID, $volunteerID){
	$con = connect();
	$query = 'DELETE FROM dbEventVolunteers WHERE eventID = "'.$eventID.'" AND userID = "'.$volunteerID.'" ';
	$result = mysqli_query($con, $query);
	mysqli_close($con);
	return $result;
}


function make_an_event($result_row) {
	/*
	 ($en, $v, $sd, $description, $ev))
	 */
    $theEvent = new Event(
                    $result_row['eventname'],
                    $result_row['event_id']);  
    return $theEvent;
}


// retrieve only those events that match the criteria given in the arguments
function getonlythose_dbEvents($name, $day, $venue) {
   $con=connect();
   $query = "SELECT * FROM dbEvents WHERE event_name LIKE '%" . $name . "%'" .
           " AND event_name LIKE '%" . $name . "%'" .
           " AND venue = '" . $venue . "'" . 
           " ORDER BY event_name";
   $result = mysqli_query($con,$query);
   $theEvents = array();
   while ($result_row = mysqli_fetch_assoc($result)) {
       $theEvent = make_an_event($result_row);
       $theEvents[] = $theEvent;
   }
   mysqli_close($con);
   return $theEvents;
}

function fetch_event_by_id($id) {
    $connection = connect();
    $id = mysqli_real_escape_string($connection, $id);
    $query = "select * from dbEvents where id = '$id'";
    $result = mysqli_query($connection, $query);
    $event = mysqli_fetch_assoc($result);
    if ($event) {
        require_once('include/output.php');
        $event = hsc($event);
        mysqli_close($connection);
        return $event;
    }
    mysqli_close($connection);
    return null;
}


function create_event($eventname, $eventdate, $starttime, $endtime, $trainer, $description, $location, $capacity) 
{
    $connection = connect();

    $eventName  = mysqli_real_escape_string($connection, $eventname);
    $eventdate       = mysqli_real_escape_string($connection, $eventdate);
    $startTime  = mysqli_real_escape_string($connection, $starttime);
    $endTime    = mysqli_real_escape_string($connection, $endtime);
    $trainer    = mysqli_real_escape_string($connection, $trainer);
    $description= mysqli_real_escape_string($connection, $description);
    $location   = mysqli_real_escape_string($connection, $location);
    $capacity   = (int)$capacity; // Cast to integer just to be safe

    $query = "
    INSERT INTO dbEvents 
    (eventdate, starttime, endtime, eventname, description, location, capacity, trainer)
    VALUES 
    ('$eventdate', '$starttime', '$endtime', '$eventName', '$description', '$location', $capacity, '$trainer')
";


    $result = mysqli_query($connection, $query);
   if (!$result) {
    echo "MySQL error: " . mysqli_error($connection);
    mysqli_close($connection);
    return null;
}


    $id = mysqli_insert_id($connection);  
    mysqli_commit($connection);
    mysqli_close($connection);

    return $id;
}

function update_event($id, $eventname, $eventdate, $starttime, $endtime, 
                      $description, $location, $capacity, $trainer) {

    $connection = connect();
    $eventname  = mysqli_real_escape_string($connection, $eventname);
    $eventdate  = mysqli_real_escape_string($connection, $eventdate);
    $starttime  = mysqli_real_escape_string($connection, $starttime);
    $endtime    = mysqli_real_escape_string($connection, $endtime);
    $description= mysqli_real_escape_string($connection, $description);
    $location   = mysqli_real_escape_string($connection, $location);
    $capacity   = (int)$capacity;
    $trainer    = mysqli_real_escape_string($connection, $trainer);

    $query = "
        UPDATE dbEvents
        SET eventname  = '$eventname',
            eventdate  = '$eventdate',
            starttime  = '$starttime',
            endtime    = '$endtime',
            description= '$description',
            location   = '$location',
            capacity   = $capacity,
            trainer    = '$trainer'
        WHERE id = '$id'
    ";

    $result = mysqli_query($connection, $query);

    if (!$result) {
        echo 'MySQL error: ' . mysqli_error($connection);
        // Optionally handle or return false
    }

    mysqli_commit($connection);
    mysqli_close($connection);
    return $result;
}


function find_event($nameLike) {
    $connection = connect();
    $query = "
        select * from dbEvents
        where eventname like '%$nameLike%'
    ";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        return null;
    }
    $all = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($connection);
    return $all;
}

function delete_event($id) {
    $query = "delete from dbEvents where id='$id'";
    $connection = connect();
    $result = mysqli_query($connection, $query);
    $result = boolval($result);
    mysqli_close($connection);
    return $result;
}

function fetch_events_in_date_range_as_array($dateFrom, $dateTo) {
    $connection = connect();
    $query = "SELECT * FROM dbEvents
    WHERE eventDate BETWEEN '$dateFrom' AND '$dateTo'";
    $result = mysqli_query($connection, $query);
    if (!$result) 
    {
        return null;
    }
    $all = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($connection);
    return $all;
}

function fetch_events_on_date($date) {
    $connection = connect();
    $query = "SELECT * FROM dbEvents
    WHERE eventDate='$date'";
    $result = mysqli_query($connection, $query);
    if (!$result) 
    {
        return null;
    }
    $all = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($connection);
    return $all;
}
