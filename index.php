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

        if ($admin && ($_POST['action'] ?? '') === 'application_payment') {

            $application_id = $_POST['application_id'] ?? '';
            $status = $_POST['payment_status'] ?? '';

            if ($application_id !== '' && in_array($status, ['0', '1'], true)) {
                update_application_payment($application_id, (int)$status);
            }

            header('Location: index.php');
            exit;

            } elseif ($admin && ($_POST['action'] ?? '') === 'tuition_payment') {

                $commitment_id = $_POST['commitment_id'] ?? '';
                $status = $_POST['payment_status'] ?? '';

                if ($commitment_id !== '' && in_array($status, ['0', '1'], true)) {
                    update_tuition_payment($commitment_id, (int)$status);
                }

                header('Location: index.php');
                exit;    

        } elseif ($admin && isset($_POST['application_id'], $_POST['CM'])) {

            $application_id = $_POST['application_id'];
            $reviewer_id = $_POST['CM'];

            if ($reviewer_id === 'Unselected') {
            $reviewer_id = NULL;
            }

            $success = update_reviewer($reviewer_id, $application_id);
        }
    }
    if (isset($_GET['page'])) $page = $_GET['page'];
    else $page = 0;

?>
<!DOCTYPE html>
<html>
    <head>
        <?php require('universal.inc'); ?>
        <title>Dashboard - Leadership Fauquier</title>
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
<!--            <p>Welcome back, <?php echo $person->get_first_name() ?>!</p> -->
<!--            <p>Today is <?php echo date('l, F j, Y'); ?>.</p>       -->     
             
