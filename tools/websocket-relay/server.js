import crypto from 'node:crypto';
import http from 'node:http';
import { WebSocketServer } from 'ws';

const port = Number(process.env.PORT || process.env.WS_PORT || 8082);
const secret = process.env.WEBSOCKET_SECRET || '';

/** @type {Map<string, Set<import('ws').WebSocket>>} */
const channelClients = new Map();

function addClient(channel, response) {
  if (!channelClients.has(channel)) {
    channelClients.set(channel, new Set());
  }
  channelClients.get(channel).add(response);
}

function removeClient(channel, response) {
  const set = channelClients.get(channel);
  if (!set) return;
  set.delete(response);
  if (set.size === 0) {
    channelClients.delete(channel);
  }
}

function readJsonBody(req) {
  return new Promise((resolve, reject) => {
    let body = '';
    req.on('data', (chunk) => {
      body += chunk;
      if (body.length > 1024 * 1024) {
        reject(new Error('Payload too large.'));
        req.destroy();
      }
    });
    req.on('end', () => {
      try {
        resolve(JSON.parse(body || '{}'));
      } catch (_error) {
        reject(new Error('Invalid JSON payload.'));
      }
    });
    req.on('error', reject);
  });
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url || '/', `http://${req.headers.host || '127.0.0.1'}`);

  if (req.method === 'GET' && url.pathname === '/health') {
    res.writeHead(200, { 'content-type': 'application/json' });
    res.end(JSON.stringify({ ok: true, channels: channelClients.size }));
    return;
  }

  if (req.method === 'POST' && url.pathname === '/broadcast') {
    const providedSecret = req.headers['x-ws-secret'];
    const providedBuffer = typeof providedSecret === 'string' ? Buffer.from(providedSecret) : null;
    const expectedBuffer = Buffer.from(secret);
    const isAuthorized = Boolean(
      secret
      && providedBuffer
      && providedBuffer.length === expectedBuffer.length
      && crypto.timingSafeEqual(providedBuffer, expectedBuffer),
    );
    if (!isAuthorized) {
      res.writeHead(401, { 'content-type': 'application/json' });
      res.end(JSON.stringify({ message: 'Unauthorized.' }));
      return;
    }

    try {
      const payload = await readJsonBody(req);
      const channel = String(payload.channel || '').trim();
      const type = String(payload.type || '').trim();
      const body = payload.payload || {};

      if (!channel || !type) {
        res.writeHead(400, { 'content-type': 'application/json' });
        res.end(JSON.stringify({ message: 'channel and type are required.' }));
        return;
      }

      const clients = channelClients.get(channel) || new Set();
      const eventPayload = {
        channel,
        type,
        payload: body,
        ts: new Date().toISOString(),
      };

      for (const client of clients) {
        if (client.readyState === client.OPEN) {
          client.send(JSON.stringify(eventPayload));
        }
      }

      res.writeHead(200, { 'content-type': 'application/json' });
      res.end(JSON.stringify({ delivered: clients.size }));
    } catch (error) {
      res.writeHead(400, { 'content-type': 'application/json' });
      res.end(JSON.stringify({ message: error.message || 'Bad request.' }));
    }
    return;
  }

  res.writeHead(404, { 'content-type': 'application/json' });
  res.end(JSON.stringify({ message: 'Not found.' }));
});

const wss = new WebSocketServer({ noServer: true });

server.on('upgrade', (req, socket, head) => {
  const url = new URL(req.url || '/', `http://${req.headers.host || '127.0.0.1'}`);
  if (url.pathname !== '/ws') {
    socket.destroy();
    return;
  }

  const channel = (url.searchParams.get('channel') || '').trim();
  if (!channel) {
    socket.destroy();
    return;
  }

  wss.handleUpgrade(req, socket, head, (ws) => {
    addClient(channel, ws);
    ws.send(JSON.stringify({ type: 'connected', channel, ts: new Date().toISOString() }));

    ws.on('close', () => {
      removeClient(channel, ws);
    });
  });
});

server.listen(port, () => {
  console.log(`[websocket-relay] listening on http://127.0.0.1:${port}`);
  console.log(`[websocket-relay] subscribe with WebSocket: ws://127.0.0.1:${port}/ws?channel=admin-orders`);
});

