import { Router } from 'express';
import { fetchAll, fetchOne, p } from '../db.js';

const router = Router();
const pre = () => p();

router.get('/', async (req, res) => {
  const type = req.query.type || 'video';
  const pid = pre();
  const rows = await fetchAll(
    `SELECT * FROM ${pid}channels WHERE type = ? ORDER BY sort_order ASC, name ASC`,
    [type]
  );
  res.json(rows);
});

router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const pid = pre();
  const row = await fetchOne(`SELECT * FROM ${pid}channels WHERE id = ? LIMIT 1`, [id]);
  if (!row) return res.status(404).json({ error: 'Not found' });
  res.json(row);
});

export default router;
