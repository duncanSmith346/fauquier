<?php
    session_cache_expire(30);
    session_start();

    date_default_timezone_set("America/New_York");
    
  if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
		
        if (isset($_SESSION['change-password'])) {
            header('Location: changePassword.php');
        } else {
            header('Location: login.php');
        }
        die();
    }
    
    include_once('database/dbApplication.php');
    include_once('database/dbPersons.php');
    include_once('domain/Person.php');
    // Get date?
    if (isset($_SESSION['_id'])) {
        $person = retrieve_person($_SESSION['_id']);
    }
    $notRoot = $person->get_id() != 'vmsroot';
    $admin = $person->get_type()[0] == 'admin';
    $applicant = in_array('applicant', $person->get_type(), true);
    $cm = $person->get_type()[0] == 'cm';
    $cohort = in_array('cohort', $person->get_type(), true);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $application_id = $_POST['application_id'];
        $reviewer_id = $_POST['CM'];
        if ($reviewer_id === 'Unselected'){
            $reviewer_id = NULL;
        }
        $success = update_reviewer($reviewer_id, $application_id);
        
    }

?>
<!DOCTYPE html>
<html>
    <head>
        <?php require('universal.inc'); ?>
        <title>Leadership Fauquier | Dashboard</title>
    </head>
    <body>
        <?php require('header.php'); ?>
        <h1>Dashboard</h1>
        <main class='dashboard'>
            <?php if (isset($_GET['pcSuccess'])): ?>
                <div class="happy-toast">Password changed successfully!</div>
            <?php elseif (isset($_GET['registerSuccess'])): ?>
                <div class="happy-toast">Volunteer registered successfully!</div>
            <?php endif ?>
            <p>Welcome back, <?php echo $person->get_first_name() ?>!</p>
            <p>Today is <?php echo date('l, F j, Y'); ?>.</p>            
             
