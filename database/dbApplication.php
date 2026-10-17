<?php

require_once('dbinfo.php');

// Find an application by its ID
function retrieve_application($id) {
    $con = connect();

    $sql = "SELECT * FROM dbApplication WHERE id = ?";
    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $application = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $application;
}

// Save a new application to the database
function add_application($id, $applicant_id, $cycle_id, $name, $message) {
    $con = connect();

    $sql = "INSERT INTO dbApplication
            (id, applicant_id, cycle_id, answer1, answer2, submittedAt)
            VALUES (?, ?, ?, ?, ?, CURRENT_DATE)";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $id,
        $applicant_id,
        $cycle_id,
        $name,
        $message
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}


function get_applicant_application($applicant_id, $cycle_id) {
    $con = connect();

    $sql = "SELECT * FROM dbApplication
            WHERE applicant_id = ? AND cycle_id = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $applicant_id, $cycle_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $application = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $application ?: null;
}
