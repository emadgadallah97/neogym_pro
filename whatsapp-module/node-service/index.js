'use strict';

require('dotenv').config();

const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const rateLimit = require('express-rate-limit');
const fs = require('fs');
const path = require('path');
const QRCode = require('qrcode');
const { Client, LocalAuth } = require('whatsapp-web.js');

const PORT = parseInt(process.env.PORT || '3001', 10);
const API_SECRET_KEY = process.env.API_SECRET_KEY || '';
const SESSION_PATH = path.resolve(process.env.SESSION_PATH || './session');
const ALLOWED_ORIGIN = process.env.ALLOWED_ORIGIN || '*';
const DEFAULT_COUNTRY_CODE = String(process.env.DEFAULT_COUNTRY_CODE || '20').replace(/\D/g, '') || '20';
const LARAVEL_INCOMING_WEBHOOK_URL = (process.env.LARAVEL_INCOMING_WEBHOOK_URL || '').trim();
const WHATSAPP_INTERNAL_SECRET = (process.env.WHATSAPP_INTERNAL_SECRET || '').trim();

const STATUS_DISCONNECTED = 'disconnected';
const STATUS_INITIALIZING = 'initializing';
const STATUS_QR_READY = 'qr_ready';
const STATUS_AUTHENTICATED = 'authenticated';
const STATUS_READY = 'ready';

/**
 * @param {string} phone
 * @returns {{ valid: boolean, formatted: string }}
 */
function normalizePhone(phone) {
  if (phone === undefined || phone === null) {
    return { valid: false, formatted: '' };
  }
  let digits = String(phone).replace(/\D/g, '');
  if (digits.startsWith('00')) {
    digits = digits.slice(2);
  }
  if (digits.startsWith('0')) {
    digits = DEFAULT_COUNTRY_CODE + digits.slice(1);
  }
  const len = digits.length;
  if (len < 7 || len > 15) {
    return { valid: false, formatted: digits };
  }
  return { valid: true, formatted: digits };
}

module.exports.normalizePhone = normalizePhone;

let client = null;
let qrBase64 = null;
let clientInfo = null;
let waStatus = STATUS_DISCONNECTED;
/** @type {string|null} آخر خطأ من client.initialize (للعرض في /api/status) */
let lastInitializeError = null;

function sleep(ms) {
  return new Promise(function (resolve) {
    setTimeout(resolve, ms);
  });
}

function clearQrAndInfo() {
  qrBase64 = null;
  clientInfo = null;
}

function forwardIncomingToLaravel(phone, body, messageId) {
  if (!LARAVEL_INCOMING_WEBHOOK_URL || !WHATSAPP_INTERNAL_SECRET) {
    return;
  }
  try {
    var u = new URL(LARAVEL_INCOMING_WEBHOOK_URL);
    var mod = u.protocol === 'https:' ? require('https') : require('http');
    var payload = JSON.stringify({
      phone: phone,
      message: body,
      message_id: messageId || null,
    });
    var opts = {
      method: 'POST',
      hostname: u.hostname,
      port: u.port || (u.protocol === 'https:' ? 443 : 80),
      path: (u.pathname || '/') + (u.search || ''),
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload),
        'X-WhatsApp-Internal': WHATSAPP_INTERNAL_SECRET,
        Accept: 'application/json',
      },
      timeout: 15000,
    };
    var req = mod.request(opts, function (res) {
      res.resume();
    });
    req.on('error', function (e) {
      console.error('forwardIncomingToLaravel', e.message);
    });
    req.write(payload);
    req.end();
  } catch (e) {
    console.error('forwardIncomingToLaravel url', e.message);
  }
}

function deleteSessionFolderContents() {
  try {
    if (!fs.existsSync(SESSION_PATH)) {
      return;
    }
    const entries = fs.readdirSync(SESSION_PATH);
    for (let i = 0; i < entries.length; i++) {
      const full = path.join(SESSION_PATH, entries[i]);
      fs.rmSync(full, { recursive: true, force: true });
    }
  } catch (err) {
    console.error('deleteSessionFolderContents', err);
  }
}

