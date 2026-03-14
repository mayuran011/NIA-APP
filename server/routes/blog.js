import { Router } from 'express';
import { fetchOne, fetchAll, p } from '../db.js';

const router = Router();
const pre = () => p();

router.get('/posts', async (req, res) => {
  const limit = Math.min(parseInt(req.query.limit, 10) || 12, 50);
  const offset = parseInt(req.query.offset, 10) || 0;
  const pid = pre();
  const rows = await fetchAll(
    `SELECT p.* FROM ${pid}posts p WHERE p.status = 'publish' ORDER BY p.created_at DESC LIMIT ? OFFSET ?`,
    [limit, offset]
  );
  res.json(rows);
});

router.get('/posts/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const pid = pre();
  const row = await fetchOne(`SELECT * FROM ${pid}posts WHERE id = ? AND status = 'publish' LIMIT 1`, [id]);
  if (!row) return res.status(404).json({ error: 'Not found' });
  res.json(row);
});

router.get('/pages/:slug', async (req, res) => {
  const pid = pre();
  const row = await fetchOne(`SELECT * FROM ${pid}pages WHERE slug = ? LIMIT 1`, [req.params.slug]);
  if (!row) return res.status(404).json({ error: 'Not found' });
  res.json(row);
});

export default router;
