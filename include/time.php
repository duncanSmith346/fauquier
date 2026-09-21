<?php

/* Take two 24-hour times and return the number of hours between them */
function calculateHourDuration($start, $end) {
    $startSec = strtotime($start);
    $endSec   = strtotime($end);
    
    // If either fails to parse, return -1
    if (!$startSec || !$endSec) {
        return -1;
    }
    
    // Calculate difference in hours
    $diffSeconds = $endSec - $startSec;
    $diffHours   = $diffSeconds / 3600;
    // Optionally format to 1 decimal place if you want
    return number_format($diffHours, 1, '.', '');
}


?>
