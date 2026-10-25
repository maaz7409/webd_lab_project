# How the Department Notice and Task Board Works

A step-by-step explanation in simple language.

NCSM301 Web Development Laboratory, Mini Project P9, Group 16

---

## Contents

1. [What the project does](#1-what-the-project-does)
2. [The big picture: four parts working together](#2-the-big-picture-four-parts-working-together)
3. [The files and what each one is for](#3-the-files-and-what-each-one-is-for)
4. [The database](#4-the-database)
5. [Connecting PHP to the database (`db.php`)](#5-connecting-php-to-the-database-dbphp)
6. [Logging in and sessions](#6-logging-in-and-sessions)
7. [The notice board page (`index.php`)](#7-the-notice-board-page-indexphp)
8. [Mark as read and Acknowledge (fetch + JSON)](#8-mark-as-read-and-acknowledge-fetch--json)
9. [The management page (`manage.php`)](#9-the-management-page-managephp)
10. [Adding a notice (normal form + POST)](#10-adding-a-notice-normal-form--post)
11. [Activating and deactivating a notice (fetch + JSON)](#11-activating-and-deactivating-a-notice-fetch--json)
12. [Where jQuery is used](#12-where-jquery-is-used)
13. [How the page is styled (`styles.css`)](#13-how-the-page-is-styled-stylescss)
14. [Security: how the project protects itself](#14-security-how-the-project-protects-itself)
15. [Complete journey of one click](#15-complete-journey-of-one-click)
16. [Likely viva questions and answers](#16-likely-viva-questions-and-answers)
17. [Glossary](#17-glossary)

---

## 1. What the project does

A department puts many notices on its notice board: exam schedules, lab submissions, placement
talks, events and general information. Students often miss them, and the department has no way to
know who has seen what.

This project is an online notice board that solves that problem.

**Students can:**

- See all current notices, each with a coloured category label: **Academic**, **Laboratory**,
  **Event**, **Placement** or **General**.
- **Search** notices by words and **filter** them by category.
- Click **Mark as read** on a notice, so it no longer shows as "New".
- Click **Acknowledge** on notices that are **tasks**, to confirm they have done what was asked
  (for example "Submit Lab 10 record files").
- See a summary at the top: how many notices are unread, how many tasks are still pending and how
  many are overdue.

**Department staff can:**

- Add new notices. A notice can be made a **task** with an optional **due date**.
- **Deactivate** a notice (hide it from students) or **activate** it again.
- See how many students have read and acknowledged each notice.

The problem statement asks for "at least one operation using fetch and JSON". This project has two:
**Mark as read / Acknowledge**, and **Activate / Deactivate**. Both work without reloading the page.

---

## 2. The big picture: four parts working together

```
   YOUR COMPUTER                              THE SERVER (XAMPP)
 ┌──────────────────────┐   HTTP request   ┌────────────┐      ┌────────┐      ┌──────────┐
 │       Browser        │ ───────────────> │   Apache   │ ───> │  PHP   │ ───> │ MariaDB  │
 │ HTML + CSS + JS +    │                  │ web server │      │ program│ SQL  │ database │
 │ jQuery               │ <─────────────── │  port 8080 │ <─── │        │ <─── │ port 3307│
 └──────────────────────┘  HTML or JSON    └────────────┘      └────────┘      └──────────┘
```

| Part | Where it runs | Its job in this project |
|---|---|---|
| **HTML** | Browser | The structure: headings, forms, buttons, notice cards, tables |
| **CSS** | Browser | The look: colours, spacing, badges, layout on phones |
| **JavaScript + jQuery** | Browser | The behaviour: checking forms, sending fetch requests, changing the page without reloading |
| **Apache** | Server | Receives requests from the browser and hands `.php` files to PHP |
| **PHP** | Server | Checks who is logged in, validates data, talks to the database, builds HTML or JSON |
| **MariaDB** | Server | Stores everything permanently: users, notices, who read what |

**Important idea (from Lab 9):** the browser never sees PHP code. PHP runs on the server and sends
back only the result: an HTML page, or JSON data for fetch requests.

**Two kinds of requests are used in this project:**

1. **Normal page requests.** You open a page or submit a form and the **whole page reloads**. Used
   for logging in, the board with its filters, and adding a notice.
2. **fetch requests.** JavaScript quietly sends a small JSON message to the server and gets JSON
   back, then **changes only part of the page**. Used for Mark as read, Acknowledge and
   Activate/Deactivate.

---

## 3. The files and what each one is for

```
notice_board/
├── setup.sql            Creates the database, tables and demo data (run once in phpMyAdmin)
├── db.php               Opens the connection to MariaDB (used by every PHP file)
├── auth.php             Starts the session; "is the user logged in?" and "is the user staff?" checks
├── functions.php        Small helper functions used by several files
├── header.php           The top bar of every page (title, menu, user name, Log Out)
├── footer.php           The bottom line of every page
├── login.php            The login form
├── process_login.php    Checks the ID and password and logs the user in
├── logout.php           Logs the user out
├── index.php            The notice board (student view) with search and filters
├── manage.php           Staff page: "Add a Notice" form + table of all notices
├── add_notice.php       Saves a new notice sent from the form on manage.php
├── api/
│   ├── update_status.php   Answers fetch requests for "Mark as read" / "Acknowledge"
│   └── toggle_notice.php   Answers fetch requests for "Activate" / "Deactivate"
├── js/
│   ├── common.js        postJson(): the shared function that sends fetch requests
│   ├── board.js         JavaScript for the notice board page
│   ├── manage.js        JavaScript for the management page
│   └── login.js         Checks the login form before it is sent
└── styles.css           All the styling
```

**Why are the `api` files separate?** Normal pages send back HTML for people to look at. The `api`
files send back **JSON** for JavaScript to read. Keeping them in their own folder makes that clear.

**Why do `header.php` and `footer.php` exist?** The top bar is the same on every page. Writing it
once and using `require "header.php";` avoids copying the same HTML into every file.

---

## 4. The database

The database is called `notice_board` and has **four tables**. `setup.sql` creates them.

### 4.1 `users`: who can log in

| user_id | login_id | full_name | password_hash | role |
|---|---|---|---|---|
| 1 | office | Department Office | $2y$10$IqP... | staff |
| 2 | labcoord | Lab Coordinator | $2y$10$IqP... | staff |
| 3 | 24JE9001 | Riya Verma | $2y$10$IqP... | student |

- `login_id` is `UNIQUE`, so two users cannot have the same ID.
- `password_hash` is **not** the password itself. It is a scrambled version made by PHP's
  `password_hash()` (see [Security](#14-security-how-the-project-protects-itself)).
- `role` is either `student` or `staff`. A `CHECK` rule makes sure nothing else can be stored.

### 4.2 `categories`: the five categories

| category_id | name |
|---|---|
| 1 | Academic |
| 2 | Laboratory |
| 3 | Event |
| 4 | Placement |
| 5 | General |

Each category name is stored only once. Notices refer to a category by its number.

### 4.3 `notices`: the notices themselves

| notice_id | title | category_id | posted_by | due_date | requires_ack | is_active |
|---|---|---|---|---|---|---|
| 1 | Submit Lab 10 record files | 2 | 2 | 2026-10-28 | 1 | 1 |
| 2 | Mid-semester answer script display | 1 | 1 | NULL | 0 | 1 |
| 10 | Old holiday notice | 5 | 1 | NULL | 0 | 0 |

The table also has `body` (the full text) and `created_at` (when it was posted).

- `category_id` is a **foreign key** to `categories`. MariaDB refuses a notice whose category does
  not exist.
- `posted_by` is a **foreign key** to `users`. It records which staff member posted it.
- `requires_ack = 1` means this notice is a **task**: students must acknowledge it.
- `due_date` is the last date for a task. `NULL` means no due date.
- `is_active = 0` means the notice is **deactivated** (hidden from students). Nothing is deleted.

### 4.4 `notice_reads`: who has read or acknowledged what

| user_id | notice_id | read_at | acknowledged_at |
|---|---|---|---|
| 3 | 2 | 2026-10-24 11:42 | NULL |
| 3 | 7 | 2026-10-20 11:42 | 2026-10-20 11:42 |

Read the first row as: "User 3 (Riya) read notice 2 at 11:42 but has not acknowledged it."

The meaning of each row depends on which columns are filled in:

| Row exists? | read_at | acknowledged_at | Meaning |
|---|---|---|---|
| No | – | – | **Unread** |
| Yes | filled | NULL | **Read** |
| Yes | filled | filled | **Acknowledged** |

**The primary key is the pair `(user_id, notice_id)`.** This means the database itself allows
**only one row for each student and notice**. Even if a button is clicked twice, there can never be
two rows for the same pair.

### 4.5 How the tables are linked

```
 users ───────────┐                ┌─────────── categories
   │ user_id      │ posted_by      │ category_id
   │              ▼                ▼
   │            notices ◄──────────┘
   │              │ notice_id
   ▼              ▼
   notice_reads (user_id, notice_id)
```

### 4.6 Demo data that stays fresh

The due dates in `setup.sql` are written like `CURDATE() + INTERVAL 3 DAY`, meaning "3 days after
the day the script is run". So whenever you set up the project, there is always one task due soon,
one due later and one already overdue for the demo.

---

## 5. Connecting PHP to the database (`db.php`)

This file is the same as the one in the Extra Lab:

```php
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // errors become exceptions we can catch
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // rows come back as $row["title"]
    PDO::ATTR_EMULATE_PREPARES => false,               // use real prepared statements
]);
```

- It connects to MariaDB on `127.0.0.1`, port `3307`, user `root` with an empty password, exactly
  as set up in the lab.
- Every other PHP file writes `require "db.php";` and can then use `$pdo`.
- If the connection fails, it prints "Could not connect to the database." and stops. It does not
  show the technical error, because that could reveal settings.

**Two extra lines about time:**

```php
date_default_timezone_set("Asia/Kolkata");   // PHP's date() uses Indian time
$pdo->exec("SET time_zone = '+05:30'");      // MariaDB's NOW() also uses Indian time
```

Without these lines, PHP and MariaDB might use different clocks. A notice could then look
"overdue" when it is not, or show the wrong time.

---

## 6. Logging in and sessions

### 6.1 Why sessions are needed

HTTP has no memory (Lab 10). Each request is separate, so when you open `index.php` the server does
not automatically know you logged in a moment ago. A **session** fixes this:

1. PHP stores data about you **on the server** (in `$_SESSION`).
2. The browser keeps only a **session ID** in a cookie and sends it with every request.
3. On every page, `session_start()` uses that ID to find your data again.

### 6.2 `auth.php`: included at the top of every page

```php
session_start();                      // start or resume the session

function is_logged_in() { ... }       // is $_SESSION["userId"] set?
function is_staff() { ... }           // is the role "staff"?
function require_login() { ... }      // if not logged in, go to login.php
function require_staff() { ... }      // if not staff, show "Only department staff..." (403)
```

So a page protects itself with a single line:

```php
require_login();   // in index.php
require_staff();   // in manage.php and add_notice.php
```

`auth.php` also has two small functions for **flash messages**: one-time messages such as
"Notice published." that appear on the next page after a redirect.

- `set_flash("success", "...")` saves the message in the session.
- `take_flash()` reads it **and deletes it**, so it is shown only once.

### 6.3 The login flow step by step

```
login.php  ──(POST: loginId, password)──>  process_login.php
                                             │
                    wrong ID/password ◄──────┤ checks database
                 (flash error, back to       │
                  login.php)                 │ correct
                                             ▼
                                   saves user in $_SESSION
                                   redirects to index.php
```

1. **`login.php`** shows a form with `method="post"` and `action="process_login.php"`.
2. Before sending, **`js/login.js`** checks that both boxes are filled. If one is empty, it calls
   `event.preventDefault()` to stop the form and shows a message (as in Lab 7).
3. **`process_login.php`** receives the data in `$_POST`:
   - It **checks again** that the fields are not empty. JavaScript checks can be skipped, so the
     server must check too (Lab 10).
   - It finds the user with a **prepared statement**:
     `SELECT ... FROM users WHERE login_id = :loginId`
   - It checks the password with `password_verify($password, $user["password_hash"])`.
   - If anything is wrong, it saves an error flash message and sends the browser back to
     `login.php`. The typed ID is remembered so the user does not have to type it again.
   - If everything is right, it:
     - calls `session_regenerate_id(true)` to get a fresh session ID (a safety step),
     - stores `userId`, `loginId`, `fullName` and `role` in `$_SESSION`,
     - creates a random **token** (`$_SESSION["token"]`, explained in
       [Security](#14-security-how-the-project-protects-itself)),
     - redirects to `index.php`.

### 6.4 Logging out (`logout.php`)

Exactly as in Lab 10:

```php
session_start();
session_unset();      // remove all session variables
session_destroy();    // end the session on the server
header("Location: login.php");
exit;
```

---

## 7. The notice board page (`index.php`)

This is the main page students use. Here is what happens when it is opened.

### 7.1 Reading the filters from the URL (GET)

Search and filtering use **GET**, so the choices appear in the URL, for example:

```
index.php?q=lab&category=2&view=pending
```

Lab 10 teaches that GET suits viewing, searching and filtering. It also means a filtered view can be
bookmarked or shared.

```php
$search = isset($_GET["q"]) ? trim($_GET["q"]) : "";
$categoryId = isset($_GET["category"]) ? (int) $_GET["category"] : 0;
$view = isset($_GET["view"]) ? $_GET["view"] : "all";
```

- `(int)` turns the category into a whole number. Junk such as `abc` becomes `0`, which means "all
  categories".
- `view` must be one of `all`, `unread`, `pending` or `acknowledged`. Anything else is replaced by
  `all`.

### 7.2 Building the SQL query from the filters

The query starts with one condition, "only active notices", and adds more depending on what the user
chose:

```php
$conditions = ["n.is_active = 1"];

if ($search !== "")    → add "(n.title LIKE :searchTitle OR n.body LIKE :searchBody)"
if ($categoryId > 0)   → add "n.category_id = :categoryId"
if view is "unread"    → add "s.read_at IS NULL"
if view is "pending"   → add "n.requires_ack = 1 AND s.acknowledged_at IS NULL"
if view is "acknowledged" → add "s.acknowledged_at IS NOT NULL"
```

All the conditions are joined with `AND`. **The user's typed text is never put directly into the
SQL.** It always goes through a placeholder such as `:searchTitle`. The `%` signs around the search
word mean "anything before or after", so searching `lab` also matches "Laboratory".

> **Why `:searchTitle` and `:searchBody` instead of one placeholder used twice?**
> With real prepared statements (`ATTR_EMULATE_PREPARES => false`), each named placeholder can be
> used only once in a query. So the same value is given twice under two names.

### 7.3 The main query, explained

```sql
SELECT n.notice_id, n.title, n.body, n.due_date, n.requires_ack, n.created_at,
       c.name AS category, u.full_name AS posted_by,
       s.read_at, s.acknowledged_at
FROM notices n
JOIN categories c ON n.category_id = c.category_id
JOIN users u ON n.posted_by = u.user_id
LEFT JOIN notice_reads s ON s.notice_id = n.notice_id AND s.user_id = :userId
WHERE ...conditions...
ORDER BY ...
```

- **`JOIN categories`** replaces the category number (2) with its name ("Laboratory").
- **`JOIN users`** gives the name of the staff member who posted the notice.
- **`LEFT JOIN notice_reads`** looks for the **current user's** read record for each notice.
  - A normal `JOIN` would drop notices that have no read record, which are exactly the unread ones!
  - A **`LEFT JOIN`** keeps every notice. When there is no matching record, `read_at` and
    `acknowledged_at` simply come back as `NULL`, which means **unread**.
- **Order:** normally the newest notice is first. In the "Tasks to acknowledge" view, the task with
  the **nearest due date comes first**, so the most urgent work is at the top.

### 7.4 The summary tiles

The four numbers at the top (Active notices, Unread, Tasks to acknowledge, Overdue tasks) come from
`get_summary()` in `functions.php`:

```sql
SELECT COUNT(*) AS total,
       SUM(s.read_at IS NULL) AS unread,
       SUM(n.requires_ack = 1 AND s.acknowledged_at IS NULL) AS pending,
       SUM(n.requires_ack = 1 AND s.acknowledged_at IS NULL AND n.due_date < :today) AS overdue
FROM notices n
LEFT JOIN notice_reads s ON ... AND s.user_id = :userId
WHERE n.is_active = 1
```

**The trick:** in MariaDB a condition such as `s.read_at IS NULL` gives `1` when true and `0` when
false. So `SUM(condition)` **counts how many rows match**. One query gives all four numbers.

Each tile is also a link. For example, clicking "Unread" opens `index.php?view=unread`.

### 7.5 Showing each notice card

PHP loops over the results with `foreach ... endforeach` (the alternative syntax from the Extra
Lab) and prints one `<article>` per notice:

```html
<article class="notice-card is-unread" id="notice-6">
    <span class="badge badge-laboratory">Laboratory</span>
    <span class="badge badge-task">Task</span>
    <span class="due-date due-soon">Due 28 Oct 2026 (in 3 days)</span>
    <h2 class="notice-title">...</h2>
    <p class="notice-body">...</p>
    <p class="notice-meta">Posted by Lab Coordinator on ...</p>
    <span class="state state-unread">Unread</span>
    <button class="status-button mark-read-button" data-notice-id="6" data-action="read">Mark as read</button>
    <button class="status-button ack-button" data-notice-id="6" data-action="acknowledge">Acknowledge</button>
</article>
```

Points to notice:

- **`id="notice-6"`** lets JavaScript find this card later.
- **`data-notice-id` and `data-action`** are custom attributes. They tell JavaScript **which notice**
  and **which action** a button is for.
- **`is-unread`** is added only for unread notices. CSS uses it for the blue left border and the
  "• New" label.
- Each button is printed **only when it is needed**. Already-read notices get no "Mark as read"
  button, and acknowledged tasks get no "Acknowledge" button.
- **Every value from the database goes through `htmlspecialchars()`** (Lab 10).
  `nl2br()` turns line breaks in the notice text into `<br>` so paragraphs show correctly.

**Helper functions in `functions.php`:**

- `describe_due_date()` turns a date into friendly text and a colour class:

  | Days left | Text | CSS class | Colour |
  |---|---|---|---|
  | below 0 | "overdue by 1 day" | `due-overdue` | red |
  | 0 | "today" | `due-soon` | orange |
  | 1 to 3 | "in 3 days" | `due-soon` | orange |
  | more than 3 | "in 6 days" | `due-later` | grey |
  | already acknowledged | (crossed out) | `due-done` | grey, struck through |

- `describe_state()` turns `read_at` and `acknowledged_at` into "Unread", "Read" or
  "Acknowledged on 20 Oct 2026, 05:13 PM".

### 7.6 The filter form

```html
<form id="filter-form" action="index.php" method="get">
    <input type="text" name="q">          <!-- search words -->
    <select name="category">...</select>  <!-- category -->
    <select name="view">...</select>      <!-- all / unread / pending / acknowledged -->
    <button type="submit">Search</button>
    <a href="index.php">Clear</a>
</form>
```

PHP marks the current choices as `selected` and puts the search word back in the box, so the form
always shows what is being filtered.

**Small JavaScript improvement (`board.js`):** when you change the Category or Show drop-down, the
`change` event (Lab 7) submits the form immediately, so you do not have to press Search.

```js
function submitFilters() {
  filterForm.submit();
}
document.getElementById("category").addEventListener("change", submitFilters);
document.getElementById("view").addEventListener("change", submitFilters);
```

---

## 8. Mark as read and Acknowledge (fetch + JSON)

This is the main "fetch and JSON" feature. When you click a button, **the page does not reload**.
Only the card and the summary numbers change.

### 8.1 The whole journey in one picture

```
 BROWSER (board.js)                                SERVER (api/update_status.php)
 ───────────────────                               ───────────────────────────────
 1. User clicks "Acknowledge" on notice 4
 2. Buttons on that card are disabled,
    message shows "Saving..."
 3. fetch POST, body is JSON:
    {"noticeId":4,"action":"acknowledge",
     "token":"ee71..."}               ───────────►  4. Checks: POST? logged in? valid JSON?
                                                       correct token?
                                                    5. Checks notice 4 exists, is active
                                                       and is a task
                                                    6. INSERT ... ON DUPLICATE KEY UPDATE
                                                    7. Reads the new state + new summary
                                    ◄───────────    8. Replies with JSON:
                                                       {"ok":true,"message":"Notice acknowledged.",
                                                        "isRead":true,"isAcknowledged":true,
                                                        "stateText":"Acknowledged on ...",
                                                        "stateClass":"state-acknowledged",
                                                        "summary":{"total":9,"unread":5,
                                                                   "pending":2,"overdue":0}}
 9. JavaScript updates the card and
    the four summary numbers
10. finally: buttons are enabled again
```

### 8.2 The browser side: `js/common.js` → `postJson()`

Both pages need to send JSON with fetch, so the code is written once, in `common.js`:

```js
function postJson(url, payload) {
  payload.token = csrfToken;                     // add the security token

  return fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)                // JS object -> JSON text (Lab 8)
  })
    .then(function(response) {
      return response.json()                     // JSON text -> JS object (Lab 8)
        .catch(function() {
          throw new Error("The server sent an unexpected reply (HTTP " + response.status + ").");
        })
        .then(function(data) {
          if (!response.ok || !data.ok) {        // check the HTTP status (Lab 8)
            throw new Error(data.message || "Request failed (HTTP " + response.status + ").");
          }
          return data;
        });
    });
}
```

In simple words:

1. It adds the security token to the data.
2. It sends a **POST** request whose **body is JSON text** (`JSON.stringify`), with the header
   `Content-Type: application/json`. This is exactly the pattern from Lab 8, §5.
3. When the reply arrives, it converts the JSON text back into a JavaScript object
   (`response.json()`).
4. If the server said no (for example status 403 with `"ok": false`), it **throws an Error carrying
   the server's own message**, such as "Your session has ended. Please log in again.", so the user
   sees a useful reason.
5. If the reply is not JSON at all (for example a PHP crash page), the user sees "The server sent an
   unexpected reply" instead of a confusing technical error.

> **Small difference from Lab 8:** Lab 8 checks `response.ok` before reading the body. Our PHP files
> always reply with JSON that contains a `message`, even when they refuse a request. So we read the
> JSON first and then check `response.ok`. That way we can show the server's exact reason.

### 8.3 The browser side: `js/board.js` → `updateNoticeStatus()`

```js
function updateNoticeStatus() {
  let button = this;                                         // the button that was clicked
  let noticeId = Number(button.getAttribute("data-notice-id"));
  let action = button.getAttribute("data-action");           // "read" or "acknowledge"
  let card = document.getElementById("notice-" + noticeId);
  let cardButtons = $(card).find("button");

  cardButtons.prop("disabled", true);                        // no double clicks
  showStatus(boardMessage, "Saving...", false);

  postJson("api/update_status.php", { noticeId: noticeId, action: action })
    .then(function(data) {
      applyState(card, data);            // change the card
      updateSummary(data.summary);       // change the 4 numbers
      showStatus(boardMessage, data.message, false);
    })
    .catch(function(error) {
      showStatus(boardMessage, "Could not update the notice: " + error.message, true);
    })
    .finally(function() {
      cardButtons.prop("disabled", false);   // always enable again
    });
}

$(".status-button").on("click", updateNoticeStatus);   // jQuery attaches it to every button
```

- **`.then`** runs when the request succeeds, **`.catch`** when anything fails, and **`.finally`**
  always runs at the end. This is the same structure as the Lab 8 example.
- The buttons are **disabled while waiting**, so two requests cannot compete (Lab 8, §8.1).
- The message paragraph has `aria-live="polite"`, so screen readers announce the change.

**`applyState(card, data)`** changes the card to match what the server said:

- sets the state text, for example "Read" or "Acknowledged on ...", and its colour class;
- if read: removes the `is-unread` class (the blue border and "New" disappear) and removes the
  "Mark as read" button;
- if acknowledged: removes the "Acknowledge" button and crosses out the due date.

**`updateSummary(summary)`** writes the four new numbers with jQuery, for example
`$("#summary-unread").text(summary.unread)`.

> **Why does the server send back the new summary?** The browser could subtract 1 itself, but the
> server's numbers are always correct. For example, acknowledging a notice also marks it as read,
> and the server's numbers already include that.

### 8.4 The server side: `api/update_status.php`

**Step 1: check the request.** `read_api_request()` in `functions.php` makes four checks. Any
failure ends the request with a JSON error:

| Check | If it fails | HTTP status |
|---|---|---|
| Is it a POST request? | "Use a POST request." | 405 |
| Is the user logged in? | "Your session has ended. Please log in again." | 401 |
| Is the body valid JSON? | "The request body must be JSON." | 400 |
| Is the token correct? | "Invalid request token. Reload the page and try again." | 403 |

PHP reads the JSON body with:

```php
$data = json_decode(file_get_contents("php://input"), true);
```

`$_POST` only works for normal form data. A JSON body has to be read as raw text from
`php://input` and converted into a PHP array with `json_decode`.

**Step 2: check the data.**

- `noticeId` must be a positive number, and `action` must be `read` or `acknowledge`.
- The notice must exist **and be active**. Otherwise: "This notice is no longer on the board." (404)
- "Acknowledge" is allowed only on tasks. Otherwise: "This notice does not need an
  acknowledgement." (400)

**Step 3: save the state.** For "acknowledge":

```sql
INSERT INTO notice_reads (user_id, notice_id, read_at, acknowledged_at)
VALUES (:userId, :noticeId, NOW(), NOW())
ON DUPLICATE KEY UPDATE read_at = COALESCE(read_at, NOW()),
                        acknowledged_at = COALESCE(acknowledged_at, NOW())
```

Read it like this:

- **"Insert a new row saying this user read and acknowledged this notice now."**
- **`ON DUPLICATE KEY UPDATE`**: "If a row for this user and notice already exists (the primary key
  would be duplicated), update that row instead of adding a new one."
- **`COALESCE(read_at, NOW())`**: "Keep the old `read_at` if there is one; only use `NOW()` if it is
  empty." So the **first** time is kept even if the button is clicked again.
- Acknowledging also fills `read_at`, because you cannot acknowledge something without reading it.

**Step 4: reply.** The file reads the row again, builds the state text, calculates the new summary
and replies with `send_json(200, [...])`. That function sets the HTTP status, adds the header
`Content-Type: application/json` and prints `json_encode(...)`.

---

## 9. The management page (`manage.php`)

Only staff can open this page. The first line is `require_staff();`. A student who types the URL
gets "Only department staff can open this page." (403). The **Manage Notices** link in the top bar
is printed only for staff (`header.php` checks `is_staff()`).

The page has two parts.

### 9.1 Part 1: the "Add a Notice" form

| Field | HTML control | `name` sent to PHP |
|---|---|---|
| Title | `<input type="text" maxlength="150">` | `title` |
| Category | `<select>` filled from the `categories` table | `categoryId` |
| Notice text | `<textarea>` | `body` |
| "Students must acknowledge" | `<input type="checkbox">` | `requiresAck` |
| Due date | `<input type="date" min="today">` | `dueDate` |
| (hidden) | `<input type="hidden">` | `token` |

The hidden `token` field works like the hidden `topic` field in Lab 10: it is invisible, but it is
sent with the form.

### 9.2 Part 2: the "All Notices" table

It shows **every** notice, active and inactive, with:

- category and "Task" badges,
- when it was posted and by whom,
- due date,
- **Read by:** e.g. `1 / 3` (1 of 3 students has read it),
- **Acknowledged by:** e.g. `0 / 3` (only for tasks),
- status (**Active** / **Inactive**) and an **Activate / Deactivate** button.

The read and acknowledge counts come from **subqueries**: small `SELECT COUNT(*)` queries inside the
main query, run once for each notice:

```sql
(SELECT COUNT(*) FROM notice_reads s JOIN users r ON r.user_id = s.user_id
 WHERE s.notice_id = n.notice_id AND r.role = 'student' AND s.read_at IS NOT NULL) AS read_count
```

Only **students** are counted (`r.role = 'student'`), so staff opening the board do not change the
numbers.

Each table row carries its state for JavaScript:

```html
<tr id="row-3" class="notice-row" data-active="1"> ... </tr>
```

### 9.3 The "Show" filter above the table

A drop-down offers "All notices / Active only / Inactive only". This filter needs **no server
request**: the browser already has all the rows, so jQuery just shows or hides them:

```js
function applyStatusFilter() {
  let choice = $("#status-filter").val();

  $(".notice-row").each(function() {
    let isActive = $(this).attr("data-active") === "1";

    if (choice === "all" || (choice === "active" && isActive) || (choice === "inactive" && !isActive)) {
      $(this).show();
    } else {
      $(this).hide();
    }
  });
}
```

---

## 10. Adding a notice (normal form + POST)

Adding a notice uses an ordinary form submission, the same pattern as `add.php` in the Extra Lab.

```
manage.php ──(POST form)──> add_notice.php ──(redirect)──> manage.php
  (JS checks first)          (PHP checks again,              (shows "Notice ... has
                              INSERT into database)            been published.")
```

### 10.1 Step 1: checks in the browser (`js/manage.js`)

When **Publish Notice** is clicked, `validateNoticeForm(event)` runs on the form's `submit` event
(Lab 7). It stops at the **first** problem it finds:

1. Title is empty → "Please enter a title."
2. Title is longer than 150 characters → error
3. No category chosen → "Please choose a category."
4. Notice text is empty → "Please enter the notice text."
5. Due date is in the past → "The due date cannot be in the past."
6. Due date given but the task box is not ticked → error

**Only if there is a problem** does it call `event.preventDefault()` and show the message. If
everything is fine, the form is sent normally.

Two helpful extras:

- **Character counter:** the `input` event (Lab 7) updates "(133 characters left)" next to "Title"
  as you type.
- **Auto-tick:** when you pick a due date, the `change` event ticks the "Students must acknowledge"
  box for you and explains why. A due date only makes sense for a task.

### 10.2 Step 2: checks on the server (`add_notice.php`)

The server **repeats every check**, because a request can be sent without our JavaScript (Lab 7,
§6.5 and Lab 10, §5). It also checks:

- the **token** is correct,
- the date is a real date: `DateTime::createFromFormat("Y-m-d", ...)` rejects values like
  `2026-02-31`,
- the category exists. If it does not, the **foreign key** makes the INSERT fail; the `catch` block
  catches that and shows "The notice could not be saved."

**When a check fails,** the small function `reject()`:

1. saves an error **flash message** in the session,
2. saves everything the user typed in `$_SESSION["oldNotice"]`,
3. redirects back to `manage.php`.

`manage.php` then shows the error and **fills the form again with the typed values**, so nothing has
to be retyped.

**When everything is fine,** the notice is saved with a prepared `INSERT`, a success flash message
is set, and the browser is redirected to `manage.php`.

> **Why redirect after saving? (Post/Redirect/Get)**
> If `add_notice.php` showed a page directly, pressing Refresh would send the form again and create
> a **duplicate notice**. Redirecting means the last request is a harmless GET of `manage.php`.
> Lab 10 lists this under "Common Beginner Errors".

---

## 11. Activating and deactivating a notice (fetch + JSON)

This is the second fetch + JSON operation, and it is part of the required "management interface".

### 11.1 Browser side (`js/manage.js` → `toggleNotice()`)

1. Reads the notice number from the button's `data-notice-id`.
2. Looks at the row's `data-active` to decide the **opposite**: active rows get deactivated,
   inactive rows get activated.
3. Disables the button and shows "Deactivating..." or "Activating...".
4. Calls `postJson("api/toggle_notice.php", { noticeId: 3, active: false })`.
5. On success, `setRowStatus()` changes the row **without a reload**:
   - `data-active` becomes `"0"` or `"1"`,
   - the row turns grey (`row-inactive`) or back to normal,
   - the pill text becomes "Inactive" or "Active",
   - the button text becomes "Activate" or "Deactivate".
6. It re-applies the "Show" filter. If you are viewing "Active only" and deactivate a notice, the
   row disappears from the list.
7. `catch` shows any error, and `finally` enables the button again.

### 11.2 Server side (`api/toggle_notice.php`)

1. `read_api_request()` makes the same four checks as before (POST, logged in, JSON, token).
2. **Only staff allowed:** if `is_staff()` is false, it replies "Only department staff can change
   notices." (403). This matters because a student could try to send the request by hand, without
   the page.
3. `active` must be a real JSON `true` or `false`. Anything else gets 400.
4. Runs `UPDATE notices SET is_active = :active WHERE notice_id = :noticeId`.
5. **Does the notice exist?** `rowCount()` tells how many rows were changed. It is 0 if the notice
   does not exist, **but also** if it already had that status. So when it is 0, a quick
   `SELECT COUNT(*)` tells the two cases apart and replies 404 only if the notice really does not
   exist.
6. Replies with JSON such as
   `{"ok":true,"message":"Notice deactivated. It is hidden from the board.","noticeId":3,"active":false}`.

### 11.3 Why "deactivate" instead of "delete"?

- Nothing is lost. A notice deactivated by mistake can be activated again with one click.
- Students' read and acknowledged records stay in `notice_reads`, so the history is kept.
- The board query always includes `WHERE n.is_active = 1`, so students simply stop seeing it.
  `update_status.php` also refuses to mark an inactive notice.

---

## 12. Where jQuery is used

Lab 8 introduces jQuery for elementary tasks. This project uses it in exactly those ways. It is
loaded from the CDN **before** our own scripts:

```html
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/common.js"></script>
<script src="js/board.js"></script>
```

| jQuery code | What it does | File |
|---|---|---|
| `$("#csrf-token").val()` | Reads the hidden token value | common.js |
| `$("#summary-unread").text(5)` | Changes the text of a summary number | board.js |
| `$(".status-button").on("click", updateNoticeStatus)` | Attaches the click handler to **every** status button at once | board.js |
| `$(card).find("button").prop("disabled", true)` | Disables all buttons inside one card | board.js |
| `$(card).find(".ack-button").remove()` | Removes a button from the page | board.js |
| `$(".toggle-button").on("click", toggleNotice)` | Attaches the click handler to every toggle button | manage.js |
| `$(this).show()` / `$(this).hide()` | Shows or hides table rows for the "Show" filter | manage.js |
| `$("#login-id").val().trim()` | Reads a form value | login.js |

Plain JavaScript is used too (`getElementById`, `textContent`, `addEventListener`), as in Labs 6–7.
jQuery is still JavaScript; it only makes some tasks shorter.

> `$(".status-button").on(...)` is a good example of why jQuery helps. With plain JavaScript we would
> need a loop to attach a handler to each button. jQuery does it for every matching element in one
> line.

---

## 13. How the page is styled (`styles.css`)

All styling is in **one external stylesheet** linked from every page (Lab 4). It uses only ideas from
Labs 4 and 5:

- **Class and id selectors:** `.notice-card`, `.badge-academic`, `#notice-form`.
- **Box model:** `padding`, `margin`, `border` and `border-radius` give the cards, tiles and panels
  their space and shape (Lab 5).
- **Centred container:** `.page-container { max-width: 1000px; margin: 0 auto; }`. This is the
  "auto horizontal margins" centring from Lab 5.
- **Side by side without flexbox:** summary tiles and filter fields use `display: inline-block` with
  percentage widths (`width: calc(25% - 16px)` for four tiles in a row).
- **Colours carry meaning:**
  - one colour per category badge (Academic blue, Laboratory teal, Event purple, Placement orange,
    General grey),
  - due dates: orange = soon, red = overdue, crossed out = done,
  - state: blue = Unread, grey = Read, green = Acknowledged.
- **"• New" without extra HTML:**
  `.notice-card.is-unread .notice-title::after { content: " \2022 New"; }` adds the text using CSS.
  When JavaScript removes the `is-unread` class, the label disappears by itself.
- **Phones:** `@media (max-width: 700px)` puts two tiles per row and stacks the filter fields one
  below the other. The `viewport` meta tag in each page makes phones use this layout.
- `!important` is **not used anywhere**. Lab 5 explains why it should not be the default solution.

**Separation of roles (Lab 6):** JavaScript decides **what** changes (it adds or removes a class
such as `is-unread` or `row-inactive`). CSS decides **how** that looks.

---

## 14. Security: how the project protects itself

| Danger | What could happen | How this project stops it |
|---|---|---|
| **SQL injection** | Someone types `' OR '1'='1` in the search box to change the SQL query | Every user value goes through a **prepared statement** with placeholders (`:loginId`, `:searchTitle` ...). Values can never change the structure of the query (Extra Lab §6.2). |
| **HTML/script injection (XSS)** | A notice title like `<script>...</script>` runs in other users' browsers | Every value printed into HTML goes through **`htmlspecialchars()`**, so `<` becomes `&lt;` and is shown as text (Lab 10 §5.2). In JavaScript, text is set with `textContent` / `.text()`, never as HTML. |
| **Stolen passwords** | If someone copies the database, they learn everyone's password | Passwords are stored as **hashes** (`password_hash`) and checked with `password_verify`. A hash cannot be turned back into the password. |
| **Skipping the JavaScript checks** | Someone sends a request directly (e.g. with curl) with bad data | **Every check is repeated in PHP** (Lab 7 §6.5, Lab 10 §5). |
| **Students using staff actions** | A student sends a "deactivate" request by hand | `require_staff()` on pages and an `is_staff()` check in `toggle_notice.php` |
| **Requests from other websites (CSRF)** | A bad website makes your logged-in browser secretly submit our forms | A random **token** is created at login and stored in the session. It is put in a hidden field and sent with every form and fetch request. Another website cannot know it, so its requests are rejected. `hash_equals` compares tokens safely. |
| **Session fixation** | Someone tricks you into using a session ID they already know | `session_regenerate_id(true)` gives a brand-new session ID at login |
| **Duplicate records** | A double click creates two "read" rows | The primary key `(user_id, notice_id)` plus `ON DUPLICATE KEY UPDATE` |
| **Resubmitting forms** | Refresh after adding a notice adds it twice | Redirect after POST |
| **Leaking error details** | A failed connection shows passwords or paths | `db.php` prints only "Could not connect to the database." |

---

## 15. Complete journey of one click

Here is everything that happens when **Riya** clicks **Mark as read** on "Lab session rescheduled"
(notice 6), from start to finish:

1. **Browser:** jQuery attached `updateNoticeStatus` to the button, so the function runs.
   `this` is the clicked button.
2. **Browser:** reads `data-notice-id="6"` and `data-action="read"`, then finds the card
   `#notice-6`.
3. **Browser:** disables the card's buttons and shows "Saving...".
4. **Browser:** `postJson()` adds the token and sends
   `POST api/update_status.php` with the body `{"noticeId":6,"action":"read","token":"..."}`.
   The session cookie is sent automatically.
5. **Apache** receives the request and passes `update_status.php` to **PHP**.
6. **PHP:** `auth.php` calls `session_start()` and finds Riya's session (userId 3).
7. **PHP:** `read_api_request()` checks POST ✔, logged in ✔, JSON ✔, token ✔.
8. **PHP:** checks the action ✔ and that notice 6 is active ✔.
9. **PHP → MariaDB:**
   `INSERT INTO notice_reads (user_id, notice_id, read_at) VALUES (3, 6, NOW()) ON DUPLICATE KEY UPDATE ...`
10. **PHP → MariaDB:** reads the row back and runs the summary query.
11. **PHP → Browser:**
    `{"ok":true,"message":"Notice marked as read.","isRead":true,...,"summary":{"total":9,"unread":6,...}}`
12. **Browser:** `response.json()` turns the text into an object; `ok` is true.
13. **Browser:** `applyState()` changes "Unread" to "Read", removes the blue border and "New", and
    removes the button.
14. **Browser:** `updateSummary()` changes **Unread** from 7 to 6.
15. **Browser:** shows "Notice marked as read." and `finally` re-enables the remaining buttons.

**The page never reloaded.** Open DevTools → Network to watch this request, its JSON payload and
the JSON response. This is a good thing to show during the demo.

---

## 16. Likely viva questions and answers

**Q: Which tech stack did you use, and why nothing else?**
HTML, CSS, JavaScript, jQuery, PHP and MariaDB on Apache (XAMPP). The problem statement allows only
this stack.

**Q: Where exactly do you use fetch and JSON?**
In two places: Mark as read / Acknowledge (`js/board.js` → `api/update_status.php`) and Activate /
Deactivate (`js/manage.js` → `api/toggle_notice.php`). Both send a JSON body made with
`JSON.stringify` and receive JSON made with `json_encode`.

**Q: Why are there two `then` calls?**
The first runs when the HTTP response arrives and starts reading the body with `response.json()`.
The second runs when that reading is finished and receives the JavaScript object.

**Q: Why do you check `response.ok`?**
fetch does not fail on HTTP errors such as 403 or 404. It only fails on network problems. We check
`response.ok` and `throw` an Error so that `catch` shows a message.

**Q: How does PHP read the JSON body? Why not `$_POST`?**
`$_POST` only understands normal form data. For JSON we read the raw body with
`file_get_contents("php://input")` and convert it with `json_decode(..., true)`.

**Q: Why use GET for the filters but POST for adding a notice?**
GET is for viewing and filtering: the values appear in the URL and can be bookmarked. POST is for
changing data, and its values go in the request body (Lab 10, §4.3).

**Q: How is the read/acknowledged state stored?**
In the `notice_reads` table, one row per student and notice, with `read_at` and `acknowledged_at`
times. No row means unread.

**Q: What stops a student from acknowledging the same notice twice?**
The primary key `(user_id, notice_id)` allows only one row. `ON DUPLICATE KEY UPDATE` updates the
existing row, and `COALESCE` keeps the first time.

**Q: Why `LEFT JOIN` and not `JOIN` on the board?**
Unread notices have no row in `notice_reads`. A normal `JOIN` would hide them. `LEFT JOIN` keeps every
notice and gives `NULL` for missing reads.

**Q: What happens when a notice is deactivated?**
`is_active` becomes 0. The board only shows `is_active = 1`, so students no longer see it. Nothing is
deleted, and it can be activated again.

**Q: Why validate in both JavaScript and PHP?**
JavaScript gives quick feedback but can be bypassed. PHP is the real protection.

**Q: What does `htmlspecialchars` protect against? And prepared statements?**
`htmlspecialchars` protects **HTML output** (XSS). Prepared statements protect **SQL** (SQL
injection). They solve different problems, and both are needed (Extra Lab, §6.2).

**Q: What is a session and where is the data kept?**
Data about the logged-in user kept **on the server** in `$_SESSION`. The browser only holds the
session ID in a cookie.

**Q: What is the token in the hidden field?**
A random value created at login and stored in the session. Every form and fetch request must send
it back. This stops other websites from making requests on behalf of a logged-in user.

**Q: Why redirect after adding a notice?**
So that refreshing the page does not submit the form again and create a duplicate (Post/Redirect/Get).

**Q: Why are buttons disabled while a request is pending?**
To stop the user sending a second, competing request before the first one finishes (Lab 8, §8.1).

**Q: How do you count unread and pending notices in one query?**
`SUM(condition)`. In MariaDB a true condition is 1 and a false one is 0, so the sum is the number of
rows where the condition is true.

**Q: How are passwords stored?**
As bcrypt hashes from `password_hash()`. At login, `password_verify()` checks the typed password
against the hash. The real password is never stored.

---

## 17. Glossary

| Term | Simple meaning |
|---|---|
| **Apache** | The web server program that receives browser requests (port 8080 here) |
| **Asynchronous** | The result arrives later; the page keeps working while it waits |
| **CDN** | A public server that hosts libraries such as jQuery |
| **COALESCE(a, b)** | SQL: "use `a`, but if it is NULL use `b`" |
| **Cookie** | A small piece of data the browser stores and sends back to the site; here it holds the session ID |
| **CSRF token** | A secret random value that proves a request came from our own page |
| **DOM** | The browser's object version of the page that JavaScript can read and change |
| **fetch** | The JavaScript function that sends an HTTP request without reloading the page |
| **Flash message** | A message saved in the session and shown once on the next page |
| **Foreign key** | A column that must match the primary key of another table |
| **Hash (password)** | A one-way scrambled version of a password that cannot be reversed |
| **HTTP status code** | A number in every response: 200 OK, 400 bad request, 401 not logged in, 403 not allowed, 404 not found, 405 wrong method |
| **JOIN / LEFT JOIN** | Combine rows from two tables; LEFT JOIN keeps rows even when there is no match |
| **JSON** | A text format for data, e.g. `{"noticeId": 6, "action": "read"}` |
| **MariaDB** | The database server (port 3307 here) |
| **PDO** | The PHP tool used to talk to the database |
| **Placeholder** | A name like `:noticeId` in SQL that is filled in safely with `execute()` |
| **Prepared statement** | SQL sent separately from its values, so values cannot change the query |
| **Primary key** | A column (or columns) that uniquely identifies each row |
| **Promise** | A JavaScript object representing a result that will arrive later |
| **Session** | Data about the current user stored on the server between requests |
| **Superglobal** | PHP's built-in arrays: `$_GET`, `$_POST`, `$_SESSION`, `$_SERVER` |
| **XSS** | Cross-site scripting: tricking a page into running someone else's script |
