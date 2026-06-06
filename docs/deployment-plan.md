# Deployment Plan — Saurashtra Express

> Production deployment, CI/CD, environment management, and rollback.

---

## 1. Environments

| Env | URL | Database | Cache | Queue | Purpose |
|---|---|---|---|---|---|
| **Local** | `http://localhost` | SQLite (or MySQL) | file | sync | Dev |
| **Staging** | `https://staging.sxpress.in` | MySQL 8 | redis | redis | Pre-prod |
| **Production** | `https://sxpress.in` | MySQL 8 (primary + read replica) | redis | redis | Live |
| **Backup** | (offsite) | MySQL 8 (read-only, lagged 1h) | — | — | DR |

---

## 2. Server requirements

### 2.1 Application server (Laravel + PHP)
- **OS:** Ubuntu 22.04 LTS (or Windows Server 2019 if you must, but Linux is strongly recommended)
- **PHP:** 8.2.x (8.3.x acceptable)
- **Extensions:** `php8.2-fpm`, `php8.2-mysql`, `php8.2-mbstring`, `php8.2-xml`, `php8.2-zip`, `php8.2-curl`, `php8.2-gd`, `php8.2-bcmath`, `php8.2-intl`
- **Composer:** 2.x
- **Node:** 18.x or 20.x (for Vite build)
- **Process manager:** systemd (for `php-fpm` and `queue:work`)
- **Web server:** Nginx 1.24+ (or Apache 2.4+ with `mod_rewrite`)

### 2.2 Database server
- **MySQL:** 8.0.x (8.4 acceptable)
- **InnoDB** buffer pool: 4-8 GB for the `grs` table alone (assuming 1M rows × 1KB ≈ 1GB)
- **Connection limit:** 200 (tune based on PHP-FPM worker count)

### 2.3 Cache / Queue server
- **Redis:** 7.x
- **Memory:** 2-4 GB
- **Persistence:** AOF (for queue durability)

### 2.4 Storage
- **S3** (or S3-compatible like MinIO) for POD uploads and GR scans.
- **Local disk** for session files (if not using Redis) and the spool for queued jobs.

---

## 3. Directory layout on the server

```
/var/www/sxpress/
├── current/           # symlink → releases/<timestamp>/
├── releases/
│   ├── 20260605-1200/
│   ├── 20260604-1800/
│   └── ...
├── shared/
│   ├── .env           # not in releases
│   ├── storage/       # symlink target from current/storage
│   └── public/uploads/  # symlink target from current/public/uploads
└── repo.git/          # bare git repo (for `git push` deployment)
```

Use a deployment tool like **Envoyer**, **Deployer**, or a custom bash script that:
1. `git pull` the new commit into `releases/<timestamp>/`
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build`
4. `php artisan migrate --force` (if new migrations)
5. `php artisan config:cache`
6. `php artisan route:cache`
7. `php artisan view:cache`
8. `php artisan event:cache`
9. `php artisan storage:link`
10. Symlink `current` to the new release
11. `php artisan queue:restart`
12. Reload php-fpm

---

## 4. CI/CD (GitHub Actions example)

```yaml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: sxpress_test
        ports: ['3306:3306']
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=5
      redis:
        image: redis:7
        ports: ['6379:6379']

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, mysql, gd, zip, intl
          coverage: xdebug

      - uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Install npm dependencies
        run: npm ci

      - name: Build assets
        run: npm run build

      - name: Copy .env
        run: cp .env.example .env

      - name: Generate key
        run: php artisan key:generate

      - name: Migrate + seed test DB
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_DATABASE: sxpress_test
          DB_USERNAME: root
          DB_PASSWORD: root
        run: |
          php artisan migrate:fresh --seed
          php artisan config:clear
          php artisan route:clear
          php artisan view:clear

      - name: Run tests
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_DATABASE: sxpress_test
          DB_USERNAME: root
          DB_PASSWORD: root
        run: php artisan test --coverage-text --min=70

      - name: PHPStan
        run: composer require --dev phpstan/phpstan && vendor/bin/phpstan analyse --level=5 app

      - name: Pint (code style)
        run: vendor/bin/pint --test

      - name: Composer audit
        run: composer audit

      - name: NPM audit
        run: npm audit --audit-level=high
