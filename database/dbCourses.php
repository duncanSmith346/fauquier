<?php
/*
 * dbCourses.php
 * 
 * Functions for creating, reading, updating, deleting courses
 * and handling course signups (both pending and direct).
 */

include_once('dbinfo.php');

/**
 * Domain class: Course
 * Adjust fields and constructor parameters to match your actual dbCourses schema.
 */
class Course {
    private $id;
    private $name;
    private $date;
    private $startTime;
    private $endTime;
    private $description;
    private $location;
    private $capacity;
    // optional arrays
    private $volunteers;
    private $trainingMedia;
    private $postMedia;

    public function __construct(
        $id,
        $name,
        $date,
        $startTime,
        $endTime,
        $description,
        $location,
        $capacity,
        $volunteers = [],
        $trainingMedia = [],
        $postMedia = []
    ) {
        $this->id            = $id;
        $this->name          = $name;
        $this->date          = $date;
        $this->startTime     = $startTime;
        $this->endTime       = $endTime;
        $this->description   = $description;
        $this->location      = $location;
        $this->capacity      = $capacity;
        $this->volunteers    = $volunteers;
        $this->trainingMedia = $trainingMedia;
        $this->postMedia     = $postMedia;
    }

    // GETTERS
    public function getID()         { return $this->id; }
    public function getName()       { return $this->name; }
    public function getDate()       { return $this->date; }
    public function getStartTime()  { return $this->startTime; }
    public function getEndTime()    { return $this->endTime; }
    public function getDescription(){ return $this->description; }
    public function getLocation()   { return $this->location; }
    public function getCapacity()   { return $this->capacity; }
    public function getVolunteers() { return $this->volunteers; }
    public function getTrainingMedia() { return $this->trainingMedia; }
    public function getPostMedia()  { return $this->postMedia; }
}

function create_course($courseData) {
    $connection = connect();
    $name        = mysqli_real_escape_string($connection, $courseData['name']);
    $date        = mysqli_real_escape_string($connection, $courseData['date']);
    $startTime   = mysqli_real_escape_string($connection, $courseData['startTime']);
    $endTime     = mysqli_real_escape_string($connection, $courseData['endTime']);
    $description = mysqli_real_escape_string($connection, $courseData['description']);
    $location    = mysqli_real_escape_string($connection, $courseData['location']);
    $capacity    = (int)$courseData['capacity'];

    $query = "
        INSERT INTO dbCourses 
        (name, date, startTime, endTime, description, location, capacity)
        VALUES 
        ('$name', '$date', '$startTime', '$endTime', '$description', '$location', $capacity)
    ";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return null;
    }
    $id = mysqli_insert_id($connection);
    mysqli_commit($connection);
    mysqli_close($connection);
    return $id;
}

/**
 * Retrieve a course by its ID from dbCourses.
 * Return an associative array or false if not found.
 */
function retrieve_course($id) {
    $con = connect();
    $id = mysqli_real_escape_string($con, $id);
    $query = "SELECT * FROM dbCourses WHERE id = '$id'";
    $result = mysqli_query($con, $query);
    if (!$result || mysqli_num_rows($result) !== 1) {
        mysqli_close($con);
        return false;
    }
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $row; // or transform into a Course object if desired
}

/**
 * Update an existing course row by ID.
 * Return true on success, false on failure.
 */
function update_course($id, $courseData) {
    $con = connect();
    $id          = mysqli_real_escape_string($con, $id);
    $name        = mysqli_real_escape_string($con, $courseData['name']);
    $date        = mysqli_real_escape_string($con, $courseData['date']);
    $startTime   = mysqli_real_escape_string($con, $courseData['startTime']);
    $endTime     = mysqli_real_escape_string($con, $courseData['endTime']);
    $description = mysqli_real_escape_string($con, $courseData['description']);
    $location    = mysqli_real_escape_string($con, $courseData['location']);
    $capacity    = (int)$courseData['capacity'];

    $query = "
       UPDATE dbCourses
       SET name='$name',
           date='$date',
           startTime='$startTime',
           endTime='$endTime',
           description='$description',
           location='$location',
           capacity=$capacity
       WHERE id='$id'
    ";
    $result = mysqli_query($con, $query);
    mysqli_commit($con);
    mysqli_close($con);
    return ($result) ? true : false;
}

