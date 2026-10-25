<?php
// Activates or deactivates a notice (staff only).
// Request (JSON):  { "noticeId": 3, "active": false, "token": "..." }
// Response (JSON): { "ok": true, "message": "...", "noticeId": 3, "active": false }
require "../auth.php";
require "../db.php";
require "../functions.php";

$data = read_api_request();

if (!is_staff()) {
    send_json(403, ["ok" => false, "message" => "Only department staff can change notices."]);
}

$noticeId = isset($data["noticeId"]) ? (int) $data["noticeId"] : 0;

if ($noticeId <= 0 || !isset($data["active"]) || !is_bool($data["active"])) {
    send_json(400, ["ok" => false, "message" => "A notice and its new status are required."]);
}

$active = $data["active"];

$statement = $pdo->prepare("UPDATE notices SET is_active = :active WHERE notice_id = :noticeId");
$statement->execute([":active" => $active ? 1 : 0, ":noticeId" => $noticeId]);

// rowCount() is 0 both when the notice does not exist and when it already had
// this status, so check which one it was.
if ($statement->rowCount() === 0) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM notices WHERE notice_id = :noticeId");
    $check->execute([":noticeId" => $noticeId]);

    if ((int) $check->fetchColumn() === 0) {
        send_json(404, ["ok" => false, "message" => "That notice does not exist."]);
    }
}

send_json(200, [
    "ok" => true,
    "message" => $active ? "Notice activated. Students can see it again." : "Notice deactivated. It is hidden from the board.",
    "noticeId" => $noticeId,
    "active" => $active,
]);
?>
