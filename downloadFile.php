<?php
ob_start();
session_start();
require_once('database/dbPersons.php');
require_once('database/dbApplication.php');   // for UPLOAD_DIR

if (!isset($_SESSION['_id'])) { http_response_code(403); exit; }
$person = retrieve_person($_SESSION['_id']);
if (!array_intersect(['admin', 'cm'], $person->get_type())) { http_response_code(403); exit; }

$file = $_GET['file'] ?? '';
if (!preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|pdf|docx)$/', $file)) { http_response_code(400); exit; }

$path = UPLOAD_DIR . $file;
if (!is_file($path)) { http_response_code(404); exit; }

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $file . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
readfile($path);