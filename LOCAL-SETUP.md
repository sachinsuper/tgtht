# Running this on Laragon (local)

Everything is already in place in `C:\laragon\www\landing-page`.
Nothing to configure — the installer creates the database by itself.

## Start it

1. Open Laragon and click **Start All** (Apache + MySQL must both be green).
2. Go to **http://localhost/landing-page/install.php**

   That address always works once Apache is running, no virtual host needed.
   Laragon's pretty hostname for this folder is **http://landing-page.test/**
   (named after the folder, so `landing.test` will NOT resolve unless you
   rename the folder to `landing` and reload Apache).
3. Fill in your name, email and a password (8+ characters). Submit.
4. You'll land on the login page. Sign in with what you just created.

Your page:  http://localhost/landing-page/technogym-india
Admin:      http://localhost/landing-page/admin/

The app detects its own address, so it works identically on localhost,
landing-page.test, or the live domain — nothing to configure either way.

### If the page doesn't load at all

- Apache not started, or port 80 taken by IIS / Skype / another stack.
  In Laragon: Menu > Apache > check the error log, or change the port.
- Blank white page: PHP error. Laragon > Menu > PHP > php_error.log.

## What the installer does for you

- Creates the MySQL database `technogym_lp` (Laragon's root user has rights,
  so there is nothing to do in phpMyAdmin).
- Creates all tables.
- Seeds the first landing page with the design's content.

## Local config (already set, for reference)

`config.php` ships with Laragon's defaults:

    DB_HOST  localhost
    DB_NAME  technogym_lp
    DB_USER  root
    DB_PASS  (empty)
    BASE_URL (empty -> auto-detected from the browser address)

Because BASE_URL is blank it works on landing.test, localhost or any other
host without editing. Set it explicitly only on the live server.

## Housekeeping

- Delete the `_to_delete` folder — it only holds the setup zip.
- Delete `install.php` once you're in (the admin shows a red bar until you do).
- Leave `DEBUG` set to `true` locally; turn it off on the live server.

## Email on localhost

`mail()` does not work on Laragon, so lead notification emails will fail
locally. That's expected and harmless — leads are always saved to the database
first, so they still appear under **Leads**. To test emails locally, enable
SMTP in `config.php`, or just test them on the live server.

## Moving to the live server later

Upload the same files, change the four DB values and `BASE_URL` in
`config.php`, set `APP_SECRET`, run `install.php`, delete it. Full details in
`README.md`.
