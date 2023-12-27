<img src="logo.svg" alt="Equalify Logo" width="300">

## Equalify V1

This is a private version of Equalify.

We'll keep V1 code private until we're successfully sustaining our open-source development with paid users.

## Setup
- Create `.env` with
```
DB_HOST='YOUR_HOST'
DB_USERNAME='YOUR_USERNAME'
DB_PASSWORD='YOUR_PASSWORD'
DB_NAME='YOUR_USER'
DB_PORT='YOUR_PORT'
```
- Make sure you have the cron running to support `process_scan.php` and `process_sitemap.php`. The cron file should include something like this:
```
*/2 * * * * echo "$(date '+%Y-%m-%d %H:%M:%S') $(php /var/www/html/WIP/engine/process_scan.php)" >> /var/www/html/WIP/engine/cron.log 2>&1
*/2 * * * * echo "$(date '+%Y-%m-%d %H:%M:%S') $(php /var/www/html/WIP/engine/process_property.php)" >> /var/www/html/WIP/engine/cron.log 2>&1
```