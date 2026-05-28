# FurniStyle

Symfony web admin (staff/admin) + **Customer REST API** for the mobile app.

## Quick start

### Requirements

- PHP 8.2+, Composer, Symfony CLI (or `php -S`)
- MySQL/MariaDB
- Node.js (for admin assets / mobile clients)

### Backend setup

```bash
cd FurniStyle
composer install
cp .env .env.local   # if needed — configure DATABASE_URL, JWT keys, mailer
php bin/console doctrine:migrations:migrate
symfony server:start
# API: http://127.0.0.1:8000
```

Generate JWT keys if not present (see Lexik JWT bundle docs in project).

### Default URLs

| Surface | URL |
|---------|-----|
| Public site | http://127.0.0.1:8000/ |
| Admin login | http://127.0.0.1:8000/login |
| Admin dashboard | http://127.0.0.1:8000/admin |
| Customer API | http://127.0.0.1:8000/api/customer/... |

## Customer mobile app (Oracion)

Primary React Native CLI client:

`../App Dev/Oracion/`

```bash
cd "../App Dev/Oracion"
npm install
npx react-native start
# separate terminal, emulator running:
npx react-native run-android
```

Configure API host: `Oracion/src/config/furnistyle.js` (Android emulator: `http://10.0.2.2:8000`).

## Documentation

| Doc | Purpose |
|-----|---------|
| [docs/CUSTOMER_MOBILE_API.md](docs/CUSTOMER_MOBILE_API.md) | All customer API routes, JSON samples |
| [docs/DEMO_GUIDE.md](docs/DEMO_GUIDE.md) | Presentation / rubric demo script |
| [docs/GOOGLE_LOGIN.md](docs/GOOGLE_LOGIN.md) | Web Google OAuth |
| [App Dev/Oracion/docs/GOOGLE_SIGNIN_ANDROID.md](../App%20Dev/Oracion/docs/GOOGLE_SIGNIN_ANDROID.md) | Android Google Sign-In |
| [App Dev/Oracion/docs/ANDROID_TROUBLESHOOTING.md](../App%20Dev/Oracion/docs/ANDROID_TROUBLESHOOTING.md) | Emulator / Metro issues |

## WebSocket real-time updates (admin orders)

FurniStyle now supports real-time order events for the admin orders page.

1. Start relay:

```bash
cd tools/websocket-relay
npm install
# Windows PowerShell:
$env:WEBSOCKET_SECRET="change_me_websocket_secret"
npm start
```

2. Set backend env values (`.env.local` / deployment variables):

```dotenv
WEBSOCKET_PUBLIC_URL=ws://127.0.0.1:8081
WEBSOCKET_BROADCAST_URL=http://127.0.0.1:8081/broadcast
WEBSOCKET_SECRET=change_me_websocket_secret
```

With this configured, the admin orders list auto-refreshes when orders are created, cancelled, or status-updated.

## Roles

| Role | Access |
|------|--------|
| **Customer** | Mobile app + `/api/customer/*` (JWT) |
| **Staff** | Web products/categories (own records) |
| **Admin** | Full admin panel + customer orders |

## Expo reference app

Optional Expo client: [mobile/README.md](mobile/README.md)
