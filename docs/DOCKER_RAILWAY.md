# Docker & Railway (FurniStyle API)

Same setup as the FDMS reference: **nginx + PHP-FPM**, `APP_URL` for verification/OAuth links, Brevo mail, JWT, and Railway deploy.

## Local Docker

### Prerequisites

- Docker Desktop running
- Ports **8000** (app), **3307** (MySQL), **8080** (phpMyAdmin), **8025** (Mailpit UI)

### Before first run

1. Copy secrets (if needed):

   ```powershell
   copy .env.local.example .env.local
   ```

   Set `APP_SECRET`, `BREVO_SMTP_KEY`, `OAUTH_GOOGLE_*`, and `JWT_PASSPHRASE` in `.env.local`.

2. JWT keys (if missing):

   ```powershell
   php bin/console lexik:jwt:generate-keypair
   ```

3. Google Cloud → Authorized redirect URI:

   `http://127.0.0.1:8000/connect/google/check`

### Start stack

```powershell
cd "C:\Users\jelor\Desktop\School\Programming\FurniStyle"
docker compose up --build
```

Migrations run on container start. To run manually:

```powershell
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

| Service     | URL |
|------------|-----|
| App        | http://127.0.0.1:8000 |
| Mailpit    | http://127.0.0.1:8025 |
| phpMyAdmin | http://127.0.0.1:8080 |

Compose overrides `MAILER_DSN` to Mailpit (`smtp://mailer:1025`). For real Brevo in Docker, remove that override in `docker-compose.yaml` and set Brevo vars in `.env.local`.

### Oracion (React Native)

Point the app at the API host:

- Android emulator: `http://10.0.2.2:8000` (default in `Oracion/src/config/furnistyle.ts`)
- Physical device: set override to your PC LAN IP, e.g. `http://192.168.x.x:8000`

## Railway

1. Connect the FurniStyle repo; Railway uses `Dockerfile` + `railway.toml`.
2. Add **MySQL** and link `DATABASE_URL`.
3. Set variables from `config/deploy.env.example` (minimum: `APP_ENV=prod`, `APP_SECRET`, `APP_URL`, `DATABASE_URL`, `JWT_PASSPHRASE`, `BREVO_SMTP_*`, `MAILER_FROM_*`, `OAUTH_GOOGLE_*`).
4. `APP_URL` can be omitted if Railway sets `RAILWAY_PUBLIC_DOMAIN` (entrypoint builds `https://…`).
5. Google redirect: `https://<your-domain>/connect/google/check`
6. After deploy, confirm migrations ran (entrypoint) or run:

   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

Verification emails should use:

`https://your-app.up.railway.app/verify-email?token=...`
