
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

// Find an existing application for an applicant and cycle
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

function get_applicant_scholarship($application_id, $cycle_id) {
    $con = connect();

    $sql = "SELECT * FROM dbscholarships
            WHERE application_id = ? AND cycle_id = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $application_id, $cycle_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $application = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $application ?: null;
}

// Retrieve undecided applications and their assigned reviewers
function retrieve_undecided_applicantions_names() {
    $con = connect();

   $sql = "SELECT
            CONCAT(dbPersons.first_name, ' ', dbPersons.last_name) AS answer1,
            dbApplication.id,
            reviewer_id
        FROM dbApplication
        INNER JOIN dbPersons
            ON dbApplication.applicant_id = dbPersons.id
        WHERE decisionStatus = 'undecided'";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_execute($stmt);

    $results = mysqli_stmt_get_result($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $results ?: null;
}

// Assign a reviewer to an application
function update_reviewer($reviewer_id, $application_id) {
    $con = connect();

    $sql = "UPDATE dbapplication SET reviewer_id = ? WHERE id = ?";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param($stmt, "ss", $reviewer_id, $application_id);

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}

// Accept or reject an application
function update_choice($choice, $application_id) {
    $con = connect();

    $sql = "UPDATE dbapplication SET decisionStatus = ? WHERE id = ?";

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($con));
    }

    mysqli_stmt_bind_param($stmt, "ss", $choice, $application_id);

    if (!mysqli_stmt_execute($stmt)) {
        die("Execute failed: " . mysqli_stmt_error($stmt));
    }

    $success = mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}

// Save an applicant's answers to dynamic questions
function save_application_answers($application_id, $answers) {
    $con = connect();


    foreach ($answers as $number => $answer) {
        $number = (int) $number;
        $answer = trim($answer);
        $question = getQuestion('sampleapplicatio', $number);
        $sql = "INSERT INTO dbQuestions (number, application_id, answer, question, type)
            VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "issss", $number, $application_id, $answer, $question['question'], $question['type']);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            mysqli_close($con);
            return false;
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return true;
}


 // Save the application and all its answers together
function submit_application_with_answers($id, $applicant_id, $cycle_id, $answers) {
    $con = connect();

    mysqli_begin_transaction($con);

    try {
        // Create the application record
        $sql = "INSERT INTO dbApplication
                (id, applicant_id, cycle_id, answer1, answer2, submittedAt)
                VALUES (?, ?, ?, '', '', CURRENT_DATE)";

        $stmt = mysqli_prepare($con, $sql);

        if (!$stmt) {
            throw new Exception(mysqli_error($con));
        }

        mysqli_stmt_bind_param($stmt, "sss", $id, $applicant_id, $cycle_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
        
        // Save each answer using the same connection
        if (!$stmt) {
            throw new Exception(mysqli_error($con));
        }

        foreach ($answers as $number => $answer) {
            $number = (int) $number;
            $answer = trim($answer);
            $question = getQuestion('sampleapplicatio', $number);
            $sql = "INSERT INTO dbQuestions (number, application_id, answer, question, type)
                VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "issss", $number, $id, $answer, $question['question'], $question['type']);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        }

        mysqli_stmt_close($stmt);

        mysqli_commit($con);
        mysqli_close($con);

        return true;

    } catch (Throwable $e) {
        mysqli_rollback($con);
        mysqli_close($con);

        error_log("Application submission failed: " . $e->getMessage());
        return false;
    }
}
function submit_Scholarship_with_answers($scholarship_id, $application_id, $cycle_id, $answers) {
    $con = connect();

    mysqli_begin_transaction($con);

    try {
        // Create the application record
        $sql = "INSERT INTO dbScholarships
                (scholarship_id, application_id, cycle_id, submittedDate, scholarshipStatus)
                VALUES (?, ?, ?, CURRENT_DATE, 'undecided')";

        $stmt = mysqli_prepare($con, $sql);

        if (!$stmt) {
            throw new Exception(mysqli_error($con));
        }

        mysqli_stmt_bind_param($stmt, "sss", $scholarship_id, $application_id, $cycle_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
        
        // Save each answer using the same connection
        if (!$stmt) {
            throw new Exception(mysqli_error($con));
        }

        foreach ($answers as $number => $answer) {
            $number = (int) $number;
            $answer = trim($answer);
            $question = getQuestion('samplescholarshi', $number);
            $sql = "INSERT INTO dbQuestions (number, application_id, answer, question, type)
                VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "issss", $number, $scholarship_id, $answer, $question['question'], $question['type']);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        }

        mysqli_stmt_close($stmt);

        mysqli_commit($con);
        mysqli_close($con);

        return true;

    } catch (Throwable $e) {
        mysqli_rollback($con);
        mysqli_close($con);

        error_log("Scholarship submission failed: " . $e->getMessage());
        return false;
    }
}