<!-- Original page code from empowerhouse -->

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

            <!-- Below here we shall add the code for each version of index acording to how Ryan wrote the if Admin section. -->
            <!-- Dashboard for if Admin -->

            <?php if ($admin):?>
                <div class="admin_dashboard">
                    <div class="navigation">
                        <a href="Forms.php" class="navigation_button"> Forms</a>
                        <a href="manageCommitteeMembers.php" class="navigation_button"> Committee Members</a>
                        <a href="personSearch.php" class="navigation_button"> Person Search</a>
                        <a href="logout.php" class="navigation_button"> Log out</a>
                    </div>
                    <div class='application_list'>
                        <?php $results = admin_list($page);?>
                        <table class='admin_applicatin_table'>
                            <tr class='admin_applicatin_table'>
                                <th class='admin_applicatin_table'>Applicant</th>
                                <th class='admin_applicatin_table'>Application</th>
                                <th class='admin_applicatin_table'>Documents</th>
                                <th class='admin_applicatin_table'>Application Fee</th>
                                <th class='admin_applicatin_table'>Review</th>
                                <th class='admin_applicatin_table'>Accepted</th>
                                <th class='admin_applicatin_table'>Commitment</th>
                                <th class='admin_applicatin_table'>Tuition</th>
                                <th class='admin_applicatin_table'>Onboarding</th>
                            </tr>
                            <?php foreach($results as $result) : ?>
                            <tr class='admin_applicatin_table'>
                                <!--Applicant-->
                                <td class='admin_applicatin_table'>
                                    <?php if ($result['appid'] !== NULL):?>
                                    <a href='Application.php?id=<?= urldecode($result['appid'])?>' class='adminlist_applicationName'>
                                    <?php echo $result['fullname'];?></a>
                                    <?php 
                                    else: 
                                        echo $result['fullname'];
                                    endif;
                                    ?>
                                </td>
                                <!--Application-->
                                <td class='admin_applicatin_table'>
                                    <?php 
                                    if ($result['appid'] == NULL): echo 'missing';
                                    elseif ($result['submittedAt'] === NULL): echo 'in progress';
                                    else: echo 'complete';
                                    endif;
                                    ?>
                                </td>
                                <!--Documents-->
                                <td class='admin_applicatin_table'>unfinished</td>
                                <!--Application Fee-->
                                <td class='admin_applicatin_table'>
                                   <?php if ($result['appid'] !== NULL): ?>
                                       <form method="POST" action="index.php">
                                         <input type="hidden" name="application_id"
                                             value="<?= htmlspecialchars($result['appid']) ?>">

                                         <input type="hidden" name="action" value="application_payment">

                                         <input type="hidden" name="payment_status"
                                              value="<?= (int)$result['applicationFee'] === 1 ? 0 : 1 ?>">

                                         <button type="submit" class="payment-toggle">
                                             <?= (int)$result['applicationFee'] === 1 ? 'Paid' : 'Unpaid' ?>
                                         </button>
                                      </form>
                                  <?php else: ?>
                                        N/A
                                  <?php endif; ?>
                                </td>
                                <!--Review Status-->
                                <td class='admin_applicatin_table'>
                                    <?php
                                    if ($result['reviewStatus'] === 1) echo 'Reviewed';
                                    else echo 'Not Reviewed';
                                    ?>
                                </td>
                                <!--Decision Status-->
                                <td class='admin_applicatin_table'>
                                    <?php 
                                    if ($result['decisionStatus'] === NULL) echo 'undecided';
                                    else echo $result['decisionStatus']; 
                                    ?>
                                </td>
                                <!--Commitment Status-->
                                <td class='admin_applicatin_table'>
                                    <?php 
                                    if ($result['commitment'] === NULL) echo 'missing';
                                    elseif ($result['submittedDate'] === NULL) echo 'in progress'; 
                                    else echo '<a href="Commitment.php?id=' .
                                         urlencode($result['commitment']) .
                                         '" class="adminlist_applicationName">complete</a>';
                                    ?>
                                </td>
                                <!--Tuition Status-->
                                <td class='admin_applicatin_table'>
                                    <?php if ($result['commitment'] !== NULL): ?>
                                        <form method="POST" action="index.php">
                                            <input type="hidden" name="commitment_id"
                                                value="<?= htmlspecialchars($result['commitment']) ?>">

                                            <input type="hidden" name="action" value="tuition_payment">

                                            <input type="hidden" name="payment_status"
                                                    value="<?= (int)$result['tuition'] === 1 ? 0 : 1 ?>">

                                            <button type="submit" class="payment-toggle">
                                                <?= (int)$result['tuition'] === 1 ? 'Paid' : 'Unpaid' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <!--Onboarding Status-->
                                <td class='admin_applicatin_table'>
                                    unfinished
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <div class='paging'>
                            <form action="index.php">
                                <?php  $count = get_count_admin_list()["count(dbapplication.id)"]/10;
                                $i = 0;
                                while ($i < $count): 
                                    $n = $i +1;
                                    ?>
                                <button class='dashboardPaging' type='submit' name='page' value='<?php echo $i;?>'><?php echo $n;?></button>

                                <?php 
                                $i = $i + 1; 
                                endwhile;
                                ?>
                            </form>
                        </div>             
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
                            if (isset($_GET['assignedOnly'])):
                                $results = get_assigned_applications($_SESSION['_id'],$page);
                        ?>
                        <form action="index.php">
                            <button type='submit'>View All Applications</button>
                        </form>
                        <?php
                            else: 
                                $results = retrieve_undecided_applicantions_names($page);
                        ?>
                        <form action="index.php" method='GET'>
                            <button type='submit' name='assignedOnly' value='True'>View Only Assigned</button>
                        </form>
                        <?php
                            endif;
                            foreach($results as $result) : 
                        ?>
                            <div class="application_card">
                                <a href='Application.php?id=<?= urldecode($result['id'])?>' class='href_application_card'> 
                                    Applicant Name: <?php echo $result['answer1'];?>  
                                </a>
                                | Committee Member: 
                                <?php if ($result['reviewer_id'] === NULL) echo 'undecided';
                                    else {
                                        $name = get_name_from_id($result['reviewer_id']);
                                        echo $name['first_name'];
                                        echo ' '; 
                                        echo $name['last_name'];
                                    }
                                ?>
                            </div>
                        <?php endforeach; ?>
                        <div class='paging'>
                            <form action="index.php">
                                <?php if (isset($_GET['assignedOnly'])):?>
                                    <input type='hidden' name='assignedOnly' value='True'/>
                                <?php endif;?>
                                <?php  $count = get_count_admin_list()["count(dbapplication.id)"]/10;
                                $i = 0;
                                while ($i < $count): 
                                    $n = $i +1;
                                    ?>
                                <button class='dashboardPaging' type='submit' name='page' value='<?php echo $i;?>'><?php echo $n;?></button>

                                <?php 
                                $i = $i + 1; 
                                endwhile;
                                ?>
                            </form>
                        </div>
                    </div>
                </div>
             <?php endif ?>

            <!-- Dashboard for if Cohort -->
            <?php if ($cohort): ?> 
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
                    <div class="dashboard-item" data-link="Commitment.php">
                        <img src="images/create-report.svg">
                        <span>Commitment Form</span>
                    </div>
                    <a class="dashboard-item"
                        href="https://www.youtube.com/watch?v=uE-1RPDqJAY"
                        target="_blank"
                        rel="noopener noreferrer">
                            <span class="payment-icon" style="font-size: 6rem !important; line-height: 6rem;">$</span>
                            <span>Pay Tuition</span>
                    </a>
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
                <?php
                    $application = get_applicant_application($_SESSION['_id'], '2026');
                ?>
                <div id="dashboard">
                    <div class="dashboard-item" data-link="Application.php">
                        <img src="images/document.svg">
                        <span style="text-align:center;">Manage Application</span>
                    </div>

                    <?php if ($application): ?>
                        <div class="dashboard-item" data-link="Scholarship.php">
                            <img src="images/document.svg">
                            <span style="text-align:center;">Manage Scholarship</span>
                        </div>
                    <?php else: ?>
                        <div class="dashboard-item" style="opacity: 0.4; cursor: not-allowed;">
                            <img src="images/document.svg">
                            <span style="text-align:center;">Manage Scholarship</span>
                        </div>
                    <?php endif; ?>

                    <a class="dashboard-item"
                        href="https://www.youtube.com/watch?v=dQw4w9WgXcQ"
                        target="_blank"
                        rel="noopener noreferrer">
                            <span class="payment-icon" style="font-size: 6rem !important; line-height: 6rem;">$</span>
                            <span style="text-align:center;">Pay Application Fee</span>
                    </a>
                </div>
            <?php endif; ?> 
        </main>
    </body>
</html>