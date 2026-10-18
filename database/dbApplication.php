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

function retrieve_undecided_applicantions_names() {
    $con = connect();

    $sql = "SELECT answer1,dbApplication.id FROM dbApplication INNER JOIN dbPersons ON dbApplication.applicant_id=dbPersons.id
            WHERE decisionStatus='undecided'";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_execute($stmt);

    $results = mysqli_stmt_get_result($stmt);
    

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $results ?: null;
}

function add_comment($comment, $applicant_id) {
    $con = connect();

    $sql = "UPDATE dbapplication SET comments= ? WHERE applicant_id = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $comment, $applicant_id);
 

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}

function update_reviewer($reviewer_id, $application_id) {
    $con = connect();

    $sql = "UPDATE dbapplication SET reviewer_id= ? WHERE id = ?";

    $stmt = mysqli_prepare($con, $sql);
    
    mysqli_stmt_bind_param($stmt, "ss", $reviewer_id, $application_id);
 

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}

