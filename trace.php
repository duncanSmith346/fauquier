<?php
    function trace($m) { file_put_contents(__DIR__ . '/trace.log', date('H:i:s') . " $m\n", FILE_APPEND); }
    register_shutdown_function(function () {
        $e = error_get_last();
        trace($e ? 'SHUTDOWN ERROR: ' . print_r($e, true) : 'shutdown clean');
    });
?>