/**
 * Delete a course by ID.
 * Return true if successfully removed, false otherwise.
 */
function remove_course($id) {
    $con = connect();
    $id = mysqli_real_escape_string($con, $id);
    $checkQuery = "SELECT id FROM dbCourses WHERE id='$id'";
    $checkResult = mysqli_query($con, $checkQuery);
    if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
        mysqli_close($con);
        return false;
    }
    $query = "DELETE FROM dbCourses WHERE id='$id'";
    $result = mysqli_query($con, $query);
    mysqli_close($con);
    return ($result) ? true : false;
}

/* ------------------------------------------------------------------------
   COURSE SIGN-UP FUNCTIONS
   ------------------------------------------------------------------------ */

/**
 * Check if a user is already fully signed up for a course.
 * Return true/false.
 */
function check_if_signed_up($courseID, $userID) {
    $connection = connect();
    $courseID = mysqli_real_escape_string($connection, $courseID);
    $userID   = mysqli_real_escape_string($connection, $userID);

    $query = "
        SELECT userID 
        FROM dbcoursepersons
        WHERE courseID = '$courseID'
          AND userID   = '$userID'
    ";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($connection);

    return ($row !== null);
}

/**
 * Request a restricted course signup => place into dbpendingcoursesignups.
 * Admin later approves or rejects.
 * Return the courseID if successful, or null if already signed/pending or error.
 */
function request_course_signup($courseName, $account_name, $role, $notes) {
    $connection = connect();
    // 1) find course ID by name
    $query1 = "SELECT id FROM dbCourses WHERE name LIKE '$courseName'";
    $result1 = mysqli_query($connection, $query1);
    $row = mysqli_fetch_assoc($result1);
    if (!$row) {
        mysqli_close($connection);
        return null; // no matching course name
    }
    $courseID = $row['id'];

    // 2) check if user in dbcoursepersons (already signed up)
    $query2 = "
       SELECT userID 
       FROM dbcoursepersons
       WHERE courseID = '$courseID'
         AND userID   = '$account_name'
    ";
    $result2 = mysqli_query($connection, $query2);
    $row2 = mysqli_fetch_assoc($result2);

    // 3) check if user is in dbpendingcoursesignups (already pending)
    $query3 = "
       SELECT username 
       FROM dbpendingcoursesignups
       WHERE coursename = '$courseID'
         AND username   = '$account_name'
    ";
    $result3 = mysqli_query($connection, $query3);
    $row3 = mysqli_fetch_assoc($result3);

    // If found in either table, they're already signed or pending
    if ($row2 || $row3) {
        mysqli_close($connection);
        return null;
    }

    // Otherwise, insert pending signup
    $query = "
      INSERT INTO dbpendingcoursesignups
      (username, coursename, role, notes)
      VALUES
      ('$account_name', '$courseID', '$role', '$notes')
    ";
    $insert = mysqli_query($connection, $query);
    mysqli_commit($connection);
    mysqli_close($connection);

    return ($insert) ? $courseID : null;
}

/**
 * Direct (unrestricted) signup => immediately placed into dbcoursepersons.
 * Return the courseID if successful, or null if already signed up or no match.
 */
function sign_up_for_course($courseName, $account_name, $role, $notes) {
    $connection = connect();

    // 1) find course ID by name
    $query1 = "SELECT id FROM dbCourses WHERE name LIKE '$courseName'";
    $result1 = mysqli_query($connection, $query1);
    $row = mysqli_fetch_assoc($result1);
    if (!$row) {
        mysqli_close($connection);
        return null; // no matching course
    }
    $courseID = $row['id'];

    // 2) check if user is already in dbcoursepersons
    $query2 = "
      SELECT userID
      FROM dbcoursepersons
      WHERE courseID = '$courseID'
        AND userID   = '$account_name'
    ";
    $result2 = mysqli_query($connection, $query2);
    $row2 = mysqli_fetch_assoc($result2);
    if ($row2) {
        mysqli_close($connection);
        return null; // already signed up
    }

    // otherwise, insert
    $query = "
      INSERT INTO dbcoursepersons
      (courseID, userID, position, notes)
      VALUES
      ('$courseID', '$account_name', '$role', '$notes')
    ";
    $insert = mysqli_query($connection, $query);
    mysqli_commit($connection);
    mysqli_close($connection);

    return ($insert) ? $courseID : null;
}

