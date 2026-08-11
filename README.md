# Landing Page Manager — setup guide

A self-hosted system for running ad landing pages. One admin login; your team
edits every headline, address, phone number, link and image from a browser.
Leads from the call-back form are stored in the database and emailed to you.

Built with PHP + MySQL, so it runs on any ordinary shared hosting account
(cPanel, Hostinger, GoDaddy, Bluehost) with no server setup, no Node, no build
step and no monthly SaaS fee.

---

## 1. What you're getting

```
index.php            the public page renderer
submit.php           call-back form handler (validate → save → email)
install.php          one-time setup wizard — DELETE after running it
config.php           the only file you edit by hand
.htaccess            pretty URLs, caching, security headers

admin/               the login-protected control panel
  login.php          sign in
  index.php          all pages: create, duplicate, publish, delete
  edit.php           the content editor (Content / SEO & tracking / Settings)
  leads.php          leads inbox with status tracking + CSV export
  settings.php       notification emails, password, system check
  users.php          add colleagues (admin or editor)

templates/           the page layouts
  store-locator.php  the design you supplied

includes/
  templates.php      ← the field schema. Add a field here, it appears in admin
  helpers.php        escaping, URLs, tokens
  db.php             database layer
  mailer.php         lead notification email

assets/              CSS, JS, and the images cropped from your design
uploads/             where images uploaded through the admin land
```

---

## 2. Installing (about 10 minutes)

**Step 1 — create a database.**
In cPanel/hPanel → *MySQL Databases*, create a database and a user, and give
that user all privileges on it. Note the three values down.

**Step 2 — get the files onto the server.**
Either upload everything to `public_html/` (or a subfolder like
`public_html/lp/`), or, if you're deploying from git (see section 10), pull
the repo there instead.

**Step 3 — create `config.php` and edit it.**
`config.php` is deliberately **not** in the repo — it holds this server's
database password, so it never travels through git. Create your own copy from
the template that *is* tracked:

```bash
cp config.sample.php config.php
```

Then edit these lines in the new `config.php`:

```php
define('DB_NAME', 'yourcpanel_technogym');
define('DB_USER', 'yourcpanel_lpuser');
define('DB_PASS', 'the-password-you-just-set');

define('BASE_URL', 'https://yourdomain.com');   // no trailing slash
define('MAIL_FROM', 'leads@yourdomain.com');    // must be on YOUR domain
define('APP_SECRET', 'paste-40-random-characters-here');
```

**Step 4 — run the installer.**
Open `https://yourdomain.com/install.php`, create your admin account, submit.

**Step 5 — delete `install.php`.**
The admin panel shows a red warning bar until you do. Do it now.

**Step 6 — turn off debug.**
In `config.php` set `define('DEBUG', false);` so PHP errors are never shown to
visitors.

Your page is live at `https://yourdomain.com/technogym-india`, and the control
panel is at `https://yourdomain.com/admin/`.

> **In a subfolder?** Set `BASE_URL` to include it
> (`https://yourdomain.com/lp`) and uncomment `RewriteBase /lp/` in `.htaccess`.

---

## 3. Making the other 4 pages

Don't copy files. In **Pages**, hit **Duplicate** on the page you like, then
change the text and images. Each copy gets its own URL, its own tracking codes
and its own leads.

| | |
|---|---|
| **Duplicate** | clones all content and settings as a new draft |
| **New page** | starts from the template defaults |
| **Publish / Unpublish** | drafts are visible only when you're logged in — safe for review |
| **URL slug** | the address, e.g. `mumbai-launch` → `yourdomain.com/mumbai-launch` |

Every page has 4 store slots and 3 hero slides. Toggle the ones you don't need
off; they disappear from the page and the layout closes up.

---

## 4. Editing content

**Content tab** — every text, image and link on the page, grouped the way the
page is laid out. Images: upload a file, or paste a URL if it's hosted
elsewhere. The Theme group changes the yellow, the dark section and the page
background, so a page can be re-skinned for a different campaign in seconds.

**SEO & tracking tab** — Google title, meta description, the WhatsApp/Facebook
share image, and two boxes for tracking code: one for `<head>` (GTM, GA4, Meta
Pixel, Google Ads) and one for right after `<body>` (the GTM noscript block).
Paste the snippets exactly as your ads platform gives them.

Conversion tracking is already wired. Every CTA fires a `cta_click` event into
`dataLayer`, `gtag` and `fbq` with a label:

| Label | Fired by |
|---|---|
| `header-call` | the phone number in the header |
| `tile-call` | the CALL US tile |
| `tile-whatsapp` | the WHATSAPP US tile |
| `tile-callback` | the call-back form submit |
| `tile-website` | the VISIT THE WEBSITE tile |
| `store-bengaluru`, `store-newdelhi`, … | each store's VISIT THE STORE link |

Build your Google Ads / Meta conversions on that event — no extra code.

**Page settings tab** — name, URL, published/draft, and extra email recipients
for this page's leads.

