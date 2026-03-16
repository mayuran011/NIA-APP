import { Router } from 'express';
import { fetchOne, fetchAll, p } from '../db.js';

const router = Router();
const pre = () => p();

router.get('/', async (req, res) => {
  const limit = Math.min(parseInt(req.query.limit, 10) || 24, 48);
  const offset = parseInt(req.query.offset, 10) || 0;
  const pid = pre();
  const rows = await fetchAll(
    `SELECT * FROM ${pid}images ORDER BY created_at DESC LIMIT ? OFFSET ?`,
    [limit, offset]
  );
  res.json(rows);
});

router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const pid = pre();
  const row = await fetchOne(`SELECT * FROM ${pid}images WHERE id = ? LIMIT 1`, [id]);
  if (!row) return res.status(404).json({ error: 'Not found' });
  res.json(row);
});

export default router;
