# Go-live checklist — Novex Trust

Everything you must set up before the first real customer, in order. Secrets go
**on the server / in AWS / in your provider dashboards — never in git or chat.**

## 0. Prerequisites
- [ ] Domain + DNS **A record** → your Lightsail static IP
- [ ] Lightsail instance: **Ubuntu 22.04, Singapore (ap-southeast-1), 2 GB / 2 vCPU**
- [ ] Code pushed to GitHub (✅ done) and **CI green** on `main`

## 1. S3 vault bucket (Singapore)
- [ ] Create an S3 bucket in **ap-southeast-1**, **Block all public access = ON**, **Default encryption = ON**
- [ ] Create an **IAM user** (programmatic) with this least-privilege policy (replace `YOUR-BUCKET`):
  ```json
  { "Version": "2012-10-17", "Statement": [{
    "Effect": "Allow",
    "Action": ["s3:GetObject","s3:PutObject","s3:DeleteObject","s3:ListBucket"],
    "Resource": ["arn:aws:s3:::YOUR-BUCKET","arn:aws:s3:::YOUR-BUCKET/*"]
  }]}
  ```
- [ ] Note the access key + secret (set them in `.env` in step 4 — not here)

> Documents are envelope-encrypted **before** upload, so S3 only ever stores
> ciphertext. The S3 adapter is already installed (`league/flysystem-aws-s3-v3`).

## 2. Email (SMTP) — alerts
- [ ] Pick a provider (Amazon SES, Postmark, Mailgun, …), verify your sending domain
- [ ] Get host / port / username / password and a from-address

## 3. WhatsApp (Twilio) — optional at launch
- [ ] Twilio **Account SID**, **Auth Token**, and a **WhatsApp sender** number
- [ ] (Requires the `TwilioWhatsappChannel` to be built — not yet done; ask me)

## 4. Provision + configure the server
Follow **DEPLOYMENT.md** (provision.sh → deploy.sh → certbot). Then set these in
`/var/www/complygcc/backend/.env` (start from `deploy/.env.production.example`):

| Variable | Value / source |
|---|---|
| `APP_URL` | `https://your-domain` |
| `APP_KEY` | `php artisan key:generate` |
| `VAULT_MASTER_KEY` | `php artisan vault:key` — **back this up off-box** |
| `DB_PASSWORD` | the MySQL app password you chose in `provision.sh` |
| `VAULT_DISK_DRIVER` | `s3` |
| `AWS_DEFAULT_REGION` | `ap-southeast-1` |
| `AWS_BUCKET` | your bucket name |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | from the IAM user (step 1) |
| `MAIL_*` | from your SMTP provider (step 2) |
| `TWILIO_*` | from Twilio (step 3, when WhatsApp is built) |

## 5. Verify
- [ ] `php artisan about` → DB **mysql**, cache/queue **redis**
- [ ] `php artisan migrate --force` ran clean
- [ ] Upload a document in the cockpit → check the object lands in the S3 bucket (and is unreadable ciphertext)
- [ ] `php artisan schedule:list` shows `deadlines:recompute` + `alerts:send`
- [ ] `sudo supervisorctl status` → workers RUNNING
- [ ] HTTPS works (`https://your-domain`)

## 6. Before real customers
- [ ] 🔴 **UAE rule data verified by a compliance professional** (primary sources)
- [ ] Automated **DB + S3 backups** to a second stable region; master key stored separately
- [ ] `APP_DEBUG=false`, `APP_ENV=production` (already in the prod template)
- [ ] Run `/security-review`
