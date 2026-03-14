import { Router } from 'express';
import { fetchAll, fetchOne, p } from '../db.js';

const router = Router();
let optionsCache = null;

async function loadOptions() {
  if (optionsCache) return optionsCache;
  const pre = p();
  const rows = await fetchAll(`SELECT name, value FROM ${pre}options WHERE autoload = 1`);
  optionsCache = Object.fromEntries(rows.map((r) => [r.name, r.value]));
  return optionsCache;
}

router.get('/', async (req, res) => {
  const opts = await loadOptions();
  res.json(opts);
});

router.get('/:key', async (req, res) => {
  const opts = await loadOptions();
  const value = opts[req.params.key] ?? null;
  res.json({ key: req.params.key, value });
});

export default router;
export { loadOptions };
