<?php

require_once('dbinfo.php');

function addQuestion($question, $type) {
$number = getNextQuestionNumber();    

$con = connect();

    $sql = "INSERT INTO dbQuestions
            (" . "number" . ", application_id, " . "type" . ", question)
            VALUES (?,'sampleapplicatio',?, ?)";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $number,
        $type,
        $question
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}

function getNextQuestionNumber() {
    $con = connect();

    $sql = "SELECT DISTINCT " . "number" . " FROM dbQuestions ORDER BY " . "number" . " DESC LIMIT 1";

    $stmt = mysqli_prepare($con, $sql);

    $success = mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $number = mysqli_fetch_assoc($result);
    
    if ($number === NULL) {
        $number = 1;
    } else {
        $number = $number['number'] + 1;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $number;
}

function getQuestions($application_id) {
    $con = connect();

    $sql = "SELECT * FROM dbQuestions WHERE application_id=?";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $application_id
    );

    mysqli_stmt_execute($stmt);

    $results = mysqli_stmt_get_result($stmt);
    

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $results ?: null;
}