/**
 * Approve a pending signup => remove from dbpendingcoursesignups,
 * add them to dbcoursepersons.
 * Return true if success, false if error.
 */
function approve_signup($courseID, $account_name, $position, $notes) {
    $connection = connect();

    // Remove from pending
    $queryDel = "
      DELETE FROM dbpendingcoursesignups
      WHERE coursename = '$courseID'
        AND username   = '$account_name'
    ";
    $delResult = mysqli_query($connection, $queryDel);

    // Insert into dbcoursepersons
    $queryAdd = "
      INSERT INTO dbcoursepersons
      (courseID, userID, position, notes)
      VALUES
      ('$courseID', '$account_name', '$position', '$notes')
    ";
    $addResult = mysqli_query($connection, $queryAdd);

    mysqli_commit($connection);
    mysqli_close($connection);

    return ($delResult && $addResult);
}

/**
 * Reject a pending signup => remove from dbpendingcoursesignups.
 * Return true if success, false otherwise.
 */
function reject_signup($courseID, $account_name) {
    $connection = connect();
    $query = "
      DELETE FROM dbpendingcoursesignups
      WHERE coursename = '$courseID'
        AND username   = '$account_name'
    ";
    $result = mysqli_query($connection, $query);
    mysqli_close($connection);
    return (bool)$result;
}

/**
 * Fetch all signed up users for a specific course.
 */
function fetch_course_signups($courseID) {
    $connection = connect();
    $courseID = mysqli_real_escape_string($connection, $courseID);
    $query = "
      SELECT userID, position, notes
      FROM dbcoursepersons
      WHERE courseID = '$courseID'
    ";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return [];
    }
    $signups = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($connection);
    return $signups;
}

/**
 * Fetch all pending signups for a specific course.
 */
function fetch_pending_for_course($courseID) {
    $connection = connect();
    $courseID = mysqli_real_escape_string($connection, $courseID);
    $query = "
       SELECT username, role, notes
       FROM dbpendingcoursesignups
       WHERE coursename = '$courseID'
    ";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return [];
    }
    $signups = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($connection);
    return $signups;
}

/**
 * Remove (cancel) a user from a course if they've already signed up.
 */
function remove_user_from_course($courseID, $userID) {
    $connection = connect();
    $courseID = mysqli_real_escape_string($connection, $courseID);
    $userID   = mysqli_real_escape_string($connection, $userID);
    $query = "
      DELETE FROM dbcoursepersons
      WHERE courseID = '$courseID'
        AND userID   = '$userID'
    ";
    $result = mysqli_query($connection, $query);
    mysqli_close($connection);
    return (bool)$result;
}

/**
 * Remove (cancel) a user from the *pending* signup list.
 */
function remove_user_from_pending_course($courseID, $userID) {
    $connection = connect();
    $courseID = mysqli_real_escape_string($connection, $courseID);
    $userID   = mysqli_real_escape_string($connection, $userID);
    $query = "
      DELETE FROM dbpendingcoursesignups
      WHERE coursename = '$courseID'
        AND username   = '$userID'
    ";
    $result = mysqli_query($connection, $query);
    mysqli_close($connection);
    return (bool)$result;
}
function fetch_all_courses() {
    $connection = connect();

    $query = "SELECT * FROM dbCourses ORDER BY name";

    $result = mysqli_query($connection, $query);

    if (!$result) {
        mysqli_close($connection);
        return [];
    }

    $courses = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_close($connection);

    return $courses;
}
?>
