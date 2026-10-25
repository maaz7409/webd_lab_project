<?php
require "auth.php";
require "db.php";
require "functions.php";

require_staff();

$flash = take_flash();

// If add_notice.php rejected the form, it saved the typed values so they can be shown again.
$old = isset($_SESSION["oldNotice"]) ? $_SESSION["oldNotice"] : [
    "title" => "",
    "categoryId" => 0,
    "body" => "",
    "dueDate" => "",
    "requiresAck" => false,
];
unset($_SESSION["oldNotice"]);

$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY category_id")->fetchAll();
$studentCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

$notices = $pdo->query(
    "SELECT n.notice_id, n.title, n.due_date, n.requires_ack, n.is_active, n.created_at,
            c.name AS category, u.full_name AS posted_by,
            (SELECT COUNT(*) FROM notice_reads s JOIN users r ON r.user_id = s.user_id
             WHERE s.notice_id = n.notice_id AND r.role = 'student' AND s.read_at IS NOT NULL) AS read_count,
            (SELECT COUNT(*) FROM notice_reads s JOIN users r ON r.user_id = s.user_id
             WHERE s.notice_id = n.notice_id AND r.role = 'student' AND s.acknowledged_at IS NOT NULL) AS ack_count
     FROM notices n
     JOIN categories c ON n.category_id = c.category_id
     JOIN users u ON n.posted_by = u.user_id
     ORDER BY n.is_active DESC, n.created_at DESC"
)->fetchAll();

$pageTitle = "Manage Notices";
$activePage = "manage";
require "header.php";
?>
    <main class="page-container">
        <h1>Manage Notices</h1>

        <?php if ($flash !== null): ?>
            <p class="flash flash-<?php echo htmlspecialchars($flash["type"]); ?>">
                <?php echo htmlspecialchars($flash["text"]); ?>
            </p>
        <?php endif; ?>

        <section class="panel">
            <h2>Add a Notice</h2>

            <form id="notice-form" action="add_notice.php" method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION["token"]); ?>">

                <label for="title">Title <span id="title-count" class="char-count"></span></label>
                <input type="text" id="title" name="title" maxlength="150"
                       value="<?php echo htmlspecialchars($old["title"]); ?>">

                <label for="category-id">Category</label>
                <select id="category-id" name="categoryId">
                    <option value="">Choose a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category["category_id"]; ?>"
                            <?php echo (int) $category["category_id"] === (int) $old["categoryId"] ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($category["name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="body">Notice text</label>
                <textarea id="body" name="body" rows="5"><?php echo htmlspecialchars($old["body"]); ?></textarea>

                <div class="task-options">
                    <label class="checkbox-label" for="requires-ack">
                        <input type="checkbox" id="requires-ack" name="requiresAck" value="1"
                            <?php echo $old["requiresAck"] ? "checked" : ""; ?>>
                        Students must acknowledge this notice (make it a task)
                    </label>

                    <label for="due-date">Due date (optional, tasks only)</label>
                    <input type="date" id="due-date" name="dueDate" min="<?php echo date("Y-m-d"); ?>"
                           value="<?php echo htmlspecialchars($old["dueDate"]); ?>">
                </div>

                <button type="submit" class="button-primary">Publish Notice</button>
            </form>

            <p id="notice-form-message" class="form-message" aria-live="polite"></p>
        </section>

        <section class="panel">
            <h2>All Notices</h2>
            <p class="panel-help">
                Deactivating a notice removes it from the board without deleting it or the students' read records.
                Read and acknowledgement counts are out of <?php echo $studentCount; ?> students.
            </p>

            <div class="table-filter">
                <label for="status-filter">Show</label>
                <select id="status-filter">
                    <option value="all">All notices</option>
                    <option value="active">Active only</option>
                    <option value="inactive">Inactive only</option>
                </select>
            </div>

            <p id="manage-message" class="status-message" aria-live="polite"></p>

            <div class="table-wrapper">
                <table class="notice-table">
                    <thead>
                        <tr>
                            <th>Notice</th>
                            <th>Posted</th>
                            <th>Due</th>
                            <th>Read by</th>
                            <th>Acknowledged by</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notices as $notice): ?>
                            <?php $isActive = $notice["is_active"] == 1; ?>
                            <tr id="row-<?php echo $notice["notice_id"]; ?>"
                                class="notice-row <?php echo $isActive ? "" : "row-inactive"; ?>"
                                data-active="<?php echo $isActive ? "1" : "0"; ?>">
                                <td>
                                    <span class="badge badge-<?php echo strtolower(htmlspecialchars($notice["category"])); ?>">
                                        <?php echo htmlspecialchars($notice["category"]); ?>
                                    </span>
                                    <?php if ($notice["requires_ack"] == 1): ?>
                                        <span class="badge badge-task">Task</span>
                                    <?php endif; ?>
                                    <span class="table-title"><?php echo htmlspecialchars($notice["title"]); ?></span>
                                </td>
                                <td>
                                    <?php echo date("d M Y", strtotime($notice["created_at"])); ?><br>
                                    <span class="muted"><?php echo htmlspecialchars($notice["posted_by"]); ?></span>
                                </td>
                                <td><?php echo $notice["due_date"] !== null ? date("d M Y", strtotime($notice["due_date"])) : "&ndash;"; ?></td>
                                <td><?php echo $notice["read_count"]; ?> / <?php echo $studentCount; ?></td>
                                <td>
                                    <?php echo $notice["requires_ack"] == 1 ? $notice["ack_count"] . " / " . $studentCount : "&ndash;"; ?>
                                </td>
                                <td>
                                    <span class="status-pill <?php echo $isActive ? "status-active" : "status-inactive"; ?>">
                                        <?php echo $isActive ? "Active" : "Inactive"; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="toggle-button"
                                            data-notice-id="<?php echo $notice["notice_id"]; ?>">
                                        <?php echo $isActive ? "Deactivate" : "Activate"; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

<?php require "footer.php"; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/manage.js"></script>
</body>
</html>
