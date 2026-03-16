import { Router } from 'express';
import { fetchOne, fetchAll, p } from '../db.js';
import { authMiddleware } from './auth.js';

const router = Router();
const pre = () => p();

router.get('/profile/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const pid = pre();
  const user = await fetchOne(
    `SELECT id, username, name, avatar, group_id, created_at FROM ${pid}users WHERE id = ?`,
    [id]
  );
  if (!user) return res.status(404).json({ error: 'Not found' });
  const subRows = await fetchAll(`SELECT COUNT(*) as c FROM ${pid}users_friends WHERE friend_id = ?`, [id]);
  const subscriber_count = subRows[0]?.c ?? 0;
  res.json({ ...user, subscriber_count });
});

router.get('/by-username/:username', async (req, res) => {
  const pid = pre();
  const user = await fetchOne(
    `SELECT id, username, name, avatar, group_id, created_at FROM ${pid}users WHERE username = ?`,
    [req.params.username]
  );
  if (!user) return res.status(404).json({ error: 'Not found' });
  res.json(user);
});

export default router;