function attachClientEvents(c) {
  c.on('qr', async function (qr) {
    try {
      qrBase64 = await QRCode.toDataURL(qr);
      waStatus = STATUS_QR_READY;
      lastInitializeError = null;
    } catch (e) {
      console.error('QR generation failed', e);
      qrBase64 = null;
    }
  });

  c.on('authenticated', function () {
    qrBase64 = null;
    waStatus = STATUS_AUTHENTICATED;
  });

  c.on('ready', function () {
    waStatus = STATUS_READY;
    lastInitializeError = null;
    try {
      const info = c.info;
      clientInfo = {
        pushname: info && info.pushname ? info.pushname : '',
        phone: info && info.wid && info.wid.user ? info.wid.user : '',
      };
    } catch (e) {
      clientInfo = { pushname: '', phone: '' };
    }
  });

  c.on('auth_failure', function (msg) {
    console.error('auth_failure', msg);
    lastInitializeError = typeof msg === 'string' ? msg : 'auth_failure';
    waStatus = STATUS_DISCONNECTED;
    clearQrAndInfo();
  });

  c.on('disconnected', function (reason) {
    console.warn('disconnected', reason);
    waStatus = STATUS_DISCONNECTED;
    clearQrAndInfo();
  });

  c.on('message', async function (msg) {
    try {
      if (msg.fromMe) {
        return;
      }
      // تجنّب getChat() هنا — يزيد استدعاءات Puppeteer ويُسهّل أخطاء "detached Frame".
      var fromStr = String(msg.from || '');
      if (fromStr.endsWith('@g.us')) {
        return;
      }
      var fromRaw = msg.from || '';
      var phone = String(fromRaw)
        .replace(/@c.us$/i, '')
        .replace(/\D/g, '');
      if (phone.length < 7) {
        return;
      }
      var body = typeof msg.body === 'string' ? msg.body : '';
      if (!body.trim() && msg.hasMedia) {
        body = '[مرفق: ' + (msg.type || 'media') + ']';
      }
      var mid = '';
      if (msg.id && msg.id._serialized) {
        mid = msg.id._serialized;
      }
      forwardIncomingToLaravel(phone, body || ' ', mid);
    } catch (e) {
      console.error('message handler', e);
    }
  });
}

async function destroyExistingClient() {
  if (!client) {
    return;
  }
  try {
    await client.destroy();
  } catch (e) {
    console.warn('client.destroy error', e.message);
  }
  client = null;
}

/**
 * @param {boolean} resetSession حذف ملفات LocalAuth لإجبار ظهور QR من جديد
 */
async function initWhatsAppClient(resetSession) {
  await destroyExistingClient();
  clearQrAndInfo();
  lastInitializeError = null;
  waStatus = STATUS_INITIALIZING;

  if (resetSession) {
    deleteSessionFolderContents();
  }

  const dataPath = SESSION_PATH;
  if (!fs.existsSync(dataPath)) {
    fs.mkdirSync(dataPath, { recursive: true });
  }

  var clientOpts = {
    authStrategy: new LocalAuth({ dataPath: dataPath }),
    puppeteer: {
      headless: true,
      defaultViewport: null,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--disable-gpu',
      ],
    },
  };
  // افتراضياً ذاكرة النسخة المحلية (مكتبة whatsapp-web.js) — لا تعتمد على GitHub.
  // لضبط نسخة بعيدة: WA_WEB_VERSION_REMOTE=https://raw.githubusercontent.com/.../file.html
  var remoteVer = (process.env.WA_WEB_VERSION_REMOTE || '').trim();
  if (remoteVer) {
    clientOpts.webVersionCache = { type: 'remote', remotePath: remoteVer };
  }

  const newClient = new Client(clientOpts);

  attachClientEvents(newClient);
  client = newClient;

  client.initialize().catch(function (err) {
    console.error('initialize error', err);
    lastInitializeError = err && err.message ? err.message : String(err);
    waStatus = STATUS_DISCONNECTED;
    clearQrAndInfo();
  });
}

