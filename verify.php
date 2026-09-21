<?php
session_cache_expire(30);
session_start();

date_default_timezone_set("America/New_York");

// If user not logged in or too low-level, redirect out.
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    if (isset($_SESSION['change-password'])) {
        header('Location: changePassword.php');
    } else {
        header('Location: logout.php');
    }
    die();
}

include_once('database/dbPersons.php');
include_once('domain/Person.php');
include_once('database/dbinfo.php');

if (isset($_SESSION['_id'])) {
    $person = retrieve_person($_SESSION['_id']);
}
$notRoot = isset($person) ? $person->get_id() != 'vmsroot' : true;

// Create connection
$conn = connect();
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Query for each user type to verify:
$sqlVolunteers = "SELECT * FROM dbPersons WHERE type = 'verify'";
$resultVolunteers = mysqli_query($conn, $sqlVolunteers);

$sqlAdmins = "SELECT * FROM dbPersons WHERE type = 'verifyAdmin'";
$resultAdmins = mysqli_query($conn, $sqlAdmins);

$sqlTrainers = "SELECT * FROM dbPersons WHERE type = 'verifyTrainer'";
$resultTrainers = mysqli_query($conn, $sqlTrainers);
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Empower House VMS | Verify</title>
</head>
<body>
<form method="post">
    <?php require_once('header.php'); ?>
    
    <label style="margin-left: 450px; font-size: 50px; font-weight: 600">
        User Verification Form
    </label>
    <br><br>

    <!-- VOLUNTEER TABLE -->
    <label for="volunteer" style="margin-left: 20px">Volunteer Users</label>
    <table id="volunteer"
           style="border:1px solid; width:97%; margin-left:20px; margin-bottom:20px;">
        <tr style="border:1px solid; background-color:#002A5E;">
            <td style="border:1px solid; color:greenyellow;">Verify</td>
            <td style="border:1px solid; color:red;">Delete</td>
            <td style="border:1px solid; color:white;">User ID / Email</td>
            <td style="border:1px solid; color:white;">First Name</td>
            <td style="border:1px solid; color:white;">Last Name</td>
            <td style="border:1px solid; color:white;">Type</td>
            <td style="border:1px solid; color:white;">Address</td>
            <td style="border:1px solid; color:white;">City</td>
            <td style="border:1px solid; color:white;">State</td>
            <td style="border:1px solid; color:white;">Zip</td>
            <td style="border:1px solid; color:white;">Phone1</td>
            <td style="border:1px solid; color:white;">Phone1 Type</td>
            <td style="border:1px solid; color:white;">Birthday</td>
            <td style="border:1px solid; color:white;">Gender</td>
        </tr>
        <?php while($row = mysqli_fetch_assoc($resultVolunteers)): ?>
            <?php $id = $row['id']; ?>
            <tr style="border:1px solid;">
                <td style="background-color:gray; border:1px solid; color:white; font-weight:500;">
                    <!-- Verify checkbox -->
                    <input type="checkbox"
                           id="chk_vol_verify_<?php echo $id; ?>"
                           name="chk_verify[]"
                           value="<?php echo $id; ?>"
                           onclick="validateRowVolunteer('<?php echo $id; ?>')">
                </td>
                <td style="background-color:gray; border:1px solid; color:white; font-weight:500;">
                    <!-- Delete checkbox -->
                    <input type="checkbox"
                           id="chk_vol_delete_<?php echo $id; ?>"
                           name="chk_delete[]"
                           value="<?php echo $id; ?>"
                           onclick="validateRowVolunteerDelete('<?php echo $id; ?>')">
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['id']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['first_name']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['last_name']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['type']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['address']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['city']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['state']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['zip']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['phone1']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['phone1type']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['birthday']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['gender']; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- ADMIN TABLE -->
    <label for="admin" style="margin-left: 20px">Admin Users</label>
    <table id="admin"
           style="border:1px solid; width:97%; margin-left:20px; margin-bottom:20px;">
        <tr style="border:1px solid; background-color:#002A5E;">
            <td style="border:1px solid; color:greenyellow;">Verify</td>
            <td style="border:1px solid; color:red;">Delete</td>
            <td style="border:1px solid; color:white;">User ID / Email</td>
            <td style="border:1px solid; color:white;">First Name</td>
            <td style="border:1px solid; color:white;">Last Name</td>
            <td style="border:1px solid; color:white;">Type</td>
            <td style="border:1px solid; color:white;">Address</td>
            <td style="border:1px solid; color:white;">City</td>
            <td style="border:1px solid; color:white;">State</td>
            <td style="border:1px solid; color:white;">Zip</td>
            <td style="border:1px solid; color:white;">Phone1</td>
            <td style="border:1px solid; color:white;">Phone1 Type</td>
            <td style="border:1px solid; color:white;">Birthday</td>
            <td style="border:1px solid; color:white;">Gender</td>
        </tr>
        <?php while($row = mysqli_fetch_assoc($resultAdmins)): ?>
            <?php $id = $row['id']; ?>
            <tr style="border:1px solid;">
                <td style="background-color:gray; border:1px solid; color:white; font-weight:500;">
                    <!-- Verify checkbox -->
                    <input type="checkbox"
                           id="chk_admin_verify_<?php echo $id; ?>"
                           name="chk_verify[]"
                           value="<?php echo $id; ?>"
                           onclick="validateRowAdmin('<?php echo $id; ?>')">
                </td>
                <td style="background-color:gray; border:1px solid; color:white; font-weight:500;">
                    <!-- Delete checkbox -->
                    <input type="checkbox"
                           id="chk_admin_delete_<?php echo $id; ?>"
                           name="chk_delete[]"
                           value="<?php echo $id; ?>"
                           onclick="validateRowAdminDelete('<?php echo $id; ?>')">
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['id']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['first_name']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['last_name']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['type']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['address']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['city']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['state']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['zip']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['phone1']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['phone1type']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['birthday']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['gender']; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- TRAINER TABLE -->
    <label for="trainer" style="margin-left: 20px">Trainer Users</label>
    <table id="trainer"
           style="border:1px solid; width:97%; margin-left:20px; margin-bottom:20px;">
        <tr style="border:1px solid; background-color:#002A5E;">
            <td style="border:1px solid; color:greenyellow;">Verify</td>
            <td style="border:1px solid; color:red;">Delete</td>
            <td style="border:1px solid; color:white;">User ID / Email</td>
            <td style="border:1px solid; color:white;">First Name</td>
            <td style="border:1px solid; color:white;">Last Name</td>
            <td style="border:1px solid; color:white;">Type</td>
            <td style="border:1px solid; color:white;">Address</td>
            <td style="border:1px solid; color:white;">City</td>
            <td style="border:1px solid; color:white;">State</td>
            <td style="border:1px solid; color:white;">Zip</td>
            <td style="border:1px solid; color:white;">Phone1</td>
            <td style="border:1px solid; color:white;">Phone1 Type</td>
            <td style="border:1px solid; color:white;">Birthday</td>
            <td style="border:1px solid; color:white;">Gender</td>
        </tr>
        <?php while($row = mysqli_fetch_assoc($resultTrainers)): ?>
            <?php $id = $row['id']; ?>
            <tr style="border:1px solid;">
                <td style="background-color:gray; border:1px solid; color:white; font-weight:500;">
                    <!-- Verify checkbox -->
                    <input type="checkbox"
                           id="chk_trainer_verify_<?php echo $id; ?>"
                           name="chk_verify[]"
                           value="<?php echo $id; ?>"
                           onclick="validateRowTrainer('<?php echo $id; ?>')">
                </td>
                <td style="background-color:gray; border:1px solid; color:white; font-weight:500;">
                    <!-- Delete checkbox -->
                    <input type="checkbox"
                           id="chk_trainer_delete_<?php echo $id; ?>"
                           name="chk_delete[]"
                           value="<?php echo $id; ?>"
                           onclick="validateRowTrainerDelete('<?php echo $id; ?>')">
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['id']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['first_name']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['last_name']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['type']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['address']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['city']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['state']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['zip']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['phone1']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['phone1type']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['birthday']; ?>
                </td>
                <td style="border:1px solid; color:black; font-weight:500;">
                    <?php echo $row['gender']; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <input type="submit" name="login" value="Verify / Delete Users">
