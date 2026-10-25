<?php
require "auth.php";
require "db.php";

require_staff();

$title = isset($_POST["title"]) ? trim($_POST["title"]) : "";
$categoryId = isset($_POST["categoryId"]) ? (int) $_POST["categoryId"] : 0;
$body = isset($_POST["body"]) ? trim($_POST["body"]) : "";
$dueDate = isset($_POST["dueDate"]) ? trim($_POST["dueDate"]) : "";
$requiresAck = isset($_POST["requiresAck"]);
$token = isset($_POST["token"]) ? $_POST["token"] : "";

// Send the user back to the form with a message and the values they typed.
function reject($text, $title, $categoryId, $body, $dueDate, $requiresAck) {
    set_flash("error", $text);
    $_SESSION["oldNotice"] = [
        "title" => $title,
        "categoryId" => $categoryId,
        "body" => $body,
        "dueDate" => $dueDate,
        "requiresAck" => $requiresAck,
    ];
    header("Location: manage.php");
    exit;
}

if (!token_is_valid($token)) {
    reject("The form has expired. Please submit it again.", $title, $categoryId, $body, $dueDate, $requiresAck);
}

// The browser has already checked these in manage.js, but a request can
// bypass JavaScript, so the server checks them again.
if ($title === "" || $categoryId <= 0 || $body === "") {
    reject("Title, category and notice text are required.", $title, $categoryId, $body, $dueDate, $requiresAck);
}

if (mb_strlen($title) > 150) {
    reject("The title can be at most 150 characters long.", $title, $categoryId, $body, $dueDate, $requiresAck);
}

if ($dueDate !== "") {
    $parsedDate = DateTime::createFromFormat("Y-m-d", $dueDate);

    if ($parsedDate === false || $parsedDate->format("Y-m-d") !== $dueDate) {
        reject("The due date is not a valid date.", $title, $categoryId, $body, $dueDate, $requiresAck);
    }

    if ($dueDate < date("Y-m-d")) {
        reject("The due date cannot be in the past.", $title, $categoryId, $body, $dueDate, $requiresAck);
    }

    if (!$requiresAck) {
        reject("A due date can only be set for a task. Tick the acknowledgement box or clear the date.",
            $title, $categoryId, $body, $dueDate, $requiresAck);
    }
}

$sql = "INSERT INTO notices (title, body, category_id, posted_by, due_date, requires_ack)
        VALUES (:title, :body, :categoryId, :postedBy, :dueDate, :requiresAck)";

try {
    $statement = $pdo->prepare($sql);
    $statement->execute([
        ":title" => $title,
        ":body" => $body,
        ":categoryId" => $categoryId,
        ":postedBy" => $_SESSION["userId"],
        ":dueDate" => $dueDate === "" ? null : $dueDate,
        ":requiresAck" => $requiresAck ? 1 : 0,
    ]);
} catch (PDOException $e) {
    // A category_id that does not exist is rejected by the foreign key.
    reject("The notice could not be saved. Check the category and try again.",
        $title, $categoryId, $body, $dueDate, $requiresAck);
}

set_flash("success", "Notice \"" . $title . "\" has been published.");
header("Location: manage.php");
exit;
?>