const app = express();

app.use(helmet({ contentSecurityPolicy: false }));
app.use(
  cors({
    origin: ALLOWED_ORIGIN === '*' ? true : ALLOWED_ORIGIN,
    credentials: true,
  })
);
app.use(morgan('combined'));
app.use(express.json({ limit: '2mb' }));

// لا تُحتسب طلبات المراقبة المتكررة (Laravel يستعلم /api/status و /health كثيراً).
// بخلاف ذلك كان الحد 100/15د يُستنفد خلال دقائق فيظهر "Too many requests".
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 500,
  standardHeaders: true,
  legacyHeaders: false,
  skip: function (req) {
    if (req.method !== 'GET') {
      return false;
    }
    var p = req.path || '';
    return p === '/health' || p === '/api/status';
  },
});
app.use(limiter);

function requireApiKey(req, res, next) {
  const key = req.headers['x-api-key'];
  if (!API_SECRET_KEY || key !== API_SECRET_KEY) {
    return res.status(401).json({ success: false, message: 'Unauthorized' });
  }
  next();
}

app.use(requireApiKey);

app.get('/health', function (req, res) {
  res.json({
    status: 'ok',
    uptime: process.uptime(),
    nodeVersion: process.version,
  });
});

app.get('/api/status', function (req, res) {
  res.json({
    status: waStatus,
    qr: qrBase64,
    info: clientInfo,
    last_error: lastInitializeError,
  });
});

app.post('/api/initialize', async function (req, res) {
  try {
    var body = req.body && typeof req.body === 'object' ? req.body : {};
    var resetSession = !!body.reset_session;
    await initWhatsAppClient(resetSession);
    res.json({ success: true, message: 'initializing' });
  } catch (e) {
    console.error(e);
    res.status(500).json({ success: false, message: e.message });
  }
});

app.post('/api/logout', async function (req, res) {
  try {
    if (client) {
      try {
        await client.logout();
      } catch (e) {
        console.warn('logout', e.message);
      }
      try {
        await client.destroy();
      } catch (e) {
        console.warn('destroy after logout', e.message);
      }
      client = null;
    }
    deleteSessionFolderContents();
    waStatus = STATUS_DISCONNECTED;
    clearQrAndInfo();
    res.json({ success: true, message: 'logged out' });
  } catch (e) {
    console.error(e);
    res.status(500).json({ success: false, message: e.message });
  }
});

app.post('/api/send', async function (req, res) {
  const phone = req.body && req.body.phone;
  const message = req.body && req.body.message;
  if (!message || typeof message !== 'string') {
    return res.status(400).json({ success: false, message: 'message required' });
  }
  const norm = normalizePhone(phone);
  if (!norm.valid) {
    return res.status(400).json({ success: false, message: 'Invalid phone number', phone: norm.formatted });
  }
  if (waStatus !== STATUS_READY || !client) {
    return res.status(503).json({ success: false, message: 'WhatsApp client not ready' });
  }
  const chatId = norm.formatted + '@c.us';
  try {
    const sent = await client.sendMessage(chatId, message);
    const ts = sent && sent.timestamp ? new Date(sent.timestamp * 1000).toISOString() : new Date().toISOString();
    res.json({
      success: true,
      messageId: sent && sent.id && sent.id._serialized ? sent.id._serialized : String(sent.id || ''),
      phone: norm.formatted,
      timestamp: ts,
    });
  } catch (e) {
    if (isPuppeteerSessionError(e)) {
      await markClientDeadAfterPuppeteerError('send', e);
      return res.status(503).json({
        success: false,
        message:
          'انقطعت جلسة المتصفح (Puppeteer). أوقف أي نسخة مكررة من الخدمة، ثم من لوحة واتساب اضغط تهيئة/مسح الجلسة وامسح QR من جديد.',
        code: 'WA_SESSION_DEAD',
      });
    }
    console.error('send', e);
    res.status(500).json({ success: false, message: e.message });
  }
});

