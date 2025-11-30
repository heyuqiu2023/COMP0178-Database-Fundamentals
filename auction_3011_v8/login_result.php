<?php
session_start();
require_once 'db_connection.php';

// 只允许 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auction_system/index.php');
    exit();
}

// 1. 取表单数据
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

// 2. 基本验证
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email.";
}
if ($password === '') {
    $errors[] = "Please enter your password.";
}

if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    header('Location: /auction_system/index.php');
    exit();
}

try {
    // 3. 查找用户
    $stmt = $pdo->prepare("SELECT user_id, email, password_hash, username, role FROM User WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. 验证邮箱是否存在 + 密码是否正确
    if (!$user) {
        $_SESSION['login_errors'] = ["Email does not exist."];
        header('Location: /auction_system/index.php');
        exit();
    }

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_errors'] = ["Incorrect password."];
        header('Location: /auction_system/index.php');
        exit();
    }

    // 5. 登录成功 → 设置 session
    $_SESSION['logged_in']    = true;
    $_SESSION['user_id']      = $user['user_id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['account_type'] = $user['role']; // buyer / seller

    // 登录成功提示
    echo "<div style='text-align:center; margin-top:50px; font-size:18px;'>
            Login successful! <br><br>
            Welcome back, <strong>{$user['username']}</strong><br><br>
            Redirecting to your page...
          </div>";
    // 3秒后自动跳回
    header("refresh:3;url=/auction_system/index.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['system_errors'] = ["Database error: " . $e->getMessage()];
    header('Location: /auction_system/index.php');
    exit();
}

?>
