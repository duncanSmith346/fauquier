<?php
$person = retrieve_person($_SESSION['_id']);

    $notRoot = $person->get_id() != 'vmsroot';
    $admin = $person->get_type()[0] == 'admin';
    $applicant = in_array('applicant', $person->get_type(), true);
    $cohort = person->get_type()[0] == 'cohort';
    $cm = person->get_type()[0] == 'cm';


?>

<?php
    if($applicant):
?>
    applicant view
<?php
    elseif($admin): 
?>
    admin view

<?php     
    elseif($cm): 
?>
    cm view
<?php     
    elseif($cohort): 
?>
    cohort view
<?php endif; ?>
