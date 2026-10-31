# Department Notice and Task Board

NCSM301 Web Development Laboratory, Mini Project **P9**, Group 16
(24JE0831, 24JE0873, 24JE0560, 24JE1033)

> Develop an interactive notice board with categories such as Academic, Laboratory, Event,
> Placement and General. Users should search/filter notices and record read/acknowledged state;
> a management interface must add or deactivate notices, with at least one operation using fetch
> and JSON.

**Stack:** HTML, CSS, JavaScript, jQuery 3.7.1, PHP (PDO) and MariaDB on Apache (standalone XAMPP).
Nothing else is used.

## Setup (same XAMPP configuration as Lab 9 and the Extra Lab)

The project expects Apache on port **8080** and MariaDB on port **3307**. If your XAMPP is already
set up this way from Lab 9 and the Extra Lab, skip to step 6.

Close the XAMPP Control Panel before editing any of the files below.

1. **Extract XAMPP.** Extract the standalone XAMPP ZIP to a folder you can write to, such as
   `C:\xampp` or `D:\xampp` (not `C:\Program Files`). If you extracted it inside another folder,
   run `setup_xampp.bat` once from the `xampp` folder. Do not install anything as a Windows
   service. 

2. **Set Apache to port 8080.** Open `xampp\apache\conf\httpd.conf` in a text editor and change:

   ```
   Listen 80                ->  Listen 8080
   ServerName localhost:80  ->  ServerName localhost:8080
   ```

   If the `ServerName` line starts with `#`, leave it as it is and add `ServerName localhost:8080`
   on a new line. Then open `xampp\apache\conf\extra\httpd-ssl.conf` and change the HTTPS port:

   ```
   Listen 443                   ->  Listen 8443
   <VirtualHost _default_:443>  ->  <VirtualHost _default_:8443>
   ServerName ...:443           ->  ServerName ...:8443
   ```

   In the last line, change only the port and keep the host name already in your file.

3. **Set MariaDB to port 3307.** Open `xampp\mysql\bin\my.ini`. Change the port in **both** the
   `[client]` and the `[mysqld]` sections:

   ```
   port=3306  ->  port=3307
   ```

   In the `[mysqld]` section, add this line directly below `port=3307` (if an active
   `bind-address` line without `#` already exists, change its value instead):

   ```
   bind-address=127.0.0.1
   ```


4. **Point phpMyAdmin to port 3307.** Open `xampp\phpMyAdmin\config.inc.php`, find the host line
   and add the port line directly below it. If the host line says `localhost`, change it to
   `127.0.0.1`:

   ```php
   $cfg['Servers'][$i]['host'] = '127.0.0.1';
   $cfg['Servers'][$i]['port'] = '3307';
   ```


5. **Match the Control Panel ports.** Open `xampp-control.exe` normally (not as administrator).
   Click **Config** at the top right, choose **Service and Port Settings**, and set:
   - **Apache** tab: Main Port `8080`, SSL Port `8443`
   - **MySQL** tab: Main Port `3307`

   Save. This only changes what the Control Panel displays; the file edits above are what
   actually change the ports.

6. **Start the servers.** In `xampp-control.exe`, click **Start** next to **Apache** and next to
   **MySQL**. Do not tick the Service checkboxes. Both rows should turn green, showing ports
   `8080, 8443` and `3307`. If Windows asks to allow `mysqld.exe` through the firewall, choose
   **Cancel**.

7. **Create the database.** Open `http://localhost:8080/phpmyadmin/`, click the server name at the
   top (it starts with *Server:*) so that no database is selected, open the **SQL** tab, paste the
   whole contents of `setup.sql` and click **Go**.