<!-- Original page code for empowerhouse -->

            <?php if (!$admin && !$applicant && !$cm && !$cohort): ?>
            <div id="dashboard">
                <?php
                    require_once('database/dbMessages.php');
                    $unreadMessageCount = get_user_unread_count($person->get_id());
                    $inboxIcon = 'inbox.svg';
                    if ($unreadMessageCount) {
                        $inboxIcon = 'inbox-unread.svg';
                    }
                ?>
                <div class="dashboard-item" data-link="inbox.php">
                    <img src="images/<?php echo $inboxIcon ?>">
                    <span>Notifications<?php 
                        if ($unreadMessageCount > 0) {
                            echo ' (' . $unreadMessageCount . ')';
                        }
                    ?></span>
                </div>
                <div class="dashboard-item" data-link="calendar.php">
                    <img src="images/view-calendar.svg">
                    <span>View Calendar</span>
                </div>
                <?php if ($_SESSION['access_level'] >= 2): ?>
                    <div class="dashboard-item" data-link="addEvent.php">
                        <img src="images/new-event.svg">
                        <span>Create Event</span>
                    </div>
                    <div class="dashboard-item" data-link="manageCourses.php">
                        <img src="images/new-event.svg">
                        <span>Manage Courses</span>
                    </div>
                <?php endif ?>
                <div class="dashboard-item" data-link="eventSearch.php">
                    <img src="images/search.svg">
                    <span>Find Event</span>
                </div>
                <?php if ($_SESSION['access_level'] >= 2): ?>
                    <div class="dashboard-item" data-link="personSearch.php">
                        <img src="images/person-search.svg">
                        <span>Find Person</span>
                    </div>
                    <div class="dashboard-item" data-link="register.php">
                        <img src="images/add-person.svg">
                        <span>Register Volunteer</span>
                    </div>
                    <div class="dashboard-item" data-link="report.php">
                        <img src="images/create-report.svg">
                        <span>Create Report</span>
                    </div>
                <?php endif ?>
                <?php if ($notRoot) : ?>
                    <div class="dashboard-item" data-link="viewProfile.php">
                        <img src="images/view-profile.svg">
                        <span>View Profile</span>
                    </div>
                    <div class="dashboard-item" data-link="editProfile.php">
                        <img src="images/manage-account.svg">
                        <span>Edit Profile</span>
                    </div>
                <?php endif ?>
                <?php if ($notRoot) : ?>
                    <div class="dashboard-item" data-link="volunteerReport.php">
                        <img src="images/volunteer-history.svg">
                        <span>View My Hours</span>
                    </div>
                <?php endif ?>
                <div class="dashboard-item" data-link="documents.php">
                    <img src="images/document.svg">
                    <span>View Documents</span>
                </div>
                <?php if ($_SESSION['access_level'] >= 2): ?>
                <div class="dashboard-item" data-link="viewEval.php">
                    <img src="images/paper-file-svgrepo-com.svg">
                    <span>View Evaluations</span>
                </div>
                <?php endif ?>
                <div class="dashboard-item" data-link="courseSignUp.php">
                    <img src="images/create-report.svg">
                    <span>Volunteer Course Sign Up</span>
                </div>
                <div class="dashboard-item" data-link="CourseEval.php">
                    <img src="images/create-report.svg">
                    <span>Course Evaluation</span>
                </div>
                <?php if ($notRoot and $admin) : ?>
                    <div class="dashboard-item" data-link="userSearch.php">
                        <img src="images/view-profile.svg">
                        <span>View Users</span>
                    </div>
                <?php endif ?>
                <?php if ($_SESSION['access_level'] >= 2): ?>
                <div class="dashboard-item" data-link="takeAttendance.php">
                    <img src="images/paper-file-svgrepo-com.svg">
                    <span>Take Attendance</span>
                </div>
                <?php endif ?>
                <div class="dashboard-item" data-link="changePassword.php">
                    <img src="images/change-password.svg">
                    <span>Change Password</span>
                </div>
                <div class="dashboard-item" data-link="TShirtSize.php">
                    <img src="images/t-shirt.svg">
                    <span>Change T-Shirt Size</span>
                </div>
                <div class="dashboard-item" data-link="logout.php">
                    <img src="images/logout.svg">
                    <span>Log out</span>
                </div>
            </div>
            <?php endif ?>

            <!-- Bellow here we shall add the code for each version of index acording to how Ryan wrote the if Admin section. -->
            <!-- Dashboard for if Admin -->

            <?php if ($admin):?>
                <div class="admin_dashboard">
                    <div class="navigation">
                        <a href="Forms.php" class="navigation_button"> Forms</a>
                        <a href="index.php" class="navigation_button"> Committee Members</a>
                        <a href="personSearch.php" class="navigation_button"> Person Search</a>
                        <a href="logout.php" class="navigation_button"> logout</a>
                        
                    </div>
                    <div class='application_list'>
                        <?php
                            $results = retrieve_undecided_applicantions_names();
                            $reviewers = get_cms();
                            foreach($results as $result) : 
                                
                        ?>
                        <div class="application_card">
                            <form action='index.php' method='POST'>
                                <a href='Application.php?id=<?= urldecode($result['id'])?>' class='href_application_card'> Applicant Name: <?php echo $result['answer1'];?>  </a>|  Committee Member: 
                                <select name="CM" id="CM" class='application_list_select'>
                                    <?php if ($result['reviewer_id'] == NULL): ?>
                                        <option value="Unselected" class='application_list_option'>Unselected</option>
                                        <?php foreach ($reviewers as $reviewer): ?>
                                        <option value="<?php echo $reviewer['id'] ?>" class="application_list_option">
                                            <?= htmlspecialchars(($reviewer['first_name'] ?? '') . ' ' . ($reviewer['last_name'] ?? '')) ?>
                                        </option>                                       
                                        <?php endforeach; 
                                    else:
                                        $reviewer = get_name_from_id($result['reviewer_id']);
                                    ?>  
                                    <option value="<?php echo $result['reviewer_id'] ?>"class="application_list_option">
                                            <?= htmlspecialchars(($reviewer['first_name'] ?? '') . ' ' . ($reviewer['last_name'] ?? '')) ?>
                                    </option>
                                    <option value="Unselected" class='application_list_option'>Unselected</option>
                                    <?php foreach ($reviewers as $reviewer): 
                                        if (!($result['reviewer_id'] === $reviewer['id'])):
                                        ?>
                                        <option value="<?php echo $reviewer['id'] ?>"class="application_list_option">
                                            <?= htmlspecialchars(($reviewer['first_name'] ?? '') . ' ' . ($reviewer['last_name'] ?? '')) ?>
                                        </option>
                                    <?php
                                        endif;
                                        endforeach;    
                                    endif;
                                    ?>
                                </select>
                                <input type='hidden' name='application_id' value='<?php echo $result['id'] ?>' />
                                <button type='submit' value='updateCMs'>Update</button>
                            </form>
                        </div>
                        <?php endforeach; ?>             
                    </div>
                </div>
            <?php endif ?>

            <!-- Dashboard for if CM -->

             <?php if ($cm): ?>
                <div class="admin_dashboard">
                    <div class="navigation">
                        <a href="index.php" class="navigation_button"> Applications</a>
                        <a href="logout.php" class="navigation_button"> logout</a>
                        
                    </div>
                    <div class='application_list'>
                        <?php
                            $results = retrieve_undecided_applicantions_names();
                            $reviewers = get_cms();
                            foreach($results as $result) : 
                        ?>
                            <div class="application_card">
                                <a href='Application.php?id=<?= urldecode($result['id'])?>' class='href_application_card'> Applicant Name: <?php echo $result['answer1'];?>  </a>|  Committee Member: 
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
             <?php endif ?>

            <!-- Dashboard for if Cohort -->
            <?php if ($cohort): ?> 
            <div id="dashboard">
                <div class="dashboard-item" data-link="inbox.php">
                    <img src="images/<?php echo $inboxIcon ?>">
                    <span>Notifications<?php 
                        if ($unreadMessageCount > 0) {
                            echo ' (' . $unreadMessageCount . ')';
                        }
                    ?></span>
                </div>
                <div class="dashboard-item" data-link="calendar.php">
                    <img src="images/view-calendar.svg">
                    <span>View Calendar</span>
                </div>
                <div class="dashboard-item" data-link="documents.php">
                    <img src="images/create-report.svg">
                    <span>Commitment Form</span>
                </div>
                <div class="dashboard-item" data-link="documents.php">
                    <img src="images/create-report.svg">
                    <span>Pay Tuition</span>
                </div>
                <div class="dashboard-item" data-link="documents.php">
                    <img src="images/create-report.svg">
                    <span>Self-Assessment</span>
                </div>
                <div class="dashboard-item" data-link="documents.php">
                    <img src="images/create-report.svg">
                    <span>Emergency Contact</span>
                </div>
                <div class="dashboard-item" data-link="documents.php">
                    <img src="images/create-report.svg">
                    <span>Allergy Information</span>
                </div>
                <div class="dashboard-item" data-link="documents.php">
                    <img src="images/document.svg">
                    <span>View Forms</span>
                </div>
                <div class="dashboard-item" data-link="changePassword.php">
                    <img src="images/change-password.svg">
                    <span>Change Password</span>
                </div>
                <div class="dashboard-item" data-link="TShirtSize.php">
                    <img src="images/t-shirt.svg">
                    <span>Change T-Shirt Size</span>
                </div>
                <div class="dashboard-item" data-link="logout.php">
                    <img src="images/logout.svg">
                    <span>Log out</span>
                </div>
            </div>
            <?php endif ?>

            <!-- Dashboard for if Applicant -->
            <?php if ($applicant): ?>
                <div class="applicant_dashboard">
                     <h2>Applicant Dashboard</h2>
                         <div class="dashboard-item">
                             <a href="Application.php">
                                   Manage Application
                            </a>
                         </div>
                 </div>
            <?php endif; ?>

        </main>
    </body>
</html>