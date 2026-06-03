<?php
/**
 * EasyPass Installation Script
 * This script helps set up the bus pass management system
 */

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('System is already installed. Delete config/installed.lock to reinstall.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 1) {
        // Database configuration
        $host = $_POST['db_host'] ?? 'localhost';
        $dbname = $_POST['db_name'] ?? 'bus_pass_db';
        $username = $_POST['db_user'] ?? 'root';
        $password = $_POST['db_pass'] ?? '';
        
        // Test database connection
        try {
            $conn = new mysqli($host, $username, $password);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Create database if it doesn't exist
            $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
            $conn->select_db($dbname);
            
            // Save configuration
            $config = [
                'db_host' => $host,
                'db_name' => $dbname,
                'db_user' => $username,
                'db_pass' => $password,
                'installed_at' => date('Y-m-d H:i:s')
            ];
            
            // Create config directory
            if (!is_dir('config')) {
                mkdir('config', 0755, true);
            }
            
            file_put_contents('config/database.php', '<?php return ' . var_export($config, true) . ';');
            file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
            
            $success = 'Database configuration saved successfully!';
            $step = 2;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    
    elseif ($step == 2) {
        // Admin account creation
        $admin_username = $_POST['admin_username'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_confirm = $_POST['admin_confirm'] ?? '';
        
        if (empty($admin_username) || empty($admin_email) || empty($admin_password)) {
            $error = 'All fields are required.';
        } elseif ($admin_password !== $admin_confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($admin_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                // Load database configuration
                $config = include 'config/database.php';
                $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
                
                if ($conn->connect_error) {
                    throw new Exception("Connection failed: " . $conn->connect_error);
                }
                
                // Create tables
                include 'db_connect.php';
                
                // Create admin user
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, email, full_name, phone, password, is_active, created_at) 
                        VALUES (?, ?, ?, ?, ?, 1, NOW())";
                $stmt = $conn->prepare($sql);
                $admin_name = 'System Administrator';
                $admin_phone = '0000000000';
                $stmt->bind_param("sssss", $admin_username, $admin_email, $admin_name, $admin_phone, $hashed_password);
                
                if ($stmt->execute()) {
                    $success = 'Admin account created successfully! Installation complete.';
                    $step = 3;
                } else {
                    $error = 'Failed to create admin account.';
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyPass Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .install-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            margin: 20px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #1a73e8;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: white;
        }
        
        .step.active {
            background: #1a73e8;
        }
        
        .step.completed {
            background: #34a853;
        }
        
        .step.pending {
            background: #ddd;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1a73e8;
        }
        
        .btn {
            background: #1a73e8;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .btn:hover {
            background: #1557b0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .step-content h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .step-content p {
            color: #666;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            text-align: center;
            padding: 20px;
            background: #d4edda;
            border-radius: 5px;
            color: #155724;
        }
        
        .success-message h3 {
            margin-bottom: 10px;
        }
        
        .success-message a {
            color: #1a73e8;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <h1><i class="fas fa-bus"></i> EasyPass</h1>
            <p>Bus Pass Management System</p>
        </div>
        
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending'; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending'; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? 'active' : 'pending'; ?>">3</div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
            <div class="step-content">
                <h2>Database Configuration</h2>
                <p>Please provide your database connection details.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" name="db_host" id="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" name="db_name" id="db_name" value="bus_pass_db" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Database Username</label>
                        <input type="text" name="db_user" id="db_user" value="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Database Password</label>
                        <input type="password" name="db_pass" id="db_pass">
                    </div>
                    
                    <button type="submit" class="btn">Test Connection & Continue</button>
                </form>
            </div>
            
        <?php elseif ($step == 2): ?>
            <div class="step-content">
                <h2>Create Admin Account</h2>
                <p>Create an administrator account for system management.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="admin_username">Admin Username</label>
                        <input type="text" name="admin_username" id="admin_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Admin Email</label>
                        <input type="email" name="admin_email" id="admin_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Admin Password</label>
                        <input type="password" name="admin_password" id="admin_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_confirm">Confirm Password</label>
                        <input type="password" name="admin_confirm" id="admin_confirm" required>
                    </div>
                    
                    <button type="submit" class="btn">Create Admin Account</button>
                </form>
            </div>
            
        <?php elseif ($step == 3): ?>
            <div class="step-content">
                <div class="success-message">
                    <h3>Installation Complete!</h3>
                    <p>Your EasyPass system has been successfully installed.</p>
                    <p><a href="index.php">Go to Homepage</a> | <a href="login.php">Admin Login</a></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Password confirmation validation
        document.getElementById('admin_confirm')?.addEventListener('input', function() {
            const password = document.getElementById('admin_password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
