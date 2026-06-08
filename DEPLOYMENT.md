# Deploying ComplyGCC on a fresh Ubuntu server

A step-by-step guide a non-expert can follow. Target OS: **Ubuntu 22.04 LTS**.
(The spec mentions 24.04, but the production server is 22.04 — the only practical
difference is that PHP 8.4 comes from the `ondrej/php` PPA, which the provision
script handles for you.)

Everything runs on one VPS for Phase 1: **Nginx → PHP-FPM 8.4**, **MySQL 8**,
**Redis** (cache + queue), **Supervisor** (queue workers) and **cron** (scheduler).

---

## 0. Before you start

- A server running Ubuntu 22.04 with a public IP, and `sudo`/root access.
- A domain name (e.g. `app.example.com`) with an **A record** pointing at the IP.
- Choose a strong MySQL app password (you'll pass it to the script).

---

## 1. Get the code onto the server

```bash
sudo mkdir -p /var/www/complygcc
sudo chown -R "$USER" /var/www/complygcc
git clone <your-repo-url> /var/www/complygcc
```

## 2. Provision the server (installs everything)

```bash
cd /var/www/complygcc/deploy
sudo APP_DOMAIN=app.example.com DB_PASSWORD='your-strong-password' bash provision.sh
```

This installs PHP 8.4 + extensions, MySQL 8, Redis, Nginx, Supervisor, Composer,
Node 20 and Certbot; creates the database and app user; and writes the Nginx,
Supervisor and cron configuration.

## 3. Configure the application environment

```bash
cd /var/www/complygcc/backend
cp ../deploy/.env.production.example .env
nano .env          # set APP_URL, DB_PASSWORD, MAIL_* (SMTP); leave keys blank
php artisan key:generate     # sets APP_KEY
php artisan vault:key        # sets VAULT_MASTER_KEY (back this up securely!)
```

> **Keep `VAULT_MASTER_KEY` safe and out of backups of the database.** It is the
> key that decrypts every stored document. Losing it loses the documents; leaking
> it defeats the encryption. For stronger custody, use a managed KMS later.

## 4. Deploy (build + migrate + cache)

```bash
cd /var/www/complygcc
bash deploy/deploy.sh
```

Then hand ownership to the web user and restart services:

```bash
sudo chown -R www-data:www-data /var/www/complygcc/backend/storage /var/www/complygcc/backend/bootstrap/cache
sudo systemctl reload php8.4-fpm
sudo supervisorctl restart complygcc-worker:*
```

(Optional) seed demo data on a non-production environment:
`php artisan migrate:fresh --seed`.

## 5. Enable HTTPS

```bash
sudo certbot --nginx -d app.example.com
```

Certbot obtains a Let's Encrypt certificate and adds the HTTPS server block.
Auto-renewal is installed by default.

## 6. Verify

- Visit `https://app.example.com` — you should reach the cockpit login.
- `php artisan about` — confirms drivers (mysql / redis).
- `php artisan schedule:list` — shows `deadlines:recompute` (daily) and
  `alerts:send` (hourly).
- `sudo supervisorctl status` — workers `RUNNING`.

---

## Routine operations

| Task | Command |
|------|---------|
| Deploy a new release | `bash deploy/deploy.sh` (from repo root) |
| Tail worker logs | `tail -f backend/storage/logs/worker.log` |
| Restart workers | `sudo supervisorctl restart complygcc-worker:*` |
| Run the daily recompute now | `php artisan deadlines:recompute` |
| Send due alerts now | `php artisan alerts:send` |
| Rotate the vault key | plan a re-encryption migration before changing it |

## Resilience / DR (SPEC.md §10)

Keep an **encrypted off-site backup** of the database and the vault bucket in a
second stable region, under transfer safeguards. The vault master key must be
stored separately from those backups.
