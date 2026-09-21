<?php

require_once('database/dbinfo.php');
date_default_timezone_set("America/New_York");

function get_user_messages($userID) {
    $query = "select * from dbMessages
              where recipientID='$userID'
              order by time desc";
    $connection = connect();
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return null;
    }
    $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
    foreach ($messages as &$message) {
        foreach ($message as $key => $value) {
            $message[$key] = htmlspecialchars($value);
        }
    }
    unset($message);
    mysqli_close($connection);
    return $messages;
}

function get_user_unread_count($userID) {
    $query = "select count(*) from dbMessages 
        where recipientID='$userID' and wasRead=0";
    $connection = connect();
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return null;
    }

    $row = mysqli_fetch_row($result);
    mysqli_close($connection);
    return intval($row[0]);
}

function get_message_by_id($id) {
    $query = "select * from dbMessages where id='$id'";
    $connection = connect();
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_close($connection);
    foreach ($row as $key => $value) {
        $row[$key] = htmlspecialchars($value);
    }
    $row['body'] = str_replace("\r\n", "<br>", $row['body']);
    return $row;
}

function send_message($from, $to, $title, $body) {
    $time = date('Y-m-d-H:i');
    $connection = connect();
    $title = mysqli_real_escape_string($connection, $title);
    $body = mysqli_real_escape_string($connection, $body);
    $query = "insert into dbMessages
        (senderID, recipientID, title, body, time)
        values ('$from', '$to', '$title', '$body', '$time')";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return null;
    }
    $id = mysqli_insert_id($connection);
    mysqli_close($connection);
    return $id; // get row id
}

function send_system_message($to, $title, $body) {
    send_message('vmsroot', $to, $title, $body);
}

function mark_read($id) {
    $query = "update dbMessages set wasRead=1
              where id='$id'";
    $connection = connect();
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return false;
    }
    mysqli_close($connection);
    return true;
}

function message_all_users_of_types($from, $types, $title, $body) {
    if (!is_array($types)) {
        $types = [$types];
    }
    $types = array_map(function($t) {

        return "'" . str_replace("'", "", $t) . "'";
    }, $types);

    $typesList = implode(',', $types);

    $query = "SELECT id FROM dbPersons WHERE type IN ($typesList)";
    
    $connection = connect();
    $result = mysqli_query($connection, $query);
    if (!$result) {
        error_log("SQL Error in message_all_users_of_types: " . mysqli_error($connection));
        mysqli_close($connection);
        return false;
    }

    $rows = mysqli_fetch_all($result, MYSQLI_NUM);
    if (!$rows) {
        mysqli_close($connection);
        return true; 
    }
    $time = date('Y-m-d-H:i');
    foreach ($rows as $row) {
        $to = $row[0];
        $insert = "INSERT INTO dbMessages (senderID, recipientID, title, body, time)
                   VALUES ('$from', '$to', '$title', '$body', '$time')";
        $ok = mysqli_query($connection, $insert);
        if (!$ok) {
            error_log("Insert error: " . mysqli_error($connection));
        }
    }

    mysqli_close($connection);    
    return true;
}


function message_all_volunteers($from, $title, $body) {
    return message_all_users_of_types($from, ['"volunteer"'], $title, $body);
}

function system_message_all_volunteers($title, $body) {
    return message_all_users_of_types('vmsroot', ['"volunteer"'], $title, $body);
}

function message_all_admins($from, $title, $body) {
    return message_all_users_of_types($from, ['"admin"', '"superadmin"'], $title, $body);
}

function system_message_all_admins($title, $body) {
    return message_all_users_of_types('vmsroot', ['"admin"', '"superadmin"'], $title, $body);
}

function system_message_all_users_except($except, $title, $body) {
    $time = date('Y-m-d-H:i');
    $query = "select id from dbPersons where id!='$except'";
    $connection = connect();
    $result = mysqli_query($connection, $query);
    $rows = mysqli_fetch_all($result, MYSQLI_NUM);
    foreach ($rows as $row) {
        $to = $row[0];
        $query = "insert into dbMessages (senderID, recipientID, title, body, time)
                  values ('vmsroot', '$to', '$title', '$body', '$time')";
        $result = mysqli_query($connection, $query);
    }
    mysqli_close($connection);    
    return true;
}

function message_all_users($from, $title, $body) {
    $time = date('Y-m-d-H:i');
    $query = "select id from dbPersons where id!='$from'";
    $connection = connect();
    $result = mysqli_query($connection, $query);
    $rows = mysqli_fetch_all($result, MYSQLI_NUM);
    foreach ($rows as $row) {
        $to = $row[0];
        $query = "insert into dbMessages (senderID, recipientID, title, body, time)
                  values ('$from', '$to', '$title', '$body', '$time')";
        $result = mysqli_query($connection, $query);
    }
    mysqli_close($connection);    
    return true;
}

// send_message('vmsroot', 'lknight2@mail.umw.edu', 'I am a bad test """""!!ASDF', "helloAAA'''ffdf!!$$");