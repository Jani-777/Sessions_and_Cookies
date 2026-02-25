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

function saveUsers($users) {
    $lines = [];
    foreach ($users as $u) {
        $lines[] = $u['username'] . '|' . $u['email'] . '|' . $u['password'];
    }
    file_put_contents('users_data.txt', implode("\n", $lines) . "\n");
}

$errors = [];
$success = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$username) $errors[] = 'Username required.';
    if (!$email)    $errors[] = 'Email required.';
    if (!$password) $errors[] = 'Password required.';

    if ($username && (strlen($username) < 3 || strlen($username) > 20)) $errors[] = 'Username must be 3-20 chars.';
    if ($password && strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';

    $users = getUsers();
    foreach ($users as $u) {
        if ($u['username'] === $username) $errors[] = 'Username already exists.';
        if ($u['email'] === $email) $errors[] = 'Email already taken.';
    }

    if (!$errors) {
        $users[] = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
        saveUsers($users);
        $success = 'Sign up successful! <a href="login.php">Login now</a>.';
        $username = '';
        $email = '';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign up</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        label {
            display: block;
        }
    </style>
</head>
<body>
<div class="form-container">
<h2>Sign Up</h2>
<form method="post">
    <label>Username: <br>
        <input type="text" name="username" value="<?=htmlspecialchars($username)?>">
    </label> 
    <br>
    <label>Email: <br>
        <input type="text" name="email" value="<?=htmlspecialchars($email)?>">
    </label>
    <br>
    <label>Password: <br>
        <input type="password" name="password">
    </label>
    <br>
    <label>Confirm Password: <br>
        <input type="password" name="confirm_password">
    </label>
    <br>
    <button type="submit">Sign Up</button>
</form>
<br>
<p>Already registered? <a href="login.php">Login here</a></p>
</div>
<?php foreach($errors as $e) echo "<p class='error' style='color:red;'>$e</p>"; ?>
<?php if ($success) echo "<p style='color:green;font-weight:500;'>$success</p>"; ?>
</body>
</html>