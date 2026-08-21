# Security / Privacy Notes

## Private photo rule
Customer upload != case-publication consent.

`consent_case_publication` defaults to 0 and is completely separate from privacy consent.

## Storage
Best:
- put the configured upload directory outside `public_html`.

Fallback:
- keep `storage/private_uploads` inside the deployed app;
- `.htaccess` denies all direct access;
- directory listing disabled;
- admin photo delivery should later go through an authenticated PHP endpoint.

## PII
Do not send to GA4:
- name
- phone
- email
- full address
- photo filename
- photo content
- free-form customer note

## Database
- PDO prepared statements
- utf8mb4
- InnoDB transaction
- no DB credentials in Git

## Rate limiting
Current foundation:
- hashed IP bucket
- max 8 requests / 10 minutes by default
- IP itself is not stored, only HMAC-SHA256 hash

## Still required before public launch
- Admin authentication
- CSRF/session protection for admin writes
- authenticated private image viewer
- backup policy
- actual data retention policy
- actual legal entity/contact information in Privacy


## Admin foundation added
- password_hash / password_verify
- secure HttpOnly SameSite=Strict session cookie
- session ID regeneration after login
- inactivity expiry
- CSRF token for status changes
- admin routes noindex / no-store
- private image viewer requires authenticated session
- private photo response adds X-Robots-Tag noindex, nofollow, noarchive

## Important limitation
This is a single-admin foundation, not a multi-user RBAC system.
Before public use, the real `config/app.php` must contain a strong password hash.
