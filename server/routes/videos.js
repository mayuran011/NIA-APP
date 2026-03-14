import { Router } from 'express';
import { fetchOne, fetchAll, query, p } from '../db.js';
import { authMiddleware } from './auth.js';

const router = Router();
const pre = () => p();

router.get('/', async (req, res) => {
  const section = req.query.section || 'browse';
  const limit = Math.min(parseInt(req.query.limit, 10) || 24, 48);
  const offset = parseInt(req.query.offset, 10) || 0;
  const pid = pre();
  let order = 'v.created_at DESC';
  if (section === 'featured') {
    const rows = await fetchAll(
      `SELECT v.* FROM ${pid}videos v WHERE v.type = 'video' AND v.private = 0 AND v.featured = 1 ORDER BY v.created_at DESC LIMIT ? OFFSET ?`,
      [limit, offset]
    );
    return res.json(rows);
  }
  if (section === 'most-viewed') order = 'v.views DESC';
  else if (section === 'top-rated') order = 'v.likes DESC';
  const rows = await fetchAll(
    `SELECT v.* FROM ${pid}videos v WHERE v.type = 'video' AND v.private = 0 ORDER BY ${order} LIMIT ? OFFSET ?`,
    [limit, offset]
  );
  res.json(rows);
});

router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const pid = pre();
  const video = await fetchOne(`SELECT * FROM ${pid}videos WHERE id = ? AND type = 'video' AND (private = 0 OR user_id = ?) LIMIT 1`, [id, req.userId || 0]);
  if (!video) return res.status(404).json({ error: 'Not found' });
  res.json(video);
});

router.get('/:id/related', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const limit = Math.min(parseInt(req.query.limit, 10) || 20, 30);
  const pid = pre();
  const video = await fetchOne(`SELECT category_id, user_id FROM ${pid}videos WHERE id = ? AND (private = 0 OR user_id = ?) LIMIT 1`, [id, req.userId || 0]);
  if (!video) return res.status(404).json({ error: 'Not found' });
  let rows;
  if (video.category_id > 0) {
    rows = await fetchAll(
      `SELECT v.* FROM ${pid}videos v WHERE v.id != ? AND v.private = 0 AND v.type = 'video' AND v.category_id = ? ORDER BY v.views DESC, v.created_at DESC LIMIT ?`,
      [id, video.category_id, limit]
    );
  } else {
    rows = await fetchAll(
      `SELECT v.* FROM ${pid}videos v WHERE v.id != ? AND v.private = 0 AND v.type = 'video' AND v.user_id = ? ORDER BY v.views DESC, v.created_at DESC LIMIT ?`,
      [id, video.user_id, limit]
    );
  }
  res.json(rows);
});

export default router;
