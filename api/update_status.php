<?php
// Records that the logged-in user has read or acknowledged a notice.
// Request (JSON):  { "noticeId": 3, "action": "read" | "acknowledge", "token": "..." }
// Response (JSON): { "ok": true, "message": "...", "isRead": true, "isAcknowledged": false,
//                    "stateText": "Read", "stateClass": "state-read", "summary": { ... } }
require "../auth.php";
require "../db.php";
require "../functions.php";

$data = read_api_request();

$userId = $_SESSION["userId"];
$noticeId = isset($data["noticeId"]) ? (int) $data["noticeId"] : 0;
$action = isset($data["action"]) ? $data["action"] : "";

if ($noticeId <= 0 || ($action !== "read" && $action !== "acknowledge")) {
    send_json(400, ["ok" => false, "message" => "A notice and a valid action are required."]);
}

$statement = $pdo->prepare("SELECT requires_ack FROM notices WHERE notice_id = :noticeId AND is_active = 1");
$statement->execute([":noticeId" => $noticeId]);
$notice = $statement->fetch();

if ($notice === false) {
    send_json(404, ["ok" => false, "message" => "This notice is no longer on the board."]);
}

if ($action === "acknowledge" && $notice["requires_ack"] != 1) {
    send_json(400, ["ok" => false, "message" => "This notice does not need an acknowledgement."]);
}

// The primary key (user_id, notice_id) allows only one row per user and notice.
// ON DUPLICATE KEY UPDATE changes that row instead of adding a second one, and
// COALESCE keeps the first recorded time if the request is repeated.
if ($action === "read") {
    $sql = "INSERT INTO notice_reads (user_id, notice_id, read_at)
            VALUES (:userId, :noticeId, NOW())
            ON DUPLICATE KEY UPDATE read_at = COALESCE(read_at, NOW())";
} else {
    // Acknowledging a notice also marks it as read.
    $sql = "INSERT INTO notice_reads (user_id, notice_id, read_at, acknowledged_at)
            VALUES (:userId, :noticeId, NOW(), NOW())
            ON DUPLICATE KEY UPDATE read_at = COALESCE(read_at, NOW()),
                                    acknowledged_at = COALESCE(acknowledged_at, NOW())";
}

$statement = $pdo->prepare($sql);
$statement->execute([":userId" => $userId, ":noticeId" => $noticeId]);

$statement = $pdo->prepare(
    "SELECT read_at, acknowledged_at FROM notice_reads WHERE user_id = :userId AND notice_id = :noticeId"
);
$statement->execute([":userId" => $userId, ":noticeId" => $noticeId]);
$state = $statement->fetch();

list($stateText, $stateClass) = describe_state($state["read_at"], $state["acknowledged_at"]);

send_json(200, [
    "ok" => true,
    "message" => $action === "read" ? "Notice marked as read." : "Notice acknowledged.",
    "isRead" => $state["read_at"] !== null,
    "isAcknowledged" => $state["acknowledged_at"] !== null,
    "stateText" => $stateText,
    "stateClass" => $stateClass,
    "summary" => get_summary($pdo, $userId),
]);
?>
