<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $img = $_POST['webcam_image'];
    $img = str_replace('data:image/jpeg;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    file_put_contents('uploads/webcam.jpg', $data);

    $python = '"C:\\Program Files\\Python311\\python.exe"';  // Note the quotes for path with spaces
    $script = "compare_faces.py";
    $image = "uploads/webcam.jpg";

    $command = "$python $script $image 2>&1";
    $output = shell_exec($command);

    echo nl2br($output ?: "❌ Python script did not return any output.");
}
?>
