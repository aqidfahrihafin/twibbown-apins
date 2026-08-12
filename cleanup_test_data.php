<?php
// Include database connection
include 'config.php';

// Delete test entries
try {
    $stmt = $pdo->prepare("DELETE FROM twibbons WHERE title = ? OR title = ?");
    $stmt->execute(['tesr', 'Test Template']);
    
    echo "Test data cleaned up successfully!\n";
    
    // Fetch all templates
    $stmt = $pdo->query("SELECT * FROM twibbons");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current templates in database:\n";
    foreach ($templates as $template) {
        echo "- " . $template['title'] . " (" . $template['template_image'] . ")\n";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}