import 'dotenv/config';
import path from 'path';
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

app.use(cors({ origin: isProduction ? process.env.SITE_URL : true, credentials: true }));
app.use(cookieParser());
app.use(express.json());

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

// Health check for Hostinger
app.get('/api/health', (req, res) => {
  res.json({ ok: true, env: process.env.NODE_ENV || 'development' });
});

// In production, serve React build
if (isProduction) {
  const clientBuild = path.join(__dirname, '..', 'client', 'dist');
  app.use(express.static(clientBuild));
  app.get('*', (req, res, next) => {
    if (req.path.startsWith('/api')) return next();
    res.sendFile(path.join(clientBuild, 'index.html'));
  });
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
    await getPool().getConnection();
  } catch (e) {
    console.error('Database connection failed:', e.message);
    process.exit(1);
  }
  app.listen(PORT, () => {
    console.log(`Nia App server listening on port ${PORT} (${isProduction ? 'production' : 'development'})`);
  });
}

start();
