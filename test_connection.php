<?php
// test_connection.php - Test database connection
require_once 'config/connect.php';

echo "<h2>🔍 Database Connection Test</h2>";
echo "<hr>";

try {
    // Test basic connection
    echo "<h3>1. Testing Basic Connection:</h3>";
    $pdo = db();
    echo "✅ <strong>SUCCESS:</strong> Connected to database successfully!<br><br>";
    
    // Test database info
    echo "<h3>2. Database Information:</h3>";
    $stmt = $pdo->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
    $info = $stmt->fetch();
    echo "📁 Current Database: <strong>" . $info['current_db'] . "</strong><br>";
    echo "🗄️ MySQL Version: <strong>" . $info['mysql_version'] . "</strong><br><br>";
    
    // Test server info
    echo "<h3>3. Connection Details:</h3>";
    global $DB_CONFIG;
    echo "🌐 Host: <strong>" . $DB_CONFIG['host'] . ":" . $DB_CONFIG['port'] . "</strong><br>";
    echo "👤 Username: <strong>" . $DB_CONFIG['username'] . "</strong><br>";
    echo "🔤 Charset: <strong>" . $DB_CONFIG['charset'] . "</strong><br><br>";
    
    // Test if tables exist
    echo "<h3>4. Checking Tables:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tables)) {
        echo "📋 <strong>Found " . count($tables) . " tables:</strong><br>";
        foreach ($tables as $table) {
            echo "• " . $table . "<br>";
        }
        
        // Check specific required tables
        $required_tables = ['users', 'students', 'branches'];
        echo "<br><strong>Required tables check:</strong><br>";
        foreach ($required_tables as $table) {
            if (in_array($table, $tables)) {
                echo "✅ " . $table . " - EXISTS<br>";
            } else {
                echo "❌ " . $table . " - MISSING<br>";
            }
        }
    } else {
        echo "⚠️ <strong>No tables found.</strong> You need to import the database schema first!<br>";
        echo "<strong>Instructions:</strong><br>";
        echo "1. Open phpMyAdmin (http://localhost/phpmyadmin)<br>";
        echo "2. Select 'origin_driving' database<br>";
        echo "3. Click 'Import' tab<br>";
        echo "4. Upload your origin_driving_schema.sql file<br>";
    }
    
    echo "<br><h3>5. Testing Sample Query:</h3>";
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = DATABASE()");
    $result = $stmt->fetch();
    echo "📊 Total tables in database: <strong>" . $result['total'] . "</strong><br>";
    
} catch (PDOException $e) {
    echo "<h3>❌ <strong>CONNECTION FAILED!</strong></h3>";
    echo "<div style='background:#fee; padding:10px; border:1px solid #f00; border-radius:5px;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
    echo "</div>";
    
    echo "<br><h3>🔧 <strong>Troubleshooting Tips:</strong></h3>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Check if port 3308 is correct (usually 3306)</li>";
    echo "<li>Verify database 'origin_driving' exists</li>";
    echo "<li>Check username/password in config</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ <strong>UNEXPECTED ERROR:</strong> " . htmlspecialchars($e->getMessage());
}

echo "<hr>";
echo "<p><strong>Note:</strong> Delete this file after testing for security!</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; margin-top: 20px; }
</style>