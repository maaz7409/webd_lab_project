<?php
// Shared top of every logged-in page. The including page sets $pageTitle and $activePage.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?> | Department Notice Board</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="index.php">Department Notice &amp; Task Board</a>

            <nav class="main-nav">
                <a href="index.php" class="<?php echo $activePage === "board" ? "active" : ""; ?>">Notice Board</a>
                <?php if (is_staff()): ?>
                    <a href="manage.php" class="<?php echo $activePage === "manage" ? "active" : ""; ?>">Manage Notices</a>
                <?php endif; ?>
                <span class="user-info">
                    <?php echo htmlspecialchars($_SESSION["fullName"]); ?>
                    (<?php echo htmlspecialchars($_SESSION["loginId"]); ?>, <?php echo htmlspecialchars($_SESSION["role"]); ?>)
                </span>
                <a href="logout.php" class="logout-link">Log Out</a>
            </nav>
        </div>
    </header>

    <!-- Read by the JavaScript files and sent with every fetch request that changes data -->
    <input type="hidden" id="csrf-token" value="<?php echo htmlspecialchars($_SESSION["token"]); ?>">
