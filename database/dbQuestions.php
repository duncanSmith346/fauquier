<?php

require_once('dbinfo.php');

function addQuestion($question, $type, $form) {
    $number = getNextQuestionNumber($form);    

    $con = connect();

    $sql = "INSERT INTO dbQuestions
            (" . "number" . ", application_id, " . "type" . ", question)
            VALUES (?,?,?, ?)";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "isss",
        $number,
        $form,
        $type,
        $question
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}

function addAnswer($question,$answer,$form) {
    $con = connect();

    $sql = "INSERT INTO dbAnswers
            (" . "number" . ", isSample, answer,application_id)
            VALUES (?,1,?,?)";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $question,
        $answer,
        $form
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}

function removeQuestion($removeNumber,$form) {
    //fist needs to remove answers from the ansers table
    //remove the question from the questions table
    //shift the answers in the answer table down
    //shift the questions in the questions table down
    $maxnumber = getNextQuestionNumber($form);
    $con = connect();

    $sql = "DELETE FROM dbAnswers WHERE " . "number" . "=? AND isSample=1 and application_id=?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt,"is",$removeNumber,$form);
    $success = mysqli_stmt_execute($stmt);

    for ($i = $removeNumber + 1; $i < $maxnumber; $i = $i + 1) {
        $sql = "UPDATE dbAnswers SET " . "number" . "= ? WHERE " . "number" . " = ? and application_id=?";

        $stmt = mysqli_prepare($con, $sql);
        $x = $i-1;
        mysqli_stmt_bind_param($stmt,"iis",$x,$i,$form);
        $success1 = mysqli_stmt_execute($stmt);
    }

    $sql = "DELETE FROM dbQuestions WHERE " . "number" . "=? AND application_id=?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt,"is",$removeNumber,$form);
    $success = mysqli_stmt_execute($stmt);

    for ($i = $removeNumber + 1; $i < $maxnumber; $i = $i + 1) {
        $sql = "UPDATE dbQuestions SET " . "number" . "= ? WHERE " . "number" . " = ? AND application_id=?";

        $stmt = mysqli_prepare($con, $sql);
        $x = $i-1;
        mysqli_stmt_bind_param($stmt,"iis",$x,$i,$form);
        $success = mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $success;
}

function getNextQuestionNumber($form) {
    $con = connect();

    $sql = "SELECT DISTINCT " . "number" . " FROM dbQuestions WHERE application_id=? ORDER BY " . "number" . " DESC LIMIT 1";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt,"s", $form);

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

function getQuestion($application_id, $number) {
    $con = connect();

    $sql = "SELECT * FROM dbQuestions WHERE application_id=? and " . "number" . " = ?";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $application_id,
        $number
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $question = mysqli_fetch_assoc($result);
    

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $question ?: null;
    }

function getAnswers($number,$form) {
    $con = connect();

    $sql = "SELECT * FROM dbAnswers WHERE isSample=1 and " . "number" . " = ? and application_id=?";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $number,
        $form
    );

    mysqli_stmt_execute($stmt);

    $results = mysqli_stmt_get_result($stmt);
    

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $results ?: null;
}
function removeAnswer($question, $answer,$form) {
        $con = connect();

    $sql = "DELETE FROM dbAnswers WHERE " . "number" . "=? AND isSample=1 AND answer= ? and application_id=?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt,"iss",$question,$answer,$form);
    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $success;
}

// Add a committee member's comment
function add_comment($comment, $question_number, $application_id) {
    $con = connect();

    $sql = "UPDATE dbQuestions SET comment = ? WHERE application_id = ? AND " . "number" . " = ? ";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $comment, $application_id, $question_number);

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}