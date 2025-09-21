<?php
// test_create_student.php - Test createStudent function
require_once 'config/connect.php';
require_once 'includes/functions.php';

echo "<h2>🧪 Test createStudent() Function</h2>";
echo "<hr>";

// Test data
$test_data = [
    'first_name' => 'Test',
    'last_name' => 'Student',
    'email' => 'test.student' . time() . '@example.com', // Unique email
    'phone' => '0400123456',
    'dob' => '2000-01-01',
    'license_number' => 'L123456',
    'address' => '123 Test Street, Melbourne'
];

echo "<h3>1. Test Data:</h3>";
echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>2. Calling createStudent():</h3>";

try {
    $result = createStudent($test_data);
    
    if ($result['success']) {
        echo "✅ <strong>SUCCESS!</strong> Student created with user_id: " . $result['user_id'] . "<br><br>";
        
        // Verify in database
        echo "<h3>3. Verification - Check Database:</h3>";
        $pdo = db();
        
        // Check users table
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$result['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "👤 <strong>User Record Found:</strong><br>";
            echo "• ID: " . $user['id'] . "<br>";
            echo "• Name: " . $user['name'] . "<br>";
            echo "• Email: " . $user['email'] . "<br>";
            echo "• Role: " . $user['role'] . "<br>";
            echo "• Status: " . $user['status'] . "<br><br>";
        } else {
            echo "❌ User record NOT found in database!<br><br>";
        }
        
        // Check students table
        $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
        $stmt->execute([$result['user_id']]);
        $student = $stmt->fetch();
        
        if ($student) {
            echo "🎓 <strong>Student Record Found:</strong><br>";
            echo "• User ID: " . $student['user_id'] . "<br>";
            echo "• License Status: " . $student['license_status'] . "<br>";
            echo "• Notes: " . nl2br(htmlspecialchars($student['notes_summary'])) . "<br>";
        } else {
            echo "❌ Student record NOT found in database!<br>";
        }
        
    } else {
        echo "❌ <strong>FAILED!</strong> Error: " . $result['error'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>EXCEPTION:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>4. Check Error Log:</h3>";
echo "<p>Look for debug messages in your PHP error log or XAMPP logs folder.</p>";

echo "<h3>5. Troubleshooting Checklist:</h3>";
echo "<ul>";
echo "<li>✅ Database connection working</li>";
echo "<li>❓ Tables 'users' and 'students' exist</li>";
echo "<li>❓ Branch with id=1 exists</li>";
echo "<li>❓ No validation errors</li>";
echo "<li>❓ Function actually being called</li>";
echo "</ul>";

// Check if branch_id=1 exists
try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = 1");
    $stmt->execute();
    $branch = $stmt->fetch();
    
    echo "<h3>6. Branch Check:</h3>";
    if ($branch) {
        echo "✅ Branch ID=1 exists: " . $branch['name'] . "<br>";
    } else {
        echo "❌ <strong>PROBLEM:</strong> Branch ID=1 does not exist!<br>";
        echo "You need to insert a branch record first.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking branch: " . $e->getMessage() . "<br>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; margin-top: 20px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; }
</style>