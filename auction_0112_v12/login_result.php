<?php
// Modified login script with relative redirects and session role flags.

session_start();
require_once 'db_connection.php';

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect back to index in current directory
    header('Location: index.php');
    exit();
}

// Get form data
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

// Basic validation
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email.";
}
if ($password === '') {
    $errors[] = "Please enter your password.";
}

if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    header('Location: index.php');
    exit();
}

try {
    // Look up user
    $stmt = $pdo->prepare("SELECT user_id, email, password_hash, username, role FROM User WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validate email and password
    if (!$user) {
        $_SESSION['login_errors'] = ["Email does not exist."];
        header('Location: index.php');
        exit();
    }

    if (!password_verify($password, $user['password_hash'])) {
        $_SESSION['login_errors'] = ["Incorrect password."];
        header('Location: index.php');
        exit();
    }

    // Login success → set session
    $_SESSION['logged_in']    = true;
    $_SESSION['user_id']      = $user['user_id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['account_type'] = $user['role']; // e.g. 'buyer' or 'seller'
    // Set a numeric is_seller flag for older code compatibility: 1 for sellers, 0 for buyers
    $_SESSION['is_seller']    = ($user['role'] === 'seller') ? 1 : 0;

    // Success message with auto-redirect after 3 seconds
    echo "<div style='text-align:center; margin-top:50px; font-size:18px;'>\n";
    echo "Login successful! <br><br>\n";
    echo "Welcome back, <strong>{$_SESSION['username']}</strong><br><br>\n";
    echo "Redirecting to your page...\n";
    echo "</div>";
    // Redirect after 3 seconds to index in current directory
    header("refresh:3;url=index.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['system_errors'] = ["Database error: " . $e->getMessage()];
    header('Location: index.php');
    exit();
}

?>