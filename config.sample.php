<?php
/**
 * ---------------------------------------------------------------------------
 *  CONFIGURATION TEMPLATE
 * ---------------------------------------------------------------------------
 *  This file is tracked in git. The real config.php is NOT (see .gitignore) --
 *  it holds your live database password and secret key, and those must never
 *  end up in source control, even in a private repo.
 *
 *  First time on a new server (or a fresh clone):
 *      cp config.sample.php config.php
 *  Then edit config.php with that environment's real values. From then on,
 *  git pull / auto-deploy never touches it again.
 * ---------------------------------------------------------------------------
 */

// --- Database -------------------------------------------------------------
// Use 'mysql' on your live hosting.  'sqlite' needs no database at all and is
// handy for testing on a laptop -- everything else in the app is identical.
define('DB_DRIVER', getenv('LP_DB_DRIVER') ?: 'mysql');

define('DB_HOST', 'localhost');
define('DB_NAME', 'technogym_lp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Only used when DB_DRIVER is 'sqlite'
define('DB_SQLITE_PATH', __DIR__ . '/storage/app.sqlite');

// --- Site -----------------------------------------------------------------
// Public base URL of the folder these files live in. No trailing slash.
// e.g. 'https://promo.technogym.in'  or  'https://example.com/lp'
define('BASE_URL', getenv('LP_BASE_URL') ?: '');

// Default page shown when someone opens the base URL with no page slug.
define('DEFAULT_SLUG', 'technogym-india');

// --- Email ----------------------------------------------------------------
// Where lead notifications come FROM. Must be an address on your own domain,
// otherwise most hosts silently drop the mail.
define('MAIL_FROM',      'leads@yourdomain.com');
define('MAIL_FROM_NAME', 'Landing Page Leads');

// Set to true and fill the block below if plain mail() is unreliable on your
// host (common on shared hosting). Requires PHPMailer -- see README.
define('SMTP_ENABLED',  false);
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     '');
define('SMTP_PASS',     '');
define('SMTP_SECURE',   'tls');

// --- Uploads --------------------------------------------------------------
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_URL', 'uploads');
define('MAX_UPLOAD_BYTES', 8 * 1024 * 1024); // 8 MB

// --- Security -------------------------------------------------------------
// Change this to any long random string before going live.
define('APP_SECRET', 'change-this-to-a-long-random-string-before-going-live');

// Show PHP errors on screen. Set to false once the site is live.
define('DEBUG', false);
