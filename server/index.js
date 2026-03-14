import 'dotenv/config';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import express from 'express';
import cors from 'cors';
import cookieParser from 'cookie-parser';
import { getPool } from './db.js';
import authRoutes from './routes/auth.js';
import optionsRoutes from './routes/options.js';
import videosRoutes from './routes/videos.js';
import musicRoutes from './routes/music.js';
import imagesRoutes from './routes/images.js';
import channelsRoutes from './routes/channels.js';
import usersRoutes from './routes/users.js';
import searchRoutes from './routes/search.js';
import blogRoutes from './routes/blog.js';
import streamRoutes from './routes/stream.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const isProduction = process.env.NODE_ENV === 'production';
const PORT = process.env.PORT || 3000;

const app = express();
app.locals.dbDown = false;

app.use(cors({ origin: isProduction ? process.env.SITE_URL : true, credentials: true }));
app.use(cookieParser());
app.use(express.json());

// If DB failed at startup, return 503 for all API calls (so the app still starts and can serve static)
app.use('/api', (req, res, next) => {
  if (app.locals.dbDown) {
    return res.status(503).json({ error: 'Database unavailable', code: 'DB_DOWN' });
  }
  next();
});

// API routes
app.use('/api/auth', authRoutes);
app.use('/api/options', optionsRoutes);
app.use('/api/videos', videosRoutes);
app.use('/api/music', musicRoutes);
app.use('/api/images', imagesRoutes);
app.use('/api/channels', channelsRoutes);
app.use('/api/users', usersRoutes);
app.use('/api/search', searchRoutes);
app.use('/api/blog', blogRoutes);
app.use('/api/stream', streamRoutes);

// Health check for Hostinger (reports DB status)
app.get('/api/health', (req, res) => {
  if (app.locals.dbDown) {
    return res.status(503).json({ ok: false, error: 'Database unavailable', env: process.env.NODE_ENV || 'development' });
  }
  res.json({ ok: true, env: process.env.NODE_ENV || 'development' });
});

// In production, serve React build (with fallback if dist missing so server never crashes)
if (isProduction) {
  const clientBuild = path.join(__dirname, '..', 'client', 'dist');
  const indexHtml = path.join(clientBuild, 'index.html');
  const hasBuild = fs.existsSync(indexHtml);

  if (hasBuild) {
    app.use(express.static(clientBuild));
    app.get('*', (req, res, next) => {
      if (req.path.startsWith('/api')) return next();
      res.sendFile(indexHtml, (err) => {
        if (err && !res.headersSent) res.status(500).send('Error loading app');
      });
    });
  } else {
    console.warn('Production build not found at', clientBuild, '- serving placeholder. Run: npm run build');
    app.get('*', (req, res, next) => {
      if (req.path.startsWith('/api')) return next();
      res.type('html').status(200).send(`
        <!DOCTYPE html><html><head><meta charset="utf-8"><title>Nia App</title></head>
        <body style="font-family:sans-serif;padding:2rem;background:#0f0f12;color:#f4f4f5;">
          <h1>Deploy in progress</h1>
          <p>Build output not found. Ensure <code>npm run build</code> ran successfully. Check deployment logs.</p>
          <p><a href="/api/health" style="color:#6366f1;">/api/health</a></p>
        </body></html>
      `);
    });
  }
}

// 404 for unknown API
app.use('/api/*', (req, res) => {
  res.status(404).json({ error: 'Not found' });
});

app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).json({ error: 'Internal server error' });
});

async function start() {
  try {
    const conn = await getPool().getConnection();
    conn.release();
  } catch (e) {
    console.error('Database connection failed:', e.message);
    console.error('Server will start anyway; API will return 503 until DB is fixed. Check DB_HOST, DB_USER, DB_PASSWORD, and Remote MySQL access.');
    app.locals.dbDown = true;
  }
  const host = process.env.HOST || '0.0.0.0';
  app.listen(PORT, host, () => {
    console.log(`Nia App server listening on ${host}:${PORT} (${isProduction ? 'production' : 'development'})${app.locals.dbDown ? ' [DB unavailable]' : ''}`);
  }).on('error', (err) => {
    console.error('Server failed to start:', err.message);
    process.exit(1);
  });
}

start().catch((err) => {
  console.error('Startup error:', err);
  process.exit(1);
});
