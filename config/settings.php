<?php

include 'config/connection.php';

function GetASF($conn) {
    $query = "SELECT asf_percent FROM ref_asf_percentage WHERE default_asf = 1";
    $result = $conn->query($query);

    if ($result) {
        return $result->fetch_assoc();
    } else {
        return ['asf_percent' => 0.00];
    }
}

// hahah
?>
