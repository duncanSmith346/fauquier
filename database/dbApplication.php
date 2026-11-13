
<?php

require_once('dbinfo.php');

define('UPLOAD_DIR', dirname(__DIR__) . '/fileUploads/');
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024);

function upload_allowed_types() {
    return [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'pdf'  => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                   'application/zip'],
    ];
}

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
function retrieve_undecided_applicantions_names($page) {
    $con = connect();

   $sql = "SELECT
            CONCAT(dbPersons.first_name, ' ', dbPersons.last_name) AS answer1,
            dbApplication.id,
            reviewer_id
        FROM dbApplication
        INNER JOIN dbPersons
            ON dbApplication.applicant_id = dbPersons.id
        WHERE decisionStatus = 'undecided'
        LIMIT 10 OFFSET ?";

    $stmt = mysqli_prepare($con, $sql);

    $lines = 10 * $page;

    mysqli_stmt_bind_param($stmt, "i", $lines);
    mysqli_stmt_execute($stmt);

    $results = mysqli_stmt_get_result($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $results ?: null;
}

function get_assigned_applications($reviewer_id,$page) {
    $con = connect();

   $sql = "SELECT
            CONCAT(dbPersons.first_name, ' ', dbPersons.last_name) AS answer1,
            dbApplication.id,
            reviewer_id
        FROM dbApplication
        INNER JOIN dbPersons
            ON dbApplication.applicant_id = dbPersons.id
        WHERE decisionStatus = 'undecided' AND reviewer_id=?
        LIMIT 10 OFFSET ?";

    $stmt = mysqli_prepare($con, $sql);

    $lines = 10*$page;

    mysqli_stmt_bind_param($stmt, "si", $reviewer_id, $lines);
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

//ties an application_id to a cohort.
function create_cohort($application_id) {
    $application = retrieve_application($application_id);

    if (!$application) {
        return false;
    }

    $con = connect();

    // Check whether this application already has a cohort record.
    $sql = "SELECT id FROM dbCohort WHERE Application_id = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $application_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $existing = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($existing) {
       mysqli_close($con);
       return true;
    }

    $cohort_id = bin2hex(random_bytes(16));

    $sql = "INSERT INTO dbCohort
           (id, person_id, Application_id, dateAccepted,
           onboardingAppointment_id, Cycle_id)
           VALUES (?, ?, ?, CURRENT_DATE, NULL, ?)";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $cohort_id,
        $application['applicant_id'],
        $application_id,
        $application['cycle_id']
    );

    $success = mysqli_stmt_execute($stmt);

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
function submit_application_with_answers($id, $applicant_id, $cycle_id, $answers, $files) {
    $con = connect();
    $new_files = [];
    $old_files = [];

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
            if (is_array($answer)) {
                $answer = implode(", ", array_filter($answer, function ($value) {
                return $value !== ''; 
                }));
            }

            $answer = trim((string) $answer);
            
            $question = getQuestion('sampleapplicatio', $number);
            if ($question['type'] === 'file') continue;
            $sql = "INSERT INTO dbQuestions (number, application_id, answer, question, type)
                VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "issss", $number, $id, $answer, $question['question'], $question['type']);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        }

        save_uploaded_files($con, $id, $files, $new_files, $old_files, 'sampleapplicatio');

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
function submit_Scholarship_with_answers($scholarship_id, $application_id, $cycle_id, $answers, $files) {
    $con = connect();
    $new_files = [];
    $old_files = [];

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
            
            if (is_array($answer)) {
                $answer = implode(", ", array_filter($answer, function ($value) {
                return $value !== ''; 
                }));
            }
            $answer = trim((string) $answer);

            $question = getQuestion('samplescholarshi', $number);
            if ($question['type'] === 'file') continue;
            $sql = "INSERT INTO dbQuestions (number, application_id, answer, question, type)
                VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "issss", $number, $scholarship_id, $answer, $question['question'], $question['type']);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        }

        save_uploaded_files($con, $scholarship_id, $files, $new_files, $old_files, 'samplescholarshi');

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
function save_draft_application($applicant_id, $cycle_id, $answers, $files) {
    $con = connect();
    mysqli_begin_transaction($con);
    $new_files = [];
    $old_files = [];

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
            if ($question['type'] === 'file') continue;
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

        save_uploaded_files($con, $id, $files, $new_files, $old_files, 'sampleapplicatio');

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

function update_application_payment($application_id, $status) {
    $con = connect();

    $sql = "UPDATE dbApplication
            SET paymentStatus = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "is", $status, $application_id);

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}

function update_tuition_payment($commitment_id, $status) {
    $con = connect();

    $sql = "UPDATE dbCommitment
            SET tuitionStatus = ?
            WHERE commitment_id = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "is", $status, $commitment_id);

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $success;
}

function Admin_list($page) {
    $con = connect();

    $sql = "SELECT CONCAT(dbPersons.first_name, ' ', dbPersons.last_name) AS fullname, 
            dbApplication.id AS appid, 
            dbCommitment.commitment_id AS commitment, 
            dbCommitment.submittedDate,
            dbApplication.submittedAt,
            dbApplication.decisionStatus AS decisionStatus,
            dbApplication.reviewStatus AS reviewStatus,
            dbApplication.paymentStatus AS applicationFee,
            dbCommitment.tuitionStatus AS tuition
            FROM dbPersons
            LEFT JOIN dbApplication ON dbApplication.applicant_id=dbPersons.id
            LEFT JOIN dbCohort ON dbCohort.person_id=dbPersons.id
            LEFT JOIN dbCommitment ON dbCohort.id=dbCommitment.cohort_id
            WHERE dbPersons.type='applicant' OR dbPersons.type='cohort'
            LIMIT 10 OFFSET ?";
    
    $stmt = mysqli_prepare($con, $sql);
    $lines = 10 * $page;
    mysqli_stmt_bind_param($stmt, "i", $lines);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $answers = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $answers;
}

