<?php
// Helpers shared by the board, the management page and the JSON endpoints.

// Counts shown in the summary panel for one user.
function get_summary($pdo, $userId) {
    $statement = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                SUM(s.read_at IS NULL) AS unread,
                SUM(n.requires_ack = 1 AND s.acknowledged_at IS NULL) AS pending,
                SUM(n.requires_ack = 1 AND s.acknowledged_at IS NULL AND n.due_date < :today) AS overdue
         FROM notices n
         LEFT JOIN notice_reads s ON s.notice_id = n.notice_id AND s.user_id = :userId
         WHERE n.is_active = 1"
    );
    $statement->execute([":today" => date("Y-m-d"), ":userId" => $userId]);
    $row = $statement->fetch();

    return [
        "total" => (int) $row["total"],
        "unread" => (int) $row["unread"],
        "pending" => (int) $row["pending"],
        "overdue" => (int) $row["overdue"],
    ];
}

// Describes a due date relative to today, e.g. "Due 28 Oct 2026 (in 3 days)".
// Returns the text and a CSS class: due-later, due-soon or due-overdue.
function describe_due_date($dueDate) {
    $today = new DateTime(date("Y-m-d"));
    $due = new DateTime($dueDate);
    $days = (int) $today->diff($due)->format("%r%a");
    $label = "Due " . $due->format("d M Y");

    if ($days < 0) {
        $overdueBy = -$days;
        return [$label . " (overdue by " . $overdueBy . ($overdueBy === 1 ? " day)" : " days)"), "due-overdue"];
    } elseif ($days === 0) {
        return [$label . " (today)", "due-soon"];
    } elseif ($days <= 3) {
        return [$label . " (in " . $days . ($days === 1 ? " day)" : " days)"), "due-soon"];
    }

    return [$label . " (in " . $days . " days)", "due-later"];
}

// The current user's state for one notice: the text shown on the card and a CSS class.
function describe_state($readAt, $acknowledgedAt) {
    if ($acknowledgedAt !== null) {
        return ["Acknowledged on " . date("d M Y, h:i A", strtotime($acknowledgedAt)), "state-acknowledged"];
    } elseif ($readAt !== null) {
        return ["Read", "state-read"];
    }

    return ["Unread", "state-unread"];
}

// Used by the JSON endpoints in the api folder.
function send_json($statusCode, $data) {
    http_response_code($statusCode);
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}

// Checks a request sent by fetch and returns its JSON body as a PHP array.
// These are the same checks as the pages make, but every failure is
// answered with JSON instead of a redirect.
function read_api_request() {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        send_json(405, ["ok" => false, "message" => "Use a POST request."]);
    }

    if (!is_logged_in()) {
        send_json(401, ["ok" => false, "message" => "Your session has ended. Please log in again."]);
    }

    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {
        send_json(400, ["ok" => false, "message" => "The request body must be JSON."]);
    }

    if (!token_is_valid(isset($data["token"]) ? $data["token"] : "")) {
        send_json(403, ["ok" => false, "message" => "Invalid request token. Reload the page and try again."]);
    }

    return $data;
}
?>
