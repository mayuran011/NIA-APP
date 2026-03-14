import { Router } from 'express';
import { fetchAll, p } from '../db.js';

const router = Router();
const pre = () => p();

router.get('/', async (req, res) => {
  const q = String(req.query.q || '').trim();
  const type = req.query.type || 'all';
  const limit = Math.min(parseInt(req.query.limit, 10) || 24, 50);
  const pid = pre();
  const like = `%${q}%`;

  const result = { videos: [], music: [], images: [], channels: [], playlists: [] };

  if (!q) return res.json(result);

  if (type === 'all' || type === 'videos') {
    result.videos = await fetchAll(
      `SELECT id, title, thumb, duration, views, likes, type FROM ${pid}videos WHERE type = 'video' AND private = 0 AND (title LIKE ? OR description LIKE ?) ORDER BY views DESC LIMIT ?`,
      [like, like, limit]
    );
  }
  if (type === 'all' || type === 'music') {
    result.music = await fetchAll(
      `SELECT id, title, thumb, duration, views, likes, type FROM ${pid}videos WHERE type = 'music' AND private = 0 AND (title LIKE ? OR description LIKE ?) ORDER BY views DESC LIMIT ?`,
      [like, like, limit]
    );
  }
  if (type === 'all' || type === 'images') {
    result.images = await fetchAll(
      `SELECT id, title, thumb, path, views FROM ${pid}images WHERE title LIKE ? OR description LIKE ? OR tags LIKE ? ORDER BY views DESC LIMIT ?`,
      [like, like, like, limit]
    );
  }
  if (type === 'all' || type === 'channels') {
    result.channels = await fetchAll(
      `SELECT id, name, slug, thumb, type FROM ${pid}channels WHERE name LIKE ? OR description LIKE ? LIMIT ?`,
      [like, like, limit]
    );
  }

  res.json(result);
});

export default router;
