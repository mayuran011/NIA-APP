import { Router } from 'express';
import { fetchOne, fetchAll, p } from '../db.js';
import { optionalAuth } from './auth.js';

const router = Router();
router.use(optionalAuth);
const pre = () => p();

router.get('/', async (req, res) => {
  const section = req.query.section || 'browse';
  const limit = Math.min(parseInt(req.query.limit, 10) || 24, 48);
  const offset = parseInt(req.query.offset, 10) || 0;
  const pid = pre();
  let order = 'v.created_at DESC';
  if (section === 'featured') {
    const rows = await fetchAll(
      `SELECT v.* FROM ${pid}videos v WHERE v.type = 'music' AND v.private = 0 AND v.featured = 1 ORDER BY v.created_at DESC LIMIT ? OFFSET ?`,
      [limit, offset]
    );
    return res.json(rows);
  }
  if (section === 'most-viewed') order = 'v.views DESC';
  else if (section === 'top-rated') order = 'v.likes DESC';
  const rows = await fetchAll(
    `SELECT v.* FROM ${pid}videos v WHERE v.type = 'music' AND v.private = 0 ORDER BY ${order} LIMIT ? OFFSET ?`,
    [limit, offset]
  );
  res.json(rows);
});

router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const pid = pre();
  const row = await fetchOne(`SELECT * FROM ${pid}videos WHERE id = ? AND type = 'music' AND (private = 0 OR user_id = ?) LIMIT 1`, [id, req.userId || 0]);
  if (!row) return res.status(404).json({ error: 'Not found' });
  res.json(row);
});

export default router;