// Retrieve all submitted answers for one application
function get_application_answers($application_id) {
    $con = connect();

    $sql = "SELECT number, answer
            FROM dbAnswers
            WHERE application_id = ?
              AND (isSample = 0 OR isSample IS NULL)
            ORDER BY number";     

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $application_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $answers = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $answers;
}

// Save an application without submitting it
function save_draft_application($applicant_id, $cycle_id, $answers) {
    $con = connect();
    mysqli_begin_transaction($con);

    try {
        // Check whether the applicant already has an application
        $sql = "SELECT id, submittedAt
                FROM dbApplication
                WHERE applicant_id = ? AND cycle_id = ?
                FOR UPDATE";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $applicant_id, $cycle_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $application = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($application) {
            // Prevent changes after final submission
            if ($application['submittedAt'] !== null) {
                throw new Exception("Application already submitted.");
            }

            // Reuse the existing application ID
            $id = $application['id'];

            // Update the last saved date
            $sql = "UPDATE dbApplication
                    SET applicationSaved = CURRENT_DATE
                    WHERE id = ?";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "s", $id);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);

        } else {
            // Create a new draft application
            $id = bin2hex(random_bytes(16));

            $sql = "INSERT INTO dbApplication
                    (id, applicant_id, cycle_id, applicationSaved)
                    VALUES (?, ?, ?, CURRENT_DATE)";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $id,
                $applicant_id,
                $cycle_id
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);
        }

        // Save every answer, including empty answers
        foreach ($answers as $number => $answer) {
            $number = (int) $number;

            // Convert the answer to a string
            if (is_array($answer)) {
                $answer = implode(", ", $answer);
            }

            $answer = trim((string) $answer);

            // Retrieve the original question
            $question = getQuestion('sampleapplicatio', $number);

            if (!$question) {
                throw new Exception("Question not found: " . $number);
            }

            // Check whether this answer already exists
            $sql = "SELECT number
                    FROM dbQuestions
                    WHERE application_id = ? AND number = ?";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "si", $id, $number);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $existing_answer = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($existing_answer) {
                // Update the existing answer
                // Do not change the committee's comment
                $sql = "UPDATE dbQuestions
                        SET answer = ?
                        WHERE application_id = ? AND number = ?";

                $stmt = mysqli_prepare($con, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssi",
                    $answer,
                    $id,
                    $number
                );

            } else {
                // Insert an answer that has not been saved before
                $sql = "INSERT INTO dbQuestions
                        (number, application_id, answer, question, type)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($con, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    "issss",
                    $number,
                    $id,
                    $answer,
                    $question['question'],
                    $question['type']
                );
            }

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);
        }

        // Commit all changes together
        mysqli_commit($con);
        mysqli_close($con);

        return true;

    } catch (Throwable $e) {
        // Undo changes if anything fails
        mysqli_rollback($con);
        mysqli_close($con);

        error_log("Draft save failed: " . $e->getMessage());

        return false;
    }
}

// Mark an existing saved draft as submitted
function submit_saved_draft($application_id) {
    $con = connect();

    $sql = "UPDATE dbApplication
            SET submittedAt = CURRENT_DATE
            WHERE id = ?
            AND submittedAt IS NULL";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $application_id);

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}