8. **Copy the project.** Copy the whole `notice_board` folder into `xampp\htdocs\`, so that
   `xampp\htdocs\notice_board\index.php` exists.

9. **Open the app** at **http://localhost:8080/notice_board/** and log in with one of the demo
   accounts below.

When you finish, click **Stop** for MySQL and Apache before closing the Control Panel. A MariaDB
left running keeps port 3307 busy, which causes *MySQL shutdown unexpectedly* next time; if that
happens, end your own `mysqld.exe` in Task Manager > Details and start again.

**If the page shows "Could not connect to the database."**, check that MySQL is running on port
3307 in the Control Panel and that `setup.sql` has been run. If you change the port or database
name, edit only `db.php`.

To reset the demo data, run `DROP DATABASE notice_board;` and then `setup.sql` again.

### Demo accounts (password for all: `notice123`)

| Login ID | Name | Role |
|---|---|---|
| `24JE9001` | Riya Verma | student |
| `24JE9002` | Aman Gupta | student |
| `24JE9003` | Neha Singh | student |
| `24JE0831`, `24JE0873`, `24JE0560`, `24JE1033` | Group 16 Member 1–4 (placeholder names) | student |
| `office` | Department Office | staff (can manage notices) |
| `labcoord` | Lab Coordinator | staff (can manage notices) |

## How the requirements are met

| Requirement | Where |
|---|---|
| Categories Academic, Laboratory, Event, Placement, General | `categories` table (`setup.sql`), colour badges in `styles.css` |
| Search / filter notices | GET form in `index.php`: text search (title + body), category, and view (all / unread / tasks to acknowledge / acknowledged). Choosing a category or view submits the form automatically (`js/board.js`). |
| Record read / acknowledged state | `notice_reads` table, one row per (user, notice). **Mark as read** and **Acknowledge** buttons send **fetch + JSON** to `api/update_status.php`; the card and summary counts update without reloading. |
| Management interface: add notices | `manage.php` form → `add_notice.php` (client-side validation in `js/manage.js`, validated again in PHP, then redirect) |
| Management interface: deactivate notices | **Deactivate / Activate** buttons send **fetch + JSON** to `api/toggle_notice.php`. Deactivated notices are hidden from the board but kept in the database. |
| "Task" board | A notice with *Students must acknowledge* ticked is a task, optionally with a due date. Tasks show *Due in N days* / *Overdue* labels, the summary counts pending and overdue tasks, and staff see how many students have read or acknowledged each notice. |

## Which lab concepts each part uses

| Lab | Concept | Used in |
|---|---|---|
| 2–3 | Headings, paragraphs, `div`, lists, links | All pages |
| 4–5 | External stylesheet, class/id selectors, box model, centred `.page-container`, `!important` avoided | `styles.css` |
| 6 | External script before `</body>`, `let`/`const`, functions, `getElementById`, `textContent`, `addEventListener` (no `onclick` attributes) | `js/*.js` |
| 7 | Form validation on `submit` with `preventDefault`, `input` and `change` events, `trim`, specific messages | `js/login.js`, `js/manage.js` (title counter, due-date auto-ticks the task box) |
| 8 | `fetch` with `.then`, `response.json()`, `JSON.stringify`, `Content-Type: application/json`, `catch` / `finally`, buttons disabled while a request is pending, `aria-live="polite"`; jQuery `.on`, `.text`, `.val`, `.show` / `.hide` | `js/common.js` (`postJson`), `js/board.js`, `js/manage.js` |
| 9 | PHP pages served by Apache, mixing HTML and PHP, `echo`, `if`/`else` | All `.php` files |
| 10 | GET for search/filter, POST for changes, `isset`/`trim` validation, `htmlspecialchars`, `session_start`, `$_SESSION`, logout with `session_unset` + `session_destroy`, redirect after POST | `index.php`, `process_login.php`, `add_notice.php`, `logout.php` |
| Extra | MariaDB tables with primary/foreign keys, `JOIN`, PDO in `db.php`, prepared statements with named placeholders, error code handling | `setup.sql`, every PHP file that queries |

A few standard PHP functions beyond the manuals are used for security and JSON:
`password_hash` / `password_verify` (passwords are never stored in plain text),
`session_regenerate_id` (new session ID at login), a random per-session token checked on every
change (`random_bytes`, `hash_equals`), and `json_decode` / `json_encode` with
`php://input` to read and answer the fetch requests.

## Files

```
notice_board/
├── EXPLANATION.md       detailed explanation of how everything works
├── setup.sql            database, tables and demo data
├── db.php               PDO connection (port 3307, as in the Extra Lab)
├── auth.php             session start, login/staff checks, request token, flash messages
├── functions.php        summary counts, due-date and state labels, JSON helpers
├── header.php / footer.php   shared page top and bottom
├── login.php            login form
├── process_login.php    checks the login (POST) and stores the user in the session
├── logout.php           ends the session
├── index.php            notice board with search / filters (GET)
├── manage.php           staff page: add form + table of all notices
├── add_notice.php       validates and inserts a new notice (POST)
├── api/
│   ├── update_status.php   JSON: mark as read / acknowledge
│   └── toggle_notice.php   JSON: activate / deactivate (staff only)
├── js/
│   ├── common.js        postJson() fetch helper
│   ├── board.js         notice board behaviour
│   ├── manage.js        form validation, activate/deactivate, status filter
│   └── login.js         login form validation
└── styles.css
```

## Database design

```
users (user_id PK, login_id UNIQUE, full_name, password_hash, role)
categories (category_id PK, name UNIQUE)
notices (notice_id PK, title, body, category_id FK, posted_by FK -> users,
         due_date NULL, requires_ack, is_active, created_at)
notice_reads (user_id FK, notice_id FK, read_at, acknowledged_at,
              PRIMARY KEY (user_id, notice_id))
```

The composite primary key on `notice_reads` makes a duplicate read record impossible.
`INSERT ... ON DUPLICATE KEY UPDATE` with `COALESCE` keeps the first read/acknowledged time even if
a button is clicked twice or two requests arrive together.

## Demo walkthrough

1. Log in as `24JE9001`. The summary shows unread, pending and overdue counts. Click **Mark as read**
   and **Acknowledge** on a few notices. The cards and counts change without a page reload
   (DevTools → Network shows the JSON request and response).
2. Search for `lab`, pick a category, or choose *Tasks to acknowledge* (sorted by due date).
3. Log out, log in as `office`, open **Manage Notices**. Submit the empty form to see validation,
   then publish a task with a due date. Deactivate a notice, log back in as a student and confirm
   it is gone from the board.
