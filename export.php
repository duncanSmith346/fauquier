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

    // 1. Create a new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // 2. Write individual cells
    $sheet->setCellValue('A1', 'Name');
    $sheet->setCellValue('B1', 'Email');

    // 3. Or write an entire array of rows at once
    $data = [
        ['Alice Smith', 'alice@example.com'],
        ['Bob Jones', 'bob@example.com'],
    ];
    $sheet->fromArray($data, null, 'A2');

    // 4. Save to a file on the server
    $writer = new Xlsx($spreadsheet);
    $writer->save('export.xlsx');

    #header("location: index.php")
    echo 'done';
?>
