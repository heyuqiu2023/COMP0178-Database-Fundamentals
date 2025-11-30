<?php
session_start();
require_once 'db_connection.php';

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auction_system/register.php');
    exit();
}

// 1. 获取表单数据
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['passwordConfirmation'] ?? '';
$username = trim($_POST['username'] ?? '');
$role = $_POST['accountType'] ?? '';

$errors = [];

// 2. 基本验证
if (empty($email)) {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if (empty($username)) {
    $errors[] = "Username is required.";
}

if (empty($password)) {
    $errors[] = "Password is required.";
} elseif (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters.";
}

if ($password !== $password_confirm) {
    $errors[] = "Passwords do not match.";
}

if ($role !== 'buyer' && $role !== 'seller') {
    $errors[] = "Please select an account type.";
}

// 3. 如果有验证错误，返回注册页面
if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old'] = [
        'email' => $email,
        'username' => $username,
        'accountType' => $role
    ];
    header('Location: /auction_system/register.php');
    exit();
}

try {
    // 4. 检查邮箱是否已存在
    $stmt = $pdo->prepare("SELECT user_id FROM User WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['register_errors'] = ["This email is already registered."];
        $_SESSION['register_old'] = [
            'email' => $email,
            'username' => $username,
            'accountType' => $role
        ];
        header('Location: /auction_system/register.php');
        exit();
    } // ← 添加这个括号

    // 5. 检查用户名是否已存在
    $stmt = $pdo->prepare("SELECT user_id FROM User WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['register_errors'] = ["This username is already taken."];
        $_SESSION['register_old'] = [
            'email' => $email,
            'username' => $username,
            'accountType' => $role
        ];
        header('Location: /auction_system/register.php');
        exit();
    } 

    // 6. 插入新用户
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO User (email, password_hash, username, role) 
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$email, $password_hash, $username, $role]);

    // 7. 注册成功
    $_SESSION['register_success'] = "Registration successful! Please log in.";
    header('Location: /auction_system/index.php');
    exit();
    
}

catch (PDOException $e) {
    $_SESSION['register_old'] = [
        'email' => $email,
        'username' => $username,
        'accountType' => $role
    ];
    header('Location: /auction_system/register.php');
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <title>Registration Successful</title>
</head>
<body>
  <div class="container">
    <h2 class="my-3">Registration Successful</h2>
    <p>Thank you for registering, <?php echo htmlspecialchars($username); ?>.</p>
    <p>You are now logged in as a <?php echo htmlspecialchars($role); ?>.  You will be redirected to the browse page shortly.</p>
    <p>If you are not redirected automatically, <a href="browse.php">click here</a>.</p>
  </div>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>

