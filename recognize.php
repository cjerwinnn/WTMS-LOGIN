<?php
// Include your database connection settings
include 'config/connection.php';

// Set the script's timezone to match your database
date_default_timezone_set('Asia/Manila');

// Set the content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Step 1: Get data from the frontend ---
    $img = $_POST['webcam_image'];
    $punch_action = $_POST['action'] ?? null; // 'IN' or 'OUT'

    if (!$punch_action || ($punch_action !== 'IN' && $punch_action !== 'OUT')) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action. Please use designated keys.']);
        exit;
    }

    // --- Step 2: Process the webcam image ---
    $img = str_replace('data:image/jpeg;base64,', '', $img);

    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);

    $temp_image_path = 'uploads/webcam.jpg';
    file_put_contents($temp_image_path, $data);

    // --- Step 3: Run Python script ---
    $python = '"C:\\Program Files\\Python311\\python.exe"';
    $script = "compare_faces.py";
    $command = "$python $script $temp_image_path 2>&1";
    $output = shell_exec($command);
    $employee_id = trim($output);

    // --- Step 4: Look up Employee and Insert Data ---
    if ($employee_id && $employee_id !== "Unknown face." && $employee_id !== "Face not detected on database.") {

        // $save_folder = 'captures'; 
        // if (!is_dir($save_folder)) {
        //     mkdir($save_folder, 0755, true); // Create the folder if it doesn't exist
        // }

        // // Create a unique filename, e.g., "2025-07-11_09-30-00_PDMC000325.jpg"
        // $current_datetime = date('Y-m-d_H-i-s');
        // $permanent_filename = "{$current_datetime}_{$employee_id}_{$punch_action}.jpg";
        // $permanent_image_path = "{$save_folder}/{$permanent_filename}";

        // // Copy the temporary file to its permanent location with the new name
        // // Using copy() is efficient as the file is already on the server
        // copy($temp_image_path, $permanent_image_path);
        // // --- END OF NEW PART ---

        $stmt = $conn2->prepare("SELECT firstname, lastname, gender FROM employee_general WHERE employeeid = ?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            $current_date = date('Y-m-d');
            $current_time = date('H:i:s');
            $verify_mode = 'Face Recognition';

            if ($punch_action === 'IN') {
                $punchstate = '0';
                $punch_status_message = 'Time In';
            } else { // 'OUT'
                $punchstate = '1';
                $punch_status_message = 'Time Out';
            }
            // End of modification

            $insert_stmt = $conn2->prepare("INSERT INTO biometrics_data (emp_id, punchdate, punchtime, punchstate, verify_mode) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssss", $employee_id, $current_date, $current_time, $punchstate, $verify_mode);

            if ($insert_stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'gender' => $user['gender'],
                    'employeeid' => $employee_id,
                    'punch_status' => $punch_status_message . ' Successful'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error: Could not log time.']);
            }
            $insert_stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Unknown face.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unknown face.']);
    }

    $conn2->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