app.post('/api/send-bulk', async function (req, res) {
  if (waStatus !== STATUS_READY || !client) {
    return res.status(503).json({ success: false, message: 'WhatsApp client not ready' });
  }
  const messages = req.body && req.body.messages;
  const delayMs =
    req.body && req.body.delay_ms !== undefined && req.body.delay_ms !== null
      ? parseInt(req.body.delay_ms, 10)
      : 1500;
  if (!Array.isArray(messages)) {
    return res.status(400).json({ success: false, message: 'messages array required' });
  }
  const results = [];
  let sent = 0;
  let failed = 0;
  for (let i = 0; i < messages.length; i++) {
    const item = messages[i];
    const norm = normalizePhone(item && item.phone);
    const msgText = item && item.message;
    if (!norm.valid || !msgText || typeof msgText !== 'string') {
      failed++;
      results.push({
        phone: norm.formatted || (item && item.phone) || '',
        success: false,
        error: !norm.valid ? 'Invalid phone' : 'Invalid message',
      });
    } else {
      const chatId = norm.formatted + '@c.us';
      try {
        const sentMsg = await client.sendMessage(chatId, msgText);
        sent++;
        results.push({
          phone: norm.formatted,
          success: true,
          messageId: sentMsg && sentMsg.id && sentMsg.id._serialized ? sentMsg.id._serialized : String(sentMsg.id || ''),
        });
      } catch (e) {
        if (isPuppeteerSessionError(e)) {
          await markClientDeadAfterPuppeteerError('send-bulk', e);
          failed++;
          results.push({
            phone: norm.formatted,
            success: false,
            error: e.message,
            code: 'WA_SESSION_DEAD',
          });
          break;
        }
        failed++;
        results.push({ phone: norm.formatted, success: false, error: e.message });
      }
    }
    if (i < messages.length - 1) {
      await sleep(isNaN(delayMs) ? 1500 : Math.max(0, delayMs));
    }
  }
  res.json({ sent: sent, failed: failed, results: results });
});

app.post('/api/validate-number', async function (req, res) {
  const phone = req.body && req.body.phone;
  const norm = normalizePhone(phone);
  if (!norm.valid) {
    return res.json({ valid: false, formatted: '', hasWhatsApp: false });
  }
  if (waStatus !== STATUS_READY || !client) {
    return res.json({ valid: true, formatted: norm.formatted, hasWhatsApp: null });
  }
  const chatId = norm.formatted + '@c.us';
  try {
    const registered = await client.isRegisteredUser(chatId);
    res.json({ valid: true, formatted: norm.formatted, hasWhatsApp: !!registered });
  } catch (e) {
    console.error('validate-number', e);
    res.json({ valid: true, formatted: norm.formatted, hasWhatsApp: null });
  }
});

function isPuppeteerSessionError(err) {
  if (!err || !err.message) {
    return false;
  }
  var m = String(err.message);
  return (
    m.indexOf('detached Frame') !== -1 ||
    m.indexOf('Target closed') !== -1 ||
    m.indexOf('Session closed') !== -1 ||
    m.indexOf('Protocol error') !== -1
  );
}

async function markClientDeadAfterPuppeteerError(context, err) {
  console.error(context, err && err.message ? err.message : err);
  waStatus = STATUS_DISCONNECTED;
  clearQrAndInfo();
  try {
    if (client) {
      await client.destroy();
    }
  } catch (destroyErr) {
    console.warn('markClientDead destroy', destroyErr.message);
  }
  client = null;
}

if (require.main === module) {
  var server = app.listen(PORT, function () {
    console.log('WhatsApp node service listening on port ' + PORT);
  });
  server.on('error', function (err) {
    if (err && err.code === 'EADDRINUSE') {
      console.error(
        'Port ' +
          PORT +
          ' is already in use (EADDRINUSE). Another node process is probably still running. ' +
          'Stop it first (Task Manager / netstat) or set a different PORT in .env.'
      );
      process.exit(1);
    }
    console.error('Server listen error', err);
    process.exit(1);
  });
}

module.exports.app = app;
