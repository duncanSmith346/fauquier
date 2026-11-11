<?php
    // Author: Lauren Knight
    // Description: Registration page for new volunteers
    // Edited for LFQ by Alexander Lech
    session_cache_expire(30);
    session_start();
    
    require_once('include/input-validation.php');

    $loggedIn = false;
    if (isset($_SESSION['change-password'])) {
        header('Location: changePassword.php');
        die();
    }
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
    }

    // if (isset($_SESSION['_id'])) {
    //     header('Location: index.php');
    // } else {
    //     $_SESSION['logged_in'] = 1;
    //     $_SESSION['access_level'] = 0;
    //     $_SESSION['venue'] = "";
    //     $_SESSION['type'] = "";
    //     $_SESSION['_id'] = "guest";
    //     header('Location: personEdit.php?id=new');
    // }
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Register - Leadership Fauquier <?php if ($loggedIn) echo ' New Cohort' ?></title>
</head>
<body>
    <?php
        require_once('header.php');
        require_once('domain/Person.php');
        require_once('database/dbPersons.php');
        require_once('database/dbMessages.php');
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // make every submitted field SQL-safe except for password
            $ignoreList = array('password', 'password-reenter');
            $args = sanitize($_POST, $ignoreList);

            // echo "<p>The form was submitted:</p>";
            // foreach ($args as $key => $value) {
            //     echo "<p>$key: $value</p>";
            // }
            
            $required = array(
                'first-name', 'last-name', 'birthdate',
                'address', 'city', 'state', 'zip', 
                'email', 'password', 'password-reenter', 'gender'
            );
            $errors = false;
            if (!wereRequiredFieldsSubmitted($args, $required)) {
                $errors = true;
            }
            $first = $args['first-name'];
            $last = $args['last-name'];
            $dateOfBirth = validateDate($args['birthdate']);
            if (!$dateOfBirth) {
                $errors = true;
                echo 'bad dob';
            }

            $address = $args['address'];
            $city = $args['city'];
            $state = $args['state'];
            if (!valueConstrainedTo($state, array('AK', 'AL', 'AR', 'AZ', 'CA', 'CO', 'CT', 'DC', 'DE', 'FL', 'GA',
                    'HI', 'IA', 'ID', 'IL', 'IN', 'KS', 'KY', 'LA', 'MA', 'MD', 'ME',
                    'MI', 'MN', 'MO', 'MS', 'MT', 'NC', 'ND', 'NE', 'NH', 'NJ', 'NM',
                    'NV', 'NY', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX',
                    'UT', 'VA', 'VT', 'WA', 'WI', 'WV', 'WY'))) {
                $errors = true;
            }
            $zipcode = $args['zip'];
            if (!validateZipcode($zipcode)) {
                $errors = true;
                echo 'bad zip';
            }
            $email = strtolower($args['email']);
            $email = validateEmail($email);
            if (!$email) {
                $errors = true;
                echo 'bad email';
            }
            $phone = '';
            
            $phoneType = '';
            
            $contactWhen = '';
            $contactMethod = 'email';
            
            $econtactName = '';
            $econtactPhone = '';
            
            $econtactRelation = '';

            $startDate = null;
            
            $gender = $args['gender'];
            if (!valueConstrainedTo($gender, ['Male', 'Female', 'Other'])) {
                $errors = true;
                echo 'bad gender';
            }

            // every new account is an applicant
            $type = 'applicant';

            $skills = '';
            
            $hasComputer = null;
            $hasCamera = '';
            $hasTransportation = '';
            $shirtSize = '';
            

            // May want to enforce password requirements at this step
            $password = password_hash($args['password'], PASSWORD_BCRYPT);
            if ($args['password'] !== $args['password-reenter']) {
                $errors = true;
                echo 'Passwords must match';
            }

            //$days = array('sundays', 'mondays', 'tuesdays', 'wednesdays', 'thursdays', 'fridays', 'saturdays');
            
            $sundaysStart = '';
            $sundaysEnd = '';
            
            $mondaysStart = '';
            $mondaysEnd = '';
            
            $tuesdaysStart = '';
            $tuesdaysEnd = '';
            
            $wednesdaysStart = '';
            $wednesdaysEnd = '';
            
            $thursdaysStart = '';
            $thursdaysEnd = '';
            
            $fridaysStart = '';
            $fridaysEnd = '';
            
            $saturdaysStart = '';
            $saturdaysEnd = '';
            

            if ($errors) {
                echo '<p>Your form submission contained unexpected input.</p>';
                die();
            }

            // ------------------------
            
            // Wade - I am thinking that I need to add the role to the person here.
            // I am not doing this currently because I think it if I do I will need to modify the PersonDB (31OCT2023)

            // need to incorporate availability here
            if($type == "applicant"){
                $newperson = new Person($first, $last, 'portland', 
                    $address, $city, $state, $zipcode, "",
                    '', null, null, null,
                    $email, null, null, '', '', '', '', '', 
                    '', $type, 'Active', '', null, null,
                    null, null, $skills, null, '', '', '', 
                    $dateOfBirth, null, null, null, $password,
                    null, null, null, null,
                    null, null, null, null,
                    null, null, null, null,
                    null, null, 0, $gender
                );
            }
            /*elseif ($type == "Trainer") {
                $newperson = new Person($first, $last, 'portland', 
                    $address, $city, $state, $zipcode, "",
                    $phone, $phoneType, null, null,
                    $email, $shirtSize, $hasComputer, $hasCamera, $hasTransportation, $econtactName, $econtactPhone, $econtactRelation, 
                    $contactWhen, 'verifyTrainer', 'Active', $contactMethod, null, null,
                    null, null, $skills, null, '', '', '', 
                    $dateOfBirth, $startDate, null, null, $password,
                    $sundaysStart, $sundaysEnd, $mondaysStart, $mondaysEnd,
                    $tuesdaysStart, $tuesdaysEnd, $wednesdaysStart, $wednesdaysEnd,
                    $thursdaysStart, $thursdaysEnd, $fridaysStart, $fridaysEnd,
                    $saturdaysStart, $saturdaysEnd, 0, $gender
                );
            }
            elseif ($type == "Admin") {
                $newperson = new Person($first, $last, 'portland', 
                    $address, $city, $state, $zipcode, "",
                    $phone, $phoneType, null, null,
                    $email, $shirtSize, $hasComputer, $hasCamera, $hasTransportation, $econtactName, $econtactPhone, $econtactRelation, 
                    $contactWhen, 'verifyAdmin', 'Active', $contactMethod, null, null,
                    null, null, $skills, null, '', '', '', 
                    $dateOfBirth, $startDate, null, null, $password,
                    $sundaysStart, $sundaysEnd, $mondaysStart, $mondaysEnd,
                    $tuesdaysStart, $tuesdaysEnd, $wednesdaysStart, $wednesdaysEnd,
                    $thursdaysStart, $thursdaysEnd, $fridaysStart, $fridaysEnd,
                    $saturdaysStart, $saturdaysEnd, 0, $gender
                );
            }*/
            
            // ------------------------
            
            message_all_users_of_types($email, 'admin', "New User Registered", "Go Verify or Delete Them");
            send_message($email, 'vmsroot', "New User Registered", "Go Verify or Delete Them");
            $result = add_person($newperson);
            if (!$result) {
                echo '<p>That e-mail address is already in use.</p>';
            } else {
                if ($loggedIn) {
                    echo '<script>document.location = "index.php?registerSuccess";</script>';
                } else {
                    echo '<script>document.location = "login.php?registerSuccess";</script>';
                }
            }
        } else {
            require_once('registrationForm.php'); 
        }
    ?>
</body>
</html>