```

---

## 5. Release checklist

For every production release:

- [ ] All tests pass on CI
- [ ] PHPStan level ≥ 5 clean
- [ ] Pint (code style) clean
- [ ] `composer audit` shows no high/critical
- [ ] `npm audit` shows no high/critical
- [ ] Database migration tested on staging
- [ ] New env vars documented in `.env.example` and the deployment runbook
- [ ] Rollback plan documented
- [ ] Stakeholders notified
- [ ] Deploy in off-peak hours (Sunday 4-6am IST for this app)
- [ ] Database backup taken immediately before deploy
- [ ] Post-deploy: smoke test (login, create GR, create gatepass, create challan)
- [ ] Monitor for 30 minutes

---

## 6. Database backup

### 6.1 Strategy
- **Daily full backup** at 2:00am IST.
- **Hourly incremental** via binary log.
- **Offsite copy** to S3 / Glacier.
- **Retention:** 30 days for daily, 7 days for hourly.

### 6.2 Cron
```bash
0 2 * * * /usr/local/bin/mysqldump --single-transaction --routines --triggers --events sxpress | gzip > /backup/sxpress-$(date +\%Y\%m\%d).sql.gz
0 * * * *  /usr/local/bin/mysqlbinlog --read-from-remote-server --host=db-replica --user=repl --password=$REPL_PASS --raw --stop-never --result-file=/backup/binlog/ mysql-bin.0
```

### 6.3 Restore
```bash
gunzip < /backup/sxpress-20260605.sql.gz | mysql sxpress
# then apply binlog up to the desired point-in-time
mysqlbinlog /backup/binlog/mysql-bin.0* | mysql sxpress
```

### 6.4 Test the backup
- **Monthly:** restore a random backup to a staging DB and run smoke tests.
- **Quarterly:** full DR drill (restore from offsite, point app at the new DB).

---

## 7. Monitoring

### 7.1 Application monitoring
- **Sentry** for exception tracking (Laravel integration is one composer package).
- **Laravel Telescope** in dev/staging only.
- **Laravel Pulse** (L11+) for production metrics.

### 7.2 Server monitoring
- **Prometheus + Grafana** for server metrics.
- **UptimeRobot** or **Pingdom** for HTTP uptime.
- **mysqld_exporter** for MySQL metrics.

### 7.3 Key alerts
| Alert | Threshold | Severity |
|---|---|---|
| HTTP 5xx rate | > 1% of requests in 5 min | P1 |
| Login failure rate | > 50% in 5 min | P2 (could be attack) |
| GR create latency | p95 > 500ms | P2 |
| MySQL connections | > 80% of max | P2 |
| Disk usage | > 80% | P2 |
| Queue backlog | > 1000 jobs | P2 |
| SSL cert expiry | < 14 days | P1 |
| Backup missing | > 26 hours since last | P1 |

---

## 8. SSL / TLS

- Use **Let's Encrypt** with certbot.
- Nginx config:
  ```nginx
  server {
      listen 443 ssl http2;
      server_name sxpress.in;
      ssl_certificate /etc/letsencrypt/live/sxpress.in/fullchain.pem;
      ssl_certificate_key /etc/letsencrypt/live/sxpress.in/privkey.pem;
      ssl_protocols TLSv1.2 TLSv1.3;
      ssl_ciphers HIGH:!aNULL:!MD5;
      add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
      add_header X-Frame-Options DENY;
      add_header X-Content-Type-Options nosniff;
      add_header Referrer-Policy "strict-origin-when-cross-origin";
      root /var/www/sxpress/current/public;
      index index.php;
      location / { try_files $uri $uri/ /index.php?$query_string; }
      location ~ \.php$ {
          fastcgi_pass unix:/run/php/php8.2-fpm.sock;
          fastcgi_index index.php;
          fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
          include fastcgi_params;
      }
  }
  ```

---

## 9. Queue worker

Supervisord config:
```ini
[program:sxpress-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sxpress/current/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/sxpress/shared/storage/logs/queue.log
stopwaitsecs=3600
```

`--max-time=3600` ensures workers self-restart every hour (good for releasing memory).

---

## 10. Scheduler

Add to crontab:
```
* * * * * cd /var/www/sxpress/current && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs (proposed):
- `app:reconcile-branch-balances` — daily at 2am
- `app:purge-temp-files` — daily at 3am
- `app:send-daily-gr-register` — daily at 8am
- `app:check-eway-bill-expiry` — daily at 9am

---

## 11. Rollback procedure

If a deploy fails:

```bash
cd /var/www/sxpress
# Point 'current' back to the previous release
ln -sfn releases/<previous-timestamp> current

# Reload php-fpm (picks up new symlink)
sudo systemctl reload php8.2-fpm

# If the new release had a DB migration:
cd /var/www/sxpress/releases/<previous-timestamp>
php artisan migrate:rollback --step=<N>  # N = number of migrations in the bad release
```

Then investigate, fix, redeploy.

---

## 12. Disaster recovery

| Scenario | RTO | RPO | Procedure |
|---|---|---|---|
| App server crash | 5 min | 0 (stateless) | Reboot; if persistent, redeploy |
| Database crash (no replica) | 30 min | 0 | Restore from latest backup + apply binlog |
| Database crash (with replica) | 1 min | < 5 sec | Failover to replica |
| Region outage | 4 hours | < 1 hour | Restore from offsite backup to a new region |
| Ransomware | 24 hours | < 24 hours | Restore from offsite backup; rotate all credentials |

---

## 13. Environment variables (.env.example)

```dotenv
APP_NAME="Saurashtra Express"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://sxpress.in

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=db-primary
DB_PORT=3306
DB_DATABASE=sxpress
DB_USERNAME=sxpress_app
DB_PASSWORD=

DB_READ_HOST=db-replica

BROADCAST_DRIVER=log
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

REDIS_HOST=redis
REDIS_PASSWORD=
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@sxpress.in
MAIL_FROM_NAME="${APP_NAME}"

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-south-1
AWS_BUCKET=sxpress-uploads
AWS_URL=https://s3.ap-south-1.amazonaws.com/sxpress-uploads

BCRYPT_ROUNDS=12

SANCTUM_STATEFUL_DOMAINS=sxpress.in,www.sxpress.in
SESSION_DOMAIN=.sxpress.in
```

---

## 14. Performance baselines (post-deployment)

After every release, run a smoke test and verify:

| Endpoint | Expected p95 | Alert if p95 > |
|---|---|---|
| `GET /` | 100ms | 300ms |
| `POST /login` | 300ms | 800ms |
| `GET /dash` | 200ms | 500ms |
| `GET /dash/gr` | 300ms (paged) | 800ms |
| `GET /dash/gatepass` | 300ms | 800ms |
| `GET /dash/challan` | 300ms | 800ms |
| `GET /dash/gr/{id}/print` | 500ms | 1.5s |
| `POST /dash/gr/store` | 400ms | 1s |

Use **Laravel Pulse** or **New Relic** to track these over time.
