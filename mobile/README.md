# FurniStyle Customer Mobile App

Expo (React Native) app that consumes the Symfony **Customer API** with JWT authentication.

**Also:** the separate **Oracion** React Native CLI project in `../../App Dev/Oracion/` talks to the same API (see `../../App Dev/README.md` and `../docs/CUSTOMER_MOBILE_API.md`).

## Features (end-to-end)

The Expo app calls the Symfony **Customer API** with JWT (`Authorization: Bearer`). Tab navigation covers shop, categories, contact, and account flows; the auth modal opens from the guest account tab using the root stack (works from nested profile navigator).

| Feature | API | Mobile screen |
|--------|-----|----------------|
| Register | `POST /api/customer/register` | Register |
| Login | `POST /api/customer/login` | Login |
| Browse products | `GET /api/customer/products` | Shop tab |
| Product details | `GET /api/customer/products/{id}` | Product detail |
| Categories | `GET /api/customer/categories` | Categories tab |
| Profile | `GET/PATCH /api/customer/me` | Account tab |
| Change password | `POST /api/customer/change-password` | Change password |
| Contact | `POST /api/customer/contact` | Contact tab |

## Prerequisites

1. Symfony backend running (from project root):

   ```bash
   symfony server:start
   # or: php -S 127.0.0.1:8000 -t public
   ```

2. Database migrated and products seeded in admin panel.

3. Node.js 18+ and npm.

## Setup

```bash
cd mobile
cp .env.example .env
npm install
npx expo start
```

Edit `.env` and set `EXPO_PUBLIC_API_URL`:

| Environment | URL |
|-------------|-----|
| iOS Simulator / Expo web | `http://127.0.0.1:8000` |
| Android emulator | `http://10.0.2.2:8000` |
| Physical phone (same Wi‑Fi) | `http://YOUR_PC_IP:8000` |

Scan the QR code with **Expo Go** on your phone, or press `a` / `i` for Android / iOS emulator.

## Test account

Create a customer via the app **Register** screen, or use an existing verified customer from the web app.

With `CUSTOMER_API_AUTO_VERIFY=true` in Symfony `.env` (default), new mobile registrations can log in immediately.

## API documentation

- Customer routes: `/api/customer/*`
- JWT login: `POST /api/customer/login` with JSON `{ "email": "...", "password": "..." }`
- API Platform docs: `http://127.0.0.1:8000/api/docs` (staff/admin resources require staff JWT)
