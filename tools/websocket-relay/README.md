# FurniStyle WebSocket Relay

Lightweight WebSocket relay used by FurniStyle for real-time order updates.

## Run locally

```bash
cd tools/websocket-relay
npm install
set WEBSOCKET_SECRET=change_me_websocket_secret
npm start
```

Server defaults:

- WebSocket subscribe URL: `ws://127.0.0.1:8081/ws?channel=admin-orders`
- Broadcast HTTP URL: `http://127.0.0.1:8081/broadcast`

## How it works

- Symfony sends `POST /broadcast` with:
  - `x-ws-secret` header
  - JSON body: `{ channel, type, payload }`
- Connected WebSocket clients on that `channel` receive the event JSON.

## Environment variables

- `WS_PORT` (default `8081`)
- `WEBSOCKET_SECRET` (required for `/broadcast` auth)

