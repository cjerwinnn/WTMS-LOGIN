<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $webcamData = $_POST['webcam_image'];
    $data = explode(',', $webcamData);
    $imageData = base64_decode($data[1]);

    $webcamPath = 'uploads/webcam.jpg';
    $referencePath = 'uploads/reference.jpg';

    // Save the captured image
    file_put_contents($webcamPath, $imageData);
    echo "<h3>✅ Webcam image saved.</h3>";

    // Check if reference image exists
    if (!file_exists($referencePath)) {
        echo "<h3>❌ Reference image not found. Please upload it first.</h3>";
        exit;
    }

    // Run Python face comparison
    $command = escapeshellcmd("python3 compare_faces.py $referencePath $webcamPath");
    $output = shell_exec($command);

    echo "<h3>🧠 Face Comparison Result:</h3>";
    echo "<pre>$output</pre>";

    // Optional: show both images
    echo "<h3>🔍 Images Compared:</h3>";
    echo "<div style='display:flex;gap:20px'>";
    echo "<div><strong>Reference:</strong><br><img src='$referencePath' width='200'></div>";
    echo "<div><strong>Webcam:</strong><br><img src='$webcamPath' width='200'></div>";
    echo "</div>";
}
?>