</form>

<!-- JavaScript to uncheck one box if the other is checked (per row) -->
<script>
function validateRowVolunteer(id) {
    let verifyBox = document.getElementById("chk_vol_verify_" + id);
    let deleteBox = document.getElementById("chk_vol_delete_" + id);
    if (verifyBox.checked) {
        deleteBox.checked = false;
    }
}
function validateRowVolunteerDelete(id) {
    let verifyBox = document.getElementById("chk_vol_verify_" + id);
    let deleteBox = document.getElementById("chk_vol_delete_" + id);
    if (deleteBox.checked) {
        verifyBox.checked = false;
    }
}

function validateRowAdmin(id) {
    let verifyBox = document.getElementById("chk_admin_verify_" + id);
    let deleteBox = document.getElementById("chk_admin_delete_" + id);
    if (verifyBox.checked) {
        deleteBox.checked = false;
    }
}
function validateRowAdminDelete(id) {
    let verifyBox = document.getElementById("chk_admin_verify_" + id);
    let deleteBox = document.getElementById("chk_admin_delete_" + id);
    if (deleteBox.checked) {
        verifyBox.checked = false;
    }
}

function validateRowTrainer(id) {
    let verifyBox = document.getElementById("chk_trainer_verify_" + id);
    let deleteBox = document.getElementById("chk_trainer_delete_" + id);
    if (verifyBox.checked) {
        deleteBox.checked = false;
    }
}
function validateRowTrainerDelete(id) {
    let verifyBox = document.getElementById("chk_trainer_verify_" + id);
    let deleteBox = document.getElementById("chk_trainer_delete_" + id);
    if (deleteBox.checked) {
        verifyBox.checked = false;
    }
}
</script>

<?php
// Handle the form POST to verify or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grab the arrays of IDs (if none checked, these become empty arrays)
    $checkboxVerify = $_POST['chk_verify'] ?? [];
    $checkboxDelete = $_POST['chk_delete'] ?? [];

    // Verify selected users
    foreach ($checkboxVerify as $verifyId) {
        $sqlCheckType = "SELECT type FROM dbPersons WHERE id = '$verifyId'";
        $resType = mysqli_query($conn, $sqlCheckType);
        if ($resType && mysqli_num_rows($resType) > 0) {
            $rowType = mysqli_fetch_assoc($resType);
            $currentType = $rowType['type'];
            
            // Update type based on what they're waiting for
            if ($currentType === 'verify') {
                $conn->query("UPDATE dbPersons SET type='volunteer' WHERE id='$verifyId'");
                echo "Verified $verifyId as volunteer.<br>";
            } else if ($currentType === 'verifyAdmin') {
                $conn->query("UPDATE dbPersons SET type='admin' WHERE id='$verifyId'");
                echo "Verified $verifyId as admin.<br>";
            } else if ($currentType === 'verifyTrainer') {
                $conn->query("UPDATE dbPersons SET type='trainer' WHERE id='$verifyId'");
                echo "Verified $verifyId as trainer.<br>";
            }
        }
    }

    // Delete selected users
    foreach ($checkboxDelete as $deleteId) {
        $conn->query("DELETE FROM dbPersons WHERE id='$deleteId'");
        echo "Deleted user $deleteId.<br>";
    }
}

// Close DB connection
$conn->close();
?>
</body>
</html>