function get_count_admin_list() {
    $con = connect();

    $sql = "SELECT count(dbapplication.id)
            FROM dbPersons
            LEFT JOIN dbApplication ON dbApplication.applicant_id=dbPersons.id
            LEFT JOIN dbCohort ON dbCohort.person_id=dbPersons.id
            LEFT JOIN dbCommitment ON dbCohort.id=dbCommitment.cohort_id
            WHERE dbPersons.type='applicant' OR dbPersons.type='cohort'";
    
    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $number = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $number;
}

function get_applicant_commitment($person_id, $cycle_id) {
    $con = connect();

    $sql = "SELECT dbCommitment.*
            FROM dbCommitment
            INNER JOIN dbCohort
                ON dbCommitment.cohort_id = dbCohort.id
            WHERE dbCohort.person_id = ?
              AND dbCommitment.cycle_id = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $person_id, $cycle_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $commitment = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $commitment ?: null;
}


// Submit a cohort commitment form and its answers.
function submit_commitment_with_answers($commitment_id, $person_id, $cycle_id, $answers, $files) {
    $con = connect();
    $new_files = [];
    $old_files = [];

    try {
        mysqli_begin_transaction($con);

        // Find this person's cohort record for the current cycle.
        $sql = "SELECT id FROM dbCohort
                WHERE person_id = ? AND Cycle_id = ?
                LIMIT 1";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $person_id, $cycle_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $cohort = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$cohort) {
            throw new Exception("No cohort record found.");
        }

        $cohort_id = $cohort['id'];

        // Create the commitment record.
        $sql = "INSERT INTO dbCommitment
                (commitment_id, cohort_id, commitmentStatus,
                 submittedDate, createdAt, cycle_id)
                VALUES (?, ?, 'submitted', CURRENT_DATE,
                        CURRENT_DATE, ?)";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param(
            $stmt, "sss", $commitment_id, $cohort_id, $cycle_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);

        // Save each answer.
        foreach ($answers as $number => $answer) {
            $number = (int) $number;

            if (is_array($answer)) {
                $answer = implode(", ", array_filter(
                    $answer,
                    static function ($value) {
                        return is_scalar($value)
                            && trim((string) $value) !== '';
                    }
                ));
            }

            $answer = trim((string) $answer);
            $question = getQuestion('samplecommitment', $number);
            if ($question['type'] === 'file') continue;

            if (!$question) {
                throw new Exception("Question not found: " . $number);
            }

            $sql = "INSERT INTO dbQuestions
                    (number, application_id, answer, question, type)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                "issss",
                $number,
                $commitment_id,
                $answer,
                $question['question'],
                $question['type']
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);
        }

        save_uploaded_files($con, $commitment_id, $files, $new_files, $old_files, 'samplecommitment');

        mysqli_commit($con);
        mysqli_close($con);
        return true;

    } catch (Throwable $e) {
        mysqli_rollback($con);
        mysqli_close($con);

        error_log("Commitment submission failed: " . $e->getMessage());
        return false;
    }
}

// Validates and stores one upload. Returns the stored filename, or null if no file was chosen.
// Throws on any problem so the caller's transaction rolls back.
function process_file_upload(array $file) {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload failed with error code " . $file['error']);
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) throw new Exception("File is too large (max 5MB).");
    if (!is_uploaded_file($file['tmp_name'])) throw new Exception("Invalid upload.");

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = upload_allowed_types();
    if (!isset($allowed[$ext])) throw new Exception("Invalid file type: " . $ext);

    // Check the real content, not just the extension.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, $allowed[$ext], true)) throw new Exception("File content does not match type: " . $mime);

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        throw new Exception("Could not create upload directory.");
    }

    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $stored)) {
        throw new Exception("Could not store uploaded file.");
    }
    return $stored;
}

// Inserts or updates the dbQuestions row for a file answer.
// Returns the previously stored filename ('' if none) so the caller can delete it after commit.
function save_file_answer($con, $id, $number, $question, $stored) {
    $stmt = mysqli_prepare($con,
        "SELECT answer FROM dbQuestions WHERE application_id = ? AND number = ?");
    mysqli_stmt_bind_param($stmt, "si", $id, $number);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row) {
        $stmt = mysqli_prepare($con,
            "UPDATE dbQuestions SET answer = ? WHERE application_id = ? AND number = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $stored, $id, $number);
    } else {
        $stmt = mysqli_prepare($con,
            "INSERT INTO dbQuestions (number, application_id, answer, question, type)
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issss", $number, $id, $stored,
            $question['question'], $question['type']);
    }
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($err);
    }
    mysqli_stmt_close($stmt);

    return $row['answer'] ?? '';
}

// Handles every uploaded file for an application. Fills $new_files / $old_files for cleanup.
function save_uploaded_files($con, $id, array $files, array &$new_files, array &$old_files, $form) {
    foreach ($files as $number => $file) {
        $number = (int) $number;
        $question = getQuestion($form, $number);
        if (!$question || $question['type'] !== 'file') {
            throw new Exception("Not a file question: " . $number);
        }

        $stored = process_file_upload($file);
        if ($stored === null) continue;          // nothing chosen: keep any saved file
        $new_files[] = $stored;

        $old = save_file_answer($con, $id, $number, $question, $stored);
        if ($old !== '') $old_files[] = $old;
    }
}