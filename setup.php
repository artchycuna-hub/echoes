<?php
// Test script to verify database and user setup
include 'connection.php';

echo "<h1>Database Setup Test</h1>";

// Test database connection
if ($conn) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Users table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Users table missing</p>";
    }
    
    // Test if categories table exists
    $result = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Categories table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Categories table missing</p>";
    }
    
    // Test if quiz_sessions table exists
    $result = $conn->query("SHOW TABLES LIKE 'quiz_sessions'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Quiz sessions table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Quiz sessions table missing</p>";
    }
    
    // Check if admin user exists
    $result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Admin user exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Admin user missing</p>";
    }
    
    // List all users
    echo "<h2>Current Users:</h2>";
    $result = $conn->query("SELECT id, username, is_admin, created_at FROM users");
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Is Admin</th><th>Created</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . ($row['is_admin'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
}

$conn->close();
?>
<p><a href="index.php">Go to Login Page</a> | <a href="register.php">Go to Registration Page</a></p>
