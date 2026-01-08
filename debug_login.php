<!DOCTYPE html>
<html>
<head>
    <title>Login Debug Tool</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #00f2ff; }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .info { color: #ffff00; }
        pre { background: #0f1a2e; padding: 15px; border-left: 3px solid #00f2ff; margin: 10px 0; }
        button { background: #00f2ff; color: #000; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; }
        button:hover { background: #00d4dd; }
    </style>
</head>
<body>
    <h1>🔐 KMC RC Login Debug Tool</h1>
    
    <form method="POST">
        <h3>Test Login</h3>
        <input type="email" name="test_email" placeholder="Email" value="admin@kmcrc.edu.np" style="padding: 10px; width: 300px;"><br><br>
        <input type="password" name="test_password" placeholder="Password" value="Admin@123" style="padding: 10px; width: 300px;"><br><br>
        <button type="submit" name="test_login">Test Login</button>
    </form>

    <hr>

<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Security.php';
require_once __DIR__ . '/includes/Auth.php';

if (isset($_POST['test_login'])) {
    echo "<h2>📊 Login Test Results</h2>";
    
    $email = $_POST['test_email'];
    $password = $_POST['test_password'];
    
    echo "<h3>1. Database Connection</h3>";
    try {
        $db = Database::getInstance();
        echo "<pre class='success'>✅ Database connected successfully</pre>";
    } catch (Exception $e) {
        echo "<pre class='error'>❌ Database connection failed: " . $e->getMessage() . "</pre>";
        exit;
    }
    
    echo "<h3>2. User Lookup</h3>";
    $user = $db->fetchOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    if ($user) {
        echo "<pre class='success'>✅ User found</pre>";
        echo "<pre>Name: " . $user['name'] . "\nEmail: " . $user['email'] . "\nRole: " . $user['role'] . "\nStatus: " . $user['status'] . "\nHash Length: " . strlen($user['password_hash']) . "</pre>";
    } else {
        echo "<pre class='error'>❌ User not found with email: $email</pre>";
        exit;
    }
    
    echo "<h3>3. Account Status Check</h3>";
    if ($user['status'] === 'active') {
        echo "<pre class='success'>✅ Account is active</pre>";
    } elseif ($user['status'] === 'inactive') {
        echo "<pre class='error'>❌ Account is INACTIVE - login will be blocked</pre>";
    } elseif ($user['status'] === 'pending') {
        echo "<pre class='info'>⚠️ Account is PENDING - might be blocked depending on code</pre>";
    }
    
    echo "<h3>4. Password Verification</h3>";
    if (password_verify($password, $user['password_hash'])) {
        echo "<pre class='success'>✅ Password is CORRECT</pre>";
    } else {
        echo "<pre class='error'>❌ Password is INCORRECT</pre>";
        exit;
    }
    
    echo "<h3>5. Session Test</h3>";
    Security::startSession();
    echo "<pre class='success'>✅ Session started: " . session_id() . "</pre>";
    
    echo "<h3>6. Full Login Attempt</h3>";
    try {
        $auth = new Auth();
        $result = $auth->login($email, $password, false);
        
        if ($result['success']) {
            echo "<pre class='success'>✅✅✅ LOGIN SUCCESSFUL! ✅✅✅</pre>";
            echo "<pre>Message: " . $result['message'] . "</pre>";
            echo "<pre>User ID: " . $result['user']['id'] . "\nName: " . $result['user']['name'] . "\nEmail: " . $result['user']['email'] . "\nRole: " . $result['user']['role'] . "</pre>";
            
            // Check session
            echo "<h3>7. Session Variables</h3>";
            echo "<pre>";
            echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
            echo "user_name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
            echo "user_email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "\n";
            echo "user_role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";
            echo "</pre>";
            
            echo "<h3>8. Redirect Test</h3>";
            $redirect = Security::isAdmin() ? 'admin/dashboard.php' : 'member/dashboard.php';
            echo "<pre class='success'>Should redirect to: <a href='$redirect' style='color: #00ff00;'>$redirect</a></pre>";
            
            echo "<h2 class='success'>🎉 Everything Works! You should be able to login now.</h2>";
            echo "<p><a href='auth/login.php' style='color: #00f2ff; font-size: 20px;'>Go to Login Page →</a></p>";
            
        } else {
            echo "<pre class='error'>❌ LOGIN FAILED</pre>";
            echo "<pre class='error'>Error Message: " . $result['message'] . "</pre>";
        }
    } catch (Exception $e) {
        echo "<pre class='error'>❌ Exception during login: " . $e->getMessage() . "</pre>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}
?>

</body>
</html>
