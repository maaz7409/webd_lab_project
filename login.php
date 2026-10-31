<?php
require "auth.php";

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$flash = take_flash();
$lastLoginId = isset($_SESSION["lastLoginId"]) ? $_SESSION["lastLoginId"] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log In | Department Notice Board</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h1>Department Notice &amp; Task Board</h1>
        <p class="subtitle">Log in with your admission number or staff ID.</p>

        <?php if ($flash !== null): ?>
            <p class="flash flash-<?php echo htmlspecialchars($flash["type"]); ?>">
                <?php echo htmlspecialchars($flash["text"]); ?>
            </p>
        <?php endif; ?>

        <form id="login-form" action="process_login.php" method="post">
            <label for="login-id">Admission Number / Staff ID</label>
            <input type="text" id="login-id" name="loginId" maxlength="20"
                   value="<?php echo htmlspecialchars($lastLoginId); ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password">

            <button type="submit" class="button-primary">Log In</button>
        </form>

        <p id="login-message" class="form-message is-error" aria-live="polite"></p>

        <div class="demo-accounts">
            <h2>Demo accounts</h2>
            <p>Password: the login ID followed by <code>@123</code> (for example <code>office@123</code>)</p>
            <ul>
                <li><code>24JE0831</code>, <code>24JE0873</code>, <code>24JE0560</code>, <code>24JE1033</code>: Group 16 students</li>
                <li><code>office</code>, <code>labcoord</code>: department staff (can manage notices)</li>
            </ul>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/login.js"></script>
</body>
</html>
