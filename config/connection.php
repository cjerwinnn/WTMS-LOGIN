<?php
date_default_timezone_set('Asia/Manila');

$servername = "192.168.1.101";
$username = "apphiisdb";
$password = "ABCD1234";
$dbname = "whp_live_db";
$dbname2 = "hris_live_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
}

$conn1 = new mysqli($servername, $username, $password, $dbname);

if ($conn1->connect_error) {
    die("Connection failed: " . $conn1->connect_error);
} else {
}

$conn2 = new mysqli($servername, $username, $password, $dbname2);

if ($conn2->connect_error) {
    die("Connection failed: " . $conn2->connect_error);
} else {
}

$project_name = 'PEP';