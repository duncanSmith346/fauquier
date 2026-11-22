<?php
session_start();

require_once('database/dbPersons.php');
require_once('database/dbApplication.php');
require_once('database/dbQuestions.php');

if (!isset($_SESSION['_id'])) {
    header('Location: login.php');
    exit;
}

$person = retrieve_person($_SESSION['_id']);

if (!$person) {
    header('Location: login.php');
    exit;
}

$types = (array) $person->get_type();

$admin = in_array('admin', $types, true);
$cohort = in_array('cohort', $types, true);

if (!$admin && !$cohort) {
    header('Location: index.php');
    exit;
}

$cycle_id = '2026';
$form_type = 'sampleonboardingappointment';

$error = null;
$appointment = null;
$existing_form = null;
$available_appointments = [];
$all_appointments = [];

if ($admin) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['create_appointment'])) {

        $meetingAt = trim($_POST['meetingAt'] ?? '');

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            $meetingAt
        );

        if (!$date ||
            $date->format('Y-m-d\TH:i') !== $meetingAt) {

            $error = 'Please enter a valid date and time.';

        } elseif ($date <= new DateTimeImmutable()) {

            $error = 'The appointment must be in the future.';

        } else {
            try {
                $success = create_onboarding_appointment(
                    $cycle_id,
                    $date->format('Y-m-d H:i:s')
                );

                if ($success) {
                    header('Location: OnboardingAppointment.php');
                    exit;
                }

                $error = 'Could not create appointment.';
            } catch (Throwable $e) {
                $error = 'That appointment time may already exist.';
            }
        }
    }

    $all_appointments = get_all_onboarding_appointments($cycle_id);
    } elseif ($cohort) {
    $appointment = get_onboarding_appointment(
        $_SESSION['_id'], $cycle_id
    );

    $existing_form = get_cohort_form(
        $_SESSION['_id'], $cycle_id, $form_type
    );

    $available_appointments = get_available_onboarding_appointments(
        $cycle_id
    );
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php require('universal.inc'); ?>
    <title>Onboarding Appointments - Leadership Fauquier</title>
</head>

<body>
    <?php require('header.php'); ?>

    <main>
        <h1>Onboarding Appointments</h1>

        <div class="navigation">
            <a href="index.php" class="navigation_button">
                Dashboard
            </a>
        </div>

        <?php if ($admin): ?>

            <h2>Create Appointment</h2>

            <?php if ($error): ?>
                <p class="error">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <label for="meetingAt">Date and Time:</label>

                <input
                    type="datetime-local"
                    id="meetingAt"
                    name="meetingAt"
                    required
                >

                <button type="submit" name="create_appointment" value="1">
                    Add Appointment
                </button>
            </form>

        <?php endif; ?>
        <?php if ($cohort && !$admin): ?>

    <h2>Schedule Your Onboarding Appointment</h2>

    <?php if ($appointment): ?>

        <p>
            Your appointment is scheduled for:
            <strong>
                <?= htmlspecialchars(
                    date('F j, Y \a\t g:i A',
                         strtotime($appointment['meetingAt']))
                ) ?>
            </strong>
        </p>

    <?php else: ?>

        <form method="POST" enctype="multipart/form-data">

            <label for="appointment_id">
                Select an Available Appointment:
            </label>

            <select name="appointment_id" id="appointment_id" required>
                <option value="">Choose a date and time</option>

                <?php foreach ($available_appointments as $slot): ?>
                    <option value="<?= htmlspecialchars($slot['appointment_id']) ?>">
                        <?= htmlspecialchars(
                            date('F j, Y \a\t g:i A',
                                 strtotime($slot['meetingAt']))
                        ) ?>
                    </option>
                <?php endforeach; ?>

            </select>

            <button type="submit" name="book_appointment" value="1">
                Schedule Appointment
            </button>

        </form>

        <?php if (empty($available_appointments)): ?>
            <p>No appointments are currently available.</p>
        <?php endif; ?>

    <?php endif; ?>

<?php endif; ?>

    </main>
</body>
</html>