<?php
session_start();

require_once('database/dbPersons.php');
require_once('database/dbApplication.php');

// Only administrators can access this page.
if (!isset($_SESSION['_id'])) {
    header('Location: login.php');
    exit;
}

$admin = retrieve_person($_SESSION['_id']);

if (!$admin || !in_array('admin', (array) $admin->get_type(), true)) {
    header('Location: index.php');
    exit;
}

// Retrieve the selected applicant or cohort member.
$person_id = $_GET['id'] ?? '';
$member = retrieve_person($person_id);

if (!$member) {
    die('Person not found.');
}

$cycle_id = '2026';
$commitment = get_applicant_commitment($person_id, $cycle_id);
?>

<!DOCTYPE html>
<html>
<head>
    <?php require('universal.inc'); ?>
    <title>Profile - Leadership Fauquier</title>
</head>

    <body>
        <?php require('header.php'); ?>

        <h1>
            <?= htmlspecialchars($member->get_first_name()) ?>
            <?= htmlspecialchars($member->get_last_name()) ?>
        </h1>

        <main class="signup-form">
            <div class="navigation">
                <a href="index.php" class="navigation_button">Dashboard</a>
            </div>
            <div style="margin-left: 25rem; margin-bottom: 2rem;">
                <h2>Profile Information</h2>

                <p>Role: <?= htmlspecialchars(implode(', ', (array) $member->get_type())) ?></p>
                <p>T-Shirt Size: <?= htmlspecialchars($member->get_shirt_size() ?: 'Not selected') ?></p>
                <br>
                <h2>Forms</h2>

                <p>
                    Commitment:
                    <?php if ($commitment): ?>
                        <a href="Commitment.php?id=<?= urlencode($commitment['commitment_id']) ?>">
                            View Commitment
                        </a>
                    <?php else: ?>
                        Missing
                    <?php endif; ?>
                </p>

                <?php
                $forms = [
                    'sampleallergy' => 'Allergy Information',
                    'sampleemergencycontact' => 'Emergency Contact',
                    'sampleselfassessment' => 'Self-Assessment'
                ];
                ?>

                <?php foreach ($forms as $form_type => $label): ?>

                    <?php
                    $form = get_cohort_form($person_id, $cycle_id, $form_type);
                    ?>

                <p>
                    <?= htmlspecialchars($label) ?>:

                    <?php if ($form): ?>

                        <a href="CohortForm.php?type=<?= urlencode($form_type) ?>&id=<?= urlencode($person_id) ?>">
                            Finished
                        </a>

                    <?php else: ?>

                        Unfinished

                    <?php endif; ?>
                </p>

                <?php endforeach; ?>

            </div>
            <form action='export.php' method='POST'>
                <input type='hidden' name='id' value='<?php echo $person_id; ?>' />
                <button class="application_buttons" type='submit'>export profile</button>
            </form>
        </main>
    </body>
</html>