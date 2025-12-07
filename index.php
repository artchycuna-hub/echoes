<?php
session_start();
include 'connection.php';

// If user is already logged in, redirect to appropriate page
if (isset($_SESSION['username'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: game.php");
    }
    exit();
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $login_type = $_POST['login_type'];

    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password!";
    } else {
        // Use prepared statement to prevent SQL injection
        $sql = "SELECT id, username, password_hash, is_admin FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $row['password_hash'])) {
                    // Check admin privileges if admin login is attempted
                    if ($login_type == 'admin' && $row['is_admin'] != 1) {
                        $error_message = "Admin access denied! This account is not an administrator.";
                    } else {
                        // Set session variables
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['is_admin'] = $row['is_admin'];
                        
                        // Update last login
                        $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("i", $row['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Redirect based on user type
                        if ($row['is_admin'] == 1) {
                            header("Location: admin/dashboard.php");
                        } else {
                            header("Location: game.php");
                        }
                        exit();
                    }
                } else {
                    $error_message = "Invalid username or password!";
                }
            } else {
                $error_message = "Invalid username or password!";
            }
            $stmt->close();
        } else {
            $error_message = "Database error!";
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Echoes of Memories</title>
    <!-- Add Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        .video-container {
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .login-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2rem;
        }

        .login-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .login-container input, .login-container select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .login-container input:focus, .login-container select:focus {
            border-color: #667eea;
            outline: none;
        }

        .login-container button[type="submit"] {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .login-container button[type="submit"]:hover {
            transform: translateY(-2px);
        }

        .login-container a {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .login-container a:hover {
            text-decoration: underline;
        }

        /* Password input container */
        .password-container {
            position: relative;
            margin-bottom: 20px;
        }

        .password-container label {
            margin-bottom: 8px;
        }

        .password-container input {
            width: 100%;
            padding: 12px 50px 12px 15px;
            margin-bottom: 0;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 70%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            padding: 5px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: #333;
            background-color: rgba(0,0,0,0.05);
            border-radius: 50%;
        }

        .toggle-password:focus {
            outline: none;
        }

        /* Improve form spacing */
        .form-group {
            margin-bottom: 20px;
        }

        .demo-accounts {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }

        .demo-accounts h3 {
            margin-top: 0;
            color: #333;
            font-size: 16px;
        }

        .demo-accounts p {
            margin: 5px 0;
            color: #666;
        }

        .login-type {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .login-type-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .login-type-option.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .login-type-option input {
            display: none;
        }

        .admin-login-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #856404;
            display: none;
        }
    </style>
</head>
<body>
    <div class="video-container">
        <video autoplay loop muted>
            <source src="Neon.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <div class="login-container">
        <form action="index.php" method="POST">
            <h1><i class="fas fa-sign-in-alt"></i> Echoes of Memories</h1>
            
            <!-- Login Type Selection -->
            <div class="login-type">
                <label class="login-type-option active" id="user-option">
                    <input type="radio" name="login_type" value="user" checked> 
                    <i class="fas fa-user"></i> User Login
                </label>
                <label class="login-type-option" id="admin-option">
                    <input type="radio" name="login_type" value="admin"> 
                    <i class="fas fa-user-shield"></i> Admin Login
                </label>
            </div>

            <div id="admin-note" class="admin-login-note">
                <i class="fas fa-exclamation-triangle"></i> 
                Admin access requires administrator privileges.
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required placeholder="Enter your username">
            </div>

            <div class="password-container">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required placeholder="Enter your password">
                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit" name="submit">Log In</button>
            <a href="register.php">Don't have an account? Sign up here</a>

          
        </form>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle eye icon
            const icon = this.querySelector('i');
            if (type === 'password') {
                icon.className = 'fas fa-eye';
            } else {
                icon.className = 'fas fa-eye-slash';
            }
        });

        // Login type selection
        const userOption = document.getElementById('user-option');
        const adminOption = document.getElementById('admin-option');
        const adminNote = document.getElementById('admin-note');

        userOption.addEventListener('click', function() {
            userOption.classList.add('active');
            adminOption.classList.remove('active');
            document.querySelector('input[name="login_type"][value="user"]').checked = true;
            adminNote.style.display = 'none';
        });

        adminOption.addEventListener('click', function() {
            adminOption.classList.add('active');
            userOption.classList.remove('active');
            document.querySelector('input[name="login_type"][value="admin"]').checked = true;
            adminNote.style.display = 'block';
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in both username and password!');
            }
        });
    </script>
</body>
</html>