---

## 5. Leads

Every submission is stored and appears under **Leads**, newest first, with the
UTM parameters from the ad click (`?utm_source=google&utm_medium=cpc&utm_campaign=…`
is captured automatically). Mark each one New → Contacted → Converted → Junk,
call or WhatsApp straight from the row, and export any filtered view to CSV.

**Email notifications:** set recipients in **Settings**, and per-page extras in
the page's own settings. Use **Send test** in Settings to confirm delivery
before you spend on ads.

If the test doesn't arrive: shared hosts often drop mail whose From address
isn't on the same domain — make sure `MAIL_FROM` in `config.php` is
`something@yourdomain.com`. If it still fails, switch on SMTP:

```bash
composer require phpmailer/phpmailer
```

then set `SMTP_ENABLED` to `true` and fill in the SMTP block in `config.php`.
Either way, leads are always saved to the database first, so a mail failure
never loses one.

---

## 6. What's protecting the form

The public page sets **no cookie** and starts **no session** — it stays fast and
cacheable for paid traffic. The form is protected by a signed token valid for
about two hours, an invisible honeypot field bots fill in and humans never see,
a server-side phone check, and a 5-minute duplicate window so a double-tap on
mobile doesn't create two leads. Indian numbers typed with a leading `0` or
`91` are normalised automatically.

Elsewhere: passwords are bcrypt-hashed, the admin uses session CSRF tokens,
uploads are type-checked by content (not filename) and PHP execution is
disabled in `uploads/`, all output is escaped, and every query is a prepared
statement.

---

## 7. Adding a new editable field

Open `includes/templates.php`, add one line to the right group:

```php
['key' => 'hero_button_text', 'label' => 'Hero button text', 'type' => 'text', 'default' => 'BOOK A DEMO'],
```

The admin form and the database rows update themselves the next time the editor
loads — existing pages are not disturbed. Then print it in
`templates/store-locator.php`:

```php
<?= e($b('hero_button_text')) ?>
```

Field types: `text`, `textarea`, `image`, `url`, `tel`, `toggle`, `color`.

To build a genuinely different layout, copy `templates/store-locator.php` to a
new file and register it as a second entry in `templates()` — it then shows up
in the template dropdown when creating a page.

---

## 8. Requirements

PHP 7.4 or newer (8.x recommended) with PDO, `pdo_mysql` and GD; MySQL 5.7+ or
MariaDB; Apache with `mod_rewrite` for pretty URLs (without it the pages still
work as `/?p=slug`). Tested on PHP 8.4.

**On Nginx** there's no `.htaccess`; add this to the server block instead:

```nginx
location / { try_files $uri $uri/ /index.php?p=$uri&$args; }
location ~ ^/uploads/.*\.php$ { deny all; }
location ~ ^/(includes|templates|storage)/ { deny all; }
location = /config.php { deny all; }
```

---

## 10. Git & auto-deploy (Hostinger)

The repo does **not** contain `config.php` or anything in `uploads/` (see
`.gitignore`) — those are per-server, not code. This matters for auto-deploy:

- **First deploy to a new server:** after the first pull, SSH in (or use the
  File Manager) and run `cp config.sample.php config.php`, then edit it with
  that server's real DB credentials, `APP_SECRET` and `BASE_URL`. Auto-deploy
  will never overwrite this file afterwards, because git doesn't track it.
- **Database:** git only carries code. The first deploy still needs
  `install.php` run once against the live database (section 2) — auto-deploy
  doesn't create tables or the admin account for you.
- **Uploaded images:** anything uploaded through the admin lives in
  `uploads/` on that server's disk, outside git. It survives future deploys
  (auto-deploy only touches tracked files) but isn't backed up by git — keep
  your own backup of `uploads/` separately.
- **After every deploy:** if `install.php` is still present, delete it from
  the live server. It's harmless to leave in the repo (a fresh environment
  needs it), but it shouldn't stay reachable on a live site once you're set up.

**Setting it up on Hostinger:** hPanel → *Git* lets you point a repository and
branch at a directory (usually `public_html` or a subfolder) and deploy on
push. Point it at this repo's `main` branch. After the first auto-deploy
completes, do the one-time `config.php` + `install.php` steps above — from
then on, `git push` alone updates the live site.

---

## 11. Going live — checklist

- [ ] `config.php` created on the server from `config.sample.php` (git does
      not create this for you) and filled in with real values
- [ ] `install.php` run once against the live database, then deleted from the
      server
- [ ] `DEBUG` set to `false` in `config.php`
- [ ] `APP_SECRET` changed to a long random string
- [ ] HTTPS on (free via hPanel → SSL)
- [ ] `uploads/` permissions set to 755
- [ ] Test email received in Settings
- [ ] One real call-back submitted and visible in Leads
- [ ] Tracking code pasted and firing (check GTM Preview)
- [ ] High-resolution images uploaded — the ones shipped were cropped from the
      design mockup and are placeholders only
