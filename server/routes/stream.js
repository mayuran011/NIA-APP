import { Router } from 'express';
import path from 'path';
import fs from 'fs';
import { fetchOne, p } from '../db.js';
import { optionalAuth } from './auth.js';

const router = Router();
router.use(optionalAuth);
const MEDIA = process.env.MEDIA_FOLDER || path.join(process.cwd(), 'media');
const MIMES = { mp4: 'video/mp4', webm: 'video/webm', mp3: 'audio/mpeg', m4a: 'audio/mp4', ogg: 'audio/ogg' };

router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  if (!id) return res.status(404).end();
  const pre = p();
  const video = await fetchOne(`SELECT * FROM ${pre}videos WHERE id = ? AND source = 'local' AND (private = 0 OR user_id = ?) LIMIT 1`, [id, req.userId || 0]);
  if (!video) return res.status(404).end();

  let filePath = null;
  const videoDir = path.join(MEDIA, 'videos', String(id));
  const defaultFile = path.join(videoDir, 'default.mp4');
  const legacyFile = path.join(MEDIA, `${id}.mp4`);

  if (video.file_path) {
    const fromDb = path.join(MEDIA, video.file_path.replace(/[/\\]/g, path.sep));
    if (fs.existsSync(fromDb) && fs.statSync(fromDb).isFile()) filePath = fromDb;
  }
  if (!filePath && fs.existsSync(defaultFile)) filePath = defaultFile;
  if (!filePath && fs.existsSync(legacyFile)) filePath = legacyFile;

  if (!filePath || !fs.statSync(filePath).isFile()) return res.status(404).end();

  const ext = path.extname(filePath).slice(1).toLowerCase();
  const mime = MIMES[ext] || 'application/octet-stream';
  const stat = fs.statSync(filePath);
  const size = stat.size;
  const range = req.headers.range;

  res.setHeader('Content-Type', mime);

  if (range) {
    const parts = range.replace(/bytes=/, '').trim().split('-');
    const startNum = parseInt(parts[0], 10);
    const endRaw = parts[1];
    const endNum = (endRaw === '' || endRaw === undefined) ? undefined : parseInt(endRaw, 10);
    const s = Number.isNaN(startNum) ? 0 : Math.max(0, startNum);
    const e = (endNum !== undefined && !Number.isNaN(endNum)) ? Math.min(endNum, size - 1) : size - 1;
    const chunk = Math.max(0, e - s + 1);
    res.status(206);
    res.setHeader('Content-Range', `bytes ${s}-${e}/${size}`);
    res.setHeader('Accept-Ranges', 'bytes');
    res.setHeader('Content-Length', chunk);
    const stream = fs.createReadStream(filePath, { start: s, end: e });
    stream.pipe(res);
  } else {
    res.setHeader('Content-Length', size);
    res.setHeader('Accept-Ranges', 'bytes');
    fs.createReadStream(filePath).pipe(res);
  }
});

export default router;
