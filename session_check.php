<?php
// session_check.php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Add user_id to session if not set
if (!isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    include 'connection.php';
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
    }
    $stmt->close();
    $conn->close();
}
?>