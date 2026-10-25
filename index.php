<?php
require "auth.php";
require "db.php";
require "functions.php";

require_login();

$userId = $_SESSION["userId"];

// Search and filter values arrive with GET, so a filtered view has its own URL.
$search = isset($_GET["q"]) ? trim($_GET["q"]) : "";
$categoryId = isset($_GET["category"]) ? (int) $_GET["category"] : 0;
$view = isset($_GET["view"]) ? $_GET["view"] : "all";

$views = [
    "all" => "All notices",
    "unread" => "Unread",
    "pending" => "Tasks to acknowledge",
    "acknowledged" => "Acknowledged",
];

if (!isset($views[$view])) {
    $view = "all";
}

// Build the WHERE clause from the chosen filters. Every user-supplied value
// goes through a placeholder; only fixed SQL text is joined into $sql.
$conditions = ["n.is_active = 1"];
$params = [":userId" => $userId];

if ($search !== "") {
    $conditions[] = "(n.title LIKE :searchTitle OR n.body LIKE :searchBody)";
    $params[":searchTitle"] = "%" . $search . "%";
    $params[":searchBody"] = "%" . $search . "%";
}

if ($categoryId > 0) {
    $conditions[] = "n.category_id = :categoryId";
    $params[":categoryId"] = $categoryId;
}

if ($view === "unread") {
    $conditions[] = "s.read_at IS NULL";
} elseif ($view === "pending") {
    $conditions[] = "n.requires_ack = 1 AND s.acknowledged_at IS NULL";
} elseif ($view === "acknowledged") {
    $conditions[] = "s.acknowledged_at IS NOT NULL";
}

// Pending tasks are listed by due date (earliest first); everything else newest first.
if ($view === "pending") {
    $orderBy = "n.due_date IS NULL, n.due_date, n.created_at DESC";
} else {
    $orderBy = "n.created_at DESC";
}

$sql = "SELECT n.notice_id, n.title, n.body, n.due_date, n.requires_ack, n.created_at,
               c.name AS category, u.full_name AS posted_by,
               s.read_at, s.acknowledged_at
        FROM notices n
        JOIN categories c ON n.category_id = c.category_id
        JOIN users u ON n.posted_by = u.user_id
        LEFT JOIN notice_reads s ON s.notice_id = n.notice_id AND s.user_id = :userId
        WHERE " . implode(" AND ", $conditions) . "
        ORDER BY " . $orderBy;

$statement = $pdo->prepare($sql);
$statement->execute($params);
$notices = $statement->fetchAll();

$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY category_id")->fetchAll();
$summary = get_summary($pdo, $userId);
$isFiltered = $search !== "" || $categoryId > 0 || $view !== "all";

$pageTitle = "Notice Board";
$activePage = "board";
require "header.php";
?>
    <main class="page-container">
        <h1>Notice Board</h1>

        <section class="summary" aria-label="Your summary">
            <a class="summary-tile" href="index.php">
                <span class="summary-number" id="summary-total"><?php echo $summary["total"]; ?></span>
                <span class="summary-label">Active notices</span>
            </a>
            <a class="summary-tile tile-unread" href="index.php?view=unread">
                <span class="summary-number" id="summary-unread"><?php echo $summary["unread"]; ?></span>
                <span class="summary-label">Unread</span>
            </a>
            <a class="summary-tile tile-pending" href="index.php?view=pending">
                <span class="summary-number" id="summary-pending"><?php echo $summary["pending"]; ?></span>
                <span class="summary-label">Tasks to acknowledge</span>
            </a>
            <a class="summary-tile tile-overdue" href="index.php?view=pending">
                <span class="summary-number" id="summary-overdue"><?php echo $summary["overdue"]; ?></span>
                <span class="summary-label">Overdue tasks</span>
            </a>
        </section>

        <form id="filter-form" class="filter-bar" action="index.php" method="get">
            <div class="filter-field filter-search">
                <label for="search">Search</label>
                <input type="text" id="search" name="q" maxlength="100"
                       placeholder="Words in the title or text"
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="filter-field">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="0">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category["category_id"]; ?>"
                            <?php echo (int) $category["category_id"] === $categoryId ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($category["name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field">
                <label for="view">Show</label>
                <select id="view" name="view">
                    <?php foreach ($views as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $value === $view ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field filter-buttons">
                <button type="submit" class="button-primary">Search</button>
                <a href="index.php" class="button-link">Clear</a>
            </div>
        </form>

        <p class="result-count">
            <?php
            echo count($notices) === 1 ? "1 notice" : count($notices) . " notices";
            echo $isFiltered ? " match the selected filters." : " on the board.";
            ?>
        </p>

        <p id="board-message" class="status-message" aria-live="polite"></p>

        <?php if (count($notices) === 0): ?>
            <p class="empty-message">No notices to show. Try another search or <a href="index.php">clear the filters</a>.</p>
        <?php endif; ?>

        <div class="notice-list">
            <?php foreach ($notices as $notice): ?>
                <?php
                list($stateText, $stateClass) = describe_state($notice["read_at"], $notice["acknowledged_at"]);
                $isUnread = $notice["read_at"] === null;
                $needsAck = $notice["requires_ack"] == 1 && $notice["acknowledged_at"] === null;
                ?>
                <article class="notice-card <?php echo $isUnread ? "is-unread" : ""; ?>" id="notice-<?php echo $notice["notice_id"]; ?>">
                    <div class="notice-tags">
                        <span class="badge badge-<?php echo strtolower(htmlspecialchars($notice["category"])); ?>">
                            <?php echo htmlspecialchars($notice["category"]); ?>
                        </span>
                        <?php if ($notice["requires_ack"] == 1): ?>
                            <span class="badge badge-task">Task</span>
                        <?php endif; ?>
                        <?php if ($notice["due_date"] !== null): ?>
                            <?php list($dueText, $dueClass) = describe_due_date($notice["due_date"]); ?>
                            <span class="due-date <?php echo $notice["acknowledged_at"] !== null ? "due-done" : $dueClass; ?>">
                                <?php echo htmlspecialchars($dueText); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h2 class="notice-title"><?php echo htmlspecialchars($notice["title"]); ?></h2>
                    <p class="notice-body"><?php echo nl2br(htmlspecialchars($notice["body"])); ?></p>
                    <p class="notice-meta">
                        Posted by <?php echo htmlspecialchars($notice["posted_by"]); ?>
                        on <?php echo date("d M Y, h:i A", strtotime($notice["created_at"])); ?>
                    </p>

                    <div class="notice-actions">
                        <span class="state <?php echo $stateClass; ?>"><?php echo htmlspecialchars($stateText); ?></span>

                        <?php if ($isUnread): ?>
                            <button type="button" class="status-button mark-read-button"
                                    data-notice-id="<?php echo $notice["notice_id"]; ?>" data-action="read">Mark as read</button>
                        <?php endif; ?>
                        <?php if ($needsAck): ?>
                            <button type="button" class="status-button ack-button"
                                    data-notice-id="<?php echo $notice["notice_id"]; ?>" data-action="acknowledge">Acknowledge</button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

<?php require "footer.php"; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/board.js"></script>
</body>
</html>
