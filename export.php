<?php
// Template for new Cohort pages. Base your new page on this one

// Make session information accessible, allowing us to associate
// data with the logged-in user.
    session_cache_expire(30);
    session_start();
    ini_set("display_errors", 1);
    error_reporting(E_ALL);

    $loggedIn = false;
    $accessLevel = 0;
    $userID = null;
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
        // 0 = not logged in, 1 = standard user, 2 = manager (Admin), 3 = super admin (TBI)
        $accessLevel = $_SESSION['access_level'];
        $userID = $_SESSION['_id'];
    } 
    // Require admin privileges
    if ($accessLevel < 2) {
        header('Location: login.php');
        echo 'bad access level';
        die();
    }

    require __DIR__ . '\vendor\autoload.php';
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    require_once('database/dbPersons.php');
    require_once('database/dbApplication.php');
    require_once('database/dbQuestions.php');

    // 1. Create a new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // 2. Write individual cells
    $sheet->setCellValue('A1', 'Name');
    $sheet->setCellValue('B1', 'Email');
    $sheet->setCellValue('C1', 'Phone');
    $sheet->setCellValue('D1', 'Shirt Size');
    $sheet->setCellValue('E1', 'Application Start Date');
    $sheet->setCellValue('F1', 'Application Cycle');
    $sheet->setCellValue('G1', 'Application Fee Status');
    $sheet->setCellValue('H1', 'Application Submitted Date');
    $sheet->setCellValue('I1', 'Application Review Status');
    $sheet->setCellValue('J1', 'Application Decision Date');
    $sheet->setCellValue('K1', 'Application Decision');
    $sheet->setCellValue('L1', 'Scholarship Submitted Date');
    $sheet->setCellValue('M1', 'Scholarship Decision');
    $sheet->setCellValue('N1', 'Scholarship Amount Awarded');
    $sheet->setCellValue('O1', 'Tuition Status');
    $sheet->setCellValue('P1', 'Commitment Submitted Date');


    // 3. Or write an entire array of rows at once
    $basicInfoList = export_list();

    $data = [];
    while ($result = mysqli_fetch_assoc($basicInfoList)) {
        $data[] = $result;
    }

    $sheet->fromArray($data, null, 'A2');

    foreach (range('A', 'P') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // 4. Save to a file on the server
    $writer = new Xlsx($spreadsheet);
    $writer->save('export.xlsx');

    header("location: index.php")
?>
