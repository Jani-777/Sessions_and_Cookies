<?php
session_start();

function getUsers() {
    if (file_exists('users_data.txt')) {
        $lines = file('users_data.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $users = [];
        foreach ($lines as $line) {
            [$username, $email, $password] = explode('|', $line);
            $users[] = compact('username', 'email', 'password');
        }
        return $users;
    }
    return [];
}

$errors = [];
$username = $_COOKIE['remember_username'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!$username || !$password) $errors[] = 'Username/email and password are required.';

    $users = getUsers();
    $found = false;
    foreach ($users as $u) {
        if (($u['username'] === $username || $u['email'] === $username) && password_verify($password, $u['password'])) {
            $found = true;
            $_SESSION['username'] = $u['username'];
            if ($remember) {
                setcookie('remember_username', $u['username'], time() + 3600, '/');
            } else {
                setcookie('remember_username', '', time() - 3600, '/');
            }
            header('Location: dashboard.php');
            exit;
        }
    }
    if (!$found) $errors[] = 'Invalid credentials.';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        label {
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
<div class="form-container">
<h2>Login</h2>
<form method="post">
    <label>Username or Email: <br>
        <input type="text" name="username" value="<?=htmlspecialchars($username)?>">
    </label>
    <label>Password: <br>
        <input type="password" name="password">
    </label>
    <label style="display: flex; align-items: center;">
        <input type="checkbox" name="remember" <?=isset($_COOKIE['remember_username'])?'checked':''?> style="width: 18px; margin-right:7px;">
        Remember me
    </label>
    <button type="submit">Login</button>
</form>
<p style="margin-top:12px;"><a href="signup.php">Don't have an account? Sign up</a></p>
</div>
<?php foreach($errors as $e) echo "<p class='error' style='color:red;'>$e</p>"; ?>
</body>
</html>