<?php
// Include your database connection settings
include 'config/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Step 1: Process the webcam image (same as before) ---
    $img = $_POST['webcam_image'];
    $img = str_replace('data:image/jpeg;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    $image_path = 'uploads/webcam.jpg';
    file_put_contents($image_path, $data);

    // --- Step 2: Run the Python script to get the Employee ID ---
    $python = '"C:\\Program Files\\Python311\\python.exe"'; // Path to your Python executable
    $script = "compare_faces.py";
    
    $command = "$python $script $image_path 2>&1";
    $output = shell_exec($command);
    
    // Clean up the output to get just the employee ID
    $employee_id = trim($output);

    // --- Step 3: Look up the Employee ID in the database ---
    if ($employee_id && $employee_id !== "Unknown face." && $employee_id !== "Face not detected on database.") {
        
        // Prepare the SQL statement to prevent SQL injection
        $stmt = $conn2->prepare("SELECT employeeid, firstname, lastname FROM employee_general WHERE employeeid = ?");
        $stmt->bind_param("s", $employee_id); // "s" means the parameter is a string
        
        // Execute the query
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch the user's data
            $user = $result->fetch_assoc();

            $employee_id = $user['employeeid'];
            $firstname = $user['firstname'];
            $lastname = $user['lastname'];

            // Output the full name and employee ID
            echo "[$employee_id] Name: $firstname $lastname\n";
        } else {
            // If the employee ID from the folder name isn't in the database
            echo "Unknown face.";
        }
        
        // Close the statement
        $stmt->close();

    } else {
        // If the Python script didn't recognize a face
        echo "Unknown face.";
    }
    
    // Close the database connection
    $conn2->close();

} else {
    echo "Invalid request method.";
}
?>