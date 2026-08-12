<?php
// Include database connection
include 'config.php';

// Fetch all twibbon templates from database
$stmt = $pdo->query("SELECT * FROM twibbons");
$twibbons = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Twibbon Templates in Database:\n";
echo "================================\n";

if (count($twibbons) > 0) {
    foreach ($twibbons as $twibbon) {
        echo "ID: " . $twibbon['id'] . "\n";
        echo "Title: " . $twibbon['title'] . "\n";
        echo "Description: " . $twibbon['description'] . "\n";
        echo "Template Image: " . $twibbon['template_image'] . "\n";
        echo "Created At: " . $twibbon['created_at'] . "\n";
        echo "------------------------\n";
    }
} else {
    echo "No templates found in database.\n";
}