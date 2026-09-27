# HEdClass – PHP read-only report

A single PHP page that reads from the MySQL database of my
[HEdClass](https://github.com/MaksWithA-KS/hedclass) degree classification
platform (originally built in Node.js/Express). You choose a programme and it
lists that programme's students with their final classification and whether it
was manually overridden.

It's deliberately small. I built it to learn server-side PHP and PDO against a
schema I already know.

## What it demonstrates

- **PDO** for database access, with exceptions on error and associative-array fetches
- **Prepared statements** with named placeholders (`:id`) for every query that uses user input,
  so the input is sent to MySQL separately from the SQL and can't be executed
- **Input validation** with `filter_input(... FILTER_VALIDATE_INT)` before the value reaches the database
- **Output escaping** with `htmlspecialchars` to prevent cross-site scripting (XSS)
- **Least privilege**: the page connects as a MySQL user that only has `SELECT` rights
- **Credentials kept out of Git**: `config.php` is ignored, and `config.example.php` shows the format
- **Safe error handling**: database errors are logged, and the user only sees a generic message

## Files

| File | Purpose |
|---|---|
| `index.php` | Fetches the data, then renders the HTML form and table |
| `db.php` | Creates the PDO connection |
| `config.example.php` | Template for the database settings |

## Running it locally (XAMPP)

1. Load the HEdClass database (`setup.sql` from the main HEdClass repo) into XAMPP's MySQL.
2. Create a read-only user (phpMyAdmin → SQL tab):
   ```sql
   CREATE USER 'hedclass_reader'@'localhost' IDENTIFIED BY 'choose-a-password';
   CREATE USER 'hedclass_reader'@'127.0.0.1' IDENTIFIED BY 'choose-a-password';
   GRANT SELECT ON hedclass.* TO 'hedclass_reader'@'localhost';
   GRANT SELECT ON hedclass.* TO 'hedclass_reader'@'127.0.0.1';
   ```
3. Copy `config.example.php` to `config.php` and enter that password.
4. Put this folder in `C:\xampp\htdocs\hedclass-php\`, start Apache and MySQL,
   and open <http://localhost/hedclass-php/>.

## Testing done

- Every programme lists the correct students, sorted by surname
- An empty, non-numeric or SQL-like value (`?programme_id=1 OR 1=1`) is rejected by validation
- A programme ID that doesn't exist shows a "not found" message
- A student name containing `<script>` is displayed as text, not run as code
- The read-only user is refused `DELETE`
- With the database unavailable, the page shows a generic error and the real error goes to the log