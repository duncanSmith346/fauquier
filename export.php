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

    $person_id = $_POST['id'];

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
    $basicInfoList = export_list($person_id);

    $data = [];
    $data[] = $basicInfoList;

    $sheet->fromArray($data, null, 'A2');

    foreach (range('A', 'P') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }


    $fromids = formIds($person_id);

    $applicationQuestions = getQuestions($fromids['dbApplication.id']);
    $scholarshipQuestions = getQuestions($fromids['dbScholarship.id']);
    $commitmentQuestions = getQuestions($fromids['dbCommitment.id']);
    
    $applicationData = [];
    $scholarshipData = [];
    $commitmentData = [];
    $files = [];

    while ($result = mysqli_fetch_assoc($applicationQuestions)) {
        if ($result['type'] === 'file') {
            $files[] = $result['answer'];
        }
        $applicationData[] = $result;
    }

    while ($result = mysqli_fetch_assoc($scholarshipQuestions)) {
    if ($result['type'] === 'file') {
            $files[] = $result['answer'];
        }    
        $scholarshipData[] = $result;
    }

    while ($result = mysqli_fetch_assoc($commitmentQuestions)) {
        if ($result['type'] === 'file') {
            $files[] = $result['answer'];
        }    
        $commitmentData[] = $result;
    }

    $sheet->fromArray($applicationData, null, 'A6');
    $sheet->fromArray($scholarshipData, null, 'D6');
    $sheet->fromArray($commitmentData, null, 'G6');

    $sheet->getStyle('B6:B100')->getAlignment()->setWrapText(true);
    $sheet->getStyle('D6:D100')->getAlignment()->setWrapText(true);
    $sheet->getStyle('G6:G100')->getAlignment()->setWrapText(true);

    // 4. Save to a file on the server
    $writer = new Xlsx($spreadsheet);
    $writer->save($basicInfoList['name'] . '.xlsx');

    $files[] = $basicInfoList['name'] . '.xlsx';

    $zipName = $basicInfoList['name'] . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipName;
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        exit("Cannot open <$zipPath>\n");
    }
    foreach ($files as $file) {
        if (file_exists($file)) {
            // basename() prevents full server directory paths from appearing inside the zip
            $zip->addFile($file, basename($file));
        }
    }
    $zip->close();

    // 5. Force download the file
    if (file_exists($zipPath)) {
        // Clear output buffer to prevent corrupted zip files
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zipName) . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($zipPath);

        // 6. Delete the temporary file from the server after download
        unlink($zipPath);
        exit;
    }

    header("location: index.php")
?>
