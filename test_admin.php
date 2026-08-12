<?php
// Include database connection
include 'config.php';

// Test inserting a template
try {
    $stmt = $pdo->prepare("INSERT INTO twibbons (title, description, template_image) VALUES (?, ?, ?)");
    $stmt->execute([
        'Test Template', 
        'This is a test template', 
        'sample_template.png'
    ]);
    echo "Test template inserted successfully!\n";
    
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