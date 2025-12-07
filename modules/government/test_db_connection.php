<?php
// test_db_connection.php
echo "<h2>Testing Database Connection</h2>";

$configPath = __DIR__ . '/../../config/database.php';
echo "<p>Config path: $configPath</p>";
echo "<p>Config exists: " . (file_exists($configPath) ? 'YES' : 'NO') . "</p>";

if (file_exists($configPath)) {
    require_once $configPath;
    echo "<p>✅ Config file loaded</p>";
    
    // Test Database class
    if (class_exists('Database')) {
        echo "<p>✅ Class Database found</p>";
        
        try {
            $db = Database::getInstance();
            echo "<p>✅ Database instance created</p>";
            
            // Test connection
            $conn = $db->getConnection();
            echo "<p>✅ Connection object: " . get_class($conn) . "</p>";
            
            // Test query
            $result = $conn->query("SELECT 1 as test");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "<p>✅ Query test successful: " . $row['test'] . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Query failed: " . $conn->error . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Class Database NOT found in config file</p>";
    }
}
?>