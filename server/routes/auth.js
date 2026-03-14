import { Router } from 'express';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { fetchOne, query, p } from '../db.js';

const router = Router();
const JWT_SECRET = process.env.JWT_SECRET || 'change-in-production';
const COOKIE_OPTS = { httpOnly: true, secure: process.env.NODE_ENV === 'production', sameSite: 'lax', maxAge: 7 * 24 * 60 * 60 * 1000 };

function signToken(id) {
  return jwt.sign({ uid: id }, JWT_SECRET, { expiresIn: '7d' });
}

function authMiddleware(req, res, next) {
  const token = req.cookies?.token || req.headers.authorization?.replace('Bearer ', '');
  if (!token) return res.status(401).json({ error: 'Unauthorized' });
  try {
    const { uid } = jwt.verify(token, JWT_SECRET);
    req.userId = uid;
    next();
  } catch {
    res.status(401).json({ error: 'Unauthorized' });
  }
}

/** Optional: set req.userId if valid token, else req.userId = 0 */
function optionalAuth(req, res, next) {
  const token = req.cookies?.token || req.headers.authorization?.replace('Bearer ', '');
  req.userId = 0;
  if (token) {
    try {
      const { uid } = jwt.verify(token, JWT_SECRET);
      req.userId = uid;
    } catch {}
  }
  next();
}

router.post('/login', async (req, res) => {
  const { email, password } = req.body || {};
  if (!email || !password) return res.status(400).json({ error: 'Email and password required' });
  const pre = p();
  const user = await fetchOne(`SELECT id, username, name, email, avatar, group_id, password FROM ${pre}users WHERE email = ? LIMIT 1`, [email.trim()]);
  if (!user || !(await bcrypt.compare(password, user.password))) {
    return res.status(401).json({ error: 'invalid_credentials' });
  }
  await query(`UPDATE ${pre}users SET last_login = NOW() WHERE id = ?`, [user.id]);
  const { password: _, ...safe } = user;
  const token = signToken(user.id);
  res.cookie('token', token, COOKIE_OPTS).json({ ok: true, user: safe });
});

router.post('/register', async (req, res) => {
  const { username, name, email, password } = req.body || {};
  const userStr = String(username || '').trim().replace(/[^a-zA-Z0-9_\-]/g, '');
  if (userStr.length < 2) return res.status(400).json({ error: 'username_invalid' });
  if (!name?.trim()) return res.status(400).json({ error: 'Name required' });
  if (!email?.trim()) return res.status(400).json({ error: 'Email required' });
  if (String(password).length < 6) return res.status(400).json({ error: 'password_short' });

  const pre = p();
  const existingUser = await fetchOne(`SELECT id FROM ${pre}users WHERE username = ? OR email = ? LIMIT 1`, [userStr, email.trim()]);
  if (existingUser) {
    const byEmail = await fetchOne(`SELECT id FROM ${pre}users WHERE email = ? LIMIT 1`, [email.trim()]);
    return res.status(400).json({ error: byEmail ? 'email_taken' : 'username_taken' });
  }

  const hash = await bcrypt.hash(password, 10);
  await query(
    `INSERT INTO ${pre}users (group_id, username, name, email, password) VALUES (?, ?, ?, ?, ?)`,
    [4, userStr, name.trim(), email.trim(), hash]
  );
  const [inserted] = await query(`SELECT id, username, name, email, avatar, group_id FROM ${pre}users WHERE id = LAST_INSERT_ID()`);
  const token = signToken(inserted.id);
  res.cookie('token', token, COOKIE_OPTS).status(201).json({ ok: true, user: inserted });
});

router.post('/logout', (req, res) => {
  res.clearCookie('token').json({ ok: true });
});

router.get('/me', authMiddleware, async (req, res) => {
  const pre = p();
  const user = await fetchOne(
    `SELECT id, username, name, email, avatar, group_id, last_login, premium_upto, created_at FROM ${pre}users WHERE id = ?`,
    [req.userId]
  );
  if (!user) return res.status(401).json({ error: 'Unauthorized' });
  res.json(user);
});

export default router;
export { authMiddleware, optionalAuth };
