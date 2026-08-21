# Admin Dashboard v4.6

## URLs
- `/admin/login.php`
- `/admin/`
- `/admin/rfq.php?id=...`
- `/admin/photo.php?id=...`

## Login
Configuration lives in `config/app.php`.

Example:

```php
'admin' => [
  'username' => 'admin',
  'password_hash' => '$2y$...',
  'session_name' => 'PHOOHOTSHU_ADMIN',
  'session_lifetime_seconds' => 28800,
],
```

Generate a password hash on PHP:

```bash
php -r "echo password_hash('YOUR_STRONG_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

Never store plaintext passwords.

## Dashboard
Lists latest 200 RFQs and status counts.

Filters:
- new
- reviewing
- need_more_info
- site_visit
- quoted
- follow_up
- won
- lost

## RFQ Detail
Displays:
- customer/contact
- location
- area
- original floor
- issue
- timing
- source/UTM
- notes
- private photos
- status history

## Status updates
Protected by:
- authenticated admin session
- CSRF token
- database transaction
- status whitelist

## Private photos
The browser never receives the private filesystem path.

`admin/photo.php`:
1. requires login;
2. looks up photo row by ID;
3. builds server-side private path;
4. validates `realpath` is inside configured private upload root;
5. streams image;
6. sends `Cache-Control: private, no-store`;
7. sends `X-Robots-Tag: noindex, nofollow, noarchive`.

## Not yet included
- password reset
- MFA
- multi-admin roles
- audit log for logins
- email notification
- front-end RFQ integration
