<?php
/*
 * dbcoursesignup.php
 * 
 * This file contains functions for managing course sign‐ups in the Empowerhouse VMS system.
 * 
 * Table structure (dbcoursesignup):
 *   - signup_id     (int, primary, auto_increment)
 *   - course_id     (int, not null)
 *   - person_id     (varchar(50), not null)
 *   - signup_date   (datetime, default CURRENT_TIMESTAMP)
 *   - status        (varchar(50), default 'active')
 *
 * This program is free software; you can redistribute and/or modify it under the terms of the GNU General Public License.
 */

include_once('dbinfo.php');

/**
 * Adds a new course signup record.
 *
 * @param int    $course_id The ID of the course.
 * @param string $person_id The ID of the person signing up.
 * @param string $status    (Optional) The status of the signup. Defaults to 'active'.
 *
 * @return mixed Returns the new signup_id on success or false if the signup already exists or an error occurred.
 */
function add_course_signup($course_id, $person_id, $status = 'active') {
    $con = connect();
    $course_id = intval($course_id);
    $person_id = mysqli_real_escape_string($con, $person_id);
    $status = mysqli_real_escape_string($con, $status);
    
    // Check if the person is already signed up for this course
    $checkQuery = "SELECT signup_id FROM dbCourseSignup WHERE course_id = $course_id AND person_id = '$person_id'";
    $checkResult = mysqli_query($con, $checkQuery);
    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        mysqli_close($con);
        return false; // Already signed up
    }
    
    $query = "INSERT INTO dbCourseSignup (course_id, person_id, status) VALUES ($course_id, '$person_id', '$status')";
    $result = mysqli_query($con, $query);
    if (!$result) {
        mysqli_close($con);
        return false;
    }
    $signup_id = mysqli_insert_id($con);
    mysqli_close($con);
    return $signup_id;
}

/**
 * Retrieves a course signup record by its signup ID.
 *
 * @param int $signup_id The signup ID.
 *
 * @return array|false Returns an associative array with the record data or false if not found.
 */
function retrieve_course_signup($signup_id) {
    $con = connect();
    $signup_id = intval($signup_id);
    $query = "SELECT * FROM dbCourseSignup WHERE signup_id = $signup_id";
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        mysqli_close($con);
        return $row;
    }
    mysqli_close($con);
    return false;
}

/**
 * Retrieves all course signup records.
 *
 * @return array Returns an array of all signup records.
 */
function get_all_course_signups() {
    $con = connect();
    $query = "SELECT * FROM dbCourseSignup ORDER BY signup_date DESC";
    $result = mysqli_query($con, $query);
    $signups = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $signups[] = $row;
        }
    }
    mysqli_close($con);
    return $signups;
}

/**
 * Retrieves all signup records for a specific course.
 *
 * @param int $course_id The course ID.
 *
 * @return array Returns an array of signup records for the course.
 */
function get_course_signups_by_course($course_id) {
    $con = connect();
    $course_id = intval($course_id);
    $query = "SELECT * FROM dbCourseSignup WHERE course_id = $course_id ORDER BY signup_date DESC";
    $result = mysqli_query($con, $query);
    $signups = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $signups[] = $row;
        }
    }
    mysqli_close($con);
    return $signups;
}

/**
 * Retrieves all signup records for a specific person.
 *
 * @param string $person_id The person's ID.
 *
 * @return array Returns an array of signup records for the person.
 */
function get_course_signups_by_person($person_id) {
    $con = connect();
    $person_id = mysqli_real_escape_string($con, $person_id);
    $query = "SELECT * FROM dbCourseSignup WHERE person_id = '$person_id' ORDER BY signup_date DESC";
    $result = mysqli_query($con, $query);
    $signups = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $signups[] = $row;
        }
    }
    mysqli_close($con);
    return $signups;
}

/**
 * Updates the status of a course signup record.
 *
 * @param int    $signup_id  The signup ID.
 * @param string $new_status The new status to set.
 *
 * @return bool True if the update was successful; false otherwise.
 */
function update_course_signup_status($signup_id, $new_status) {
    $con = connect();
    $signup_id = intval($signup_id);
    $new_status = mysqli_real_escape_string($con, $new_status);
    $query = "UPDATE dbCourseSignup SET status = '$new_status' WHERE signup_id = $signup_id";
    $result = mysqli_query($con, $query);
    mysqli_close($con);
    return $result;
}

/**
 * Removes a course signup record.
 *
 * @param int $signup_id The signup ID.
 *
 * @return bool True if the deletion was successful; false otherwise.
 */
function remove_course_signup($signup_id) {
    $con = connect();
    $signup_id = intval($signup_id);
    $query = "DELETE FROM dbCourseSignup WHERE signup_id = $signup_id";
    $result = mysqli_query($con, $query);
    mysqli_close($con);
    return $result;
}
?> 