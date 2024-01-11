<img src="logo.svg" alt="Equalify Logo" width="300">

## Equalify V1

This is a private version of Equalify.

We'll keep V1 code private until we're successfully sustaining our open-source development with paid users.

## Setup
- Create `.env` with
```
## DB Info
MODE='managed'
DB_HOST='v1-db'
DB_USERNAME='root'
DB_PASSWORD='root'
DB_NAME='db'

## Auth0 Info
# Your Auth0 application's Client ID
AUTH0_CLIENT_ID=
# The URL of your Auth0 tenant domain
AUTH0_DOMAIN=
# Your Auth0 application's Client Secret
AUTH0_CLIENT_SECRET=
# A long, secret value used to encrypt the session cookie
AUTH0_COOKIE_SECRET=
# A url your application is accessible from. Update this as appropriate.
AUTH0_BASE_URL=
```
- Make sure you have the cron running to support `process_scan.php` and `process_sitemap.php`. The cron file should include something like this:
```
*/1 * * * * php /var/www/html/WIP/engine/process_scan.php >> /var/www/html/WIP/engine/cron.log 2>&1
*/1 * * * * php /var/www/html/WIP/engine/process_property.php >> /var/www/html/WIP/engine/cron.log 2>&1
```
(we aim to process 10k scans over 7 days)