import mysql from 'mysql2/promise';

const prefix = process.env.DB_PREFIX || 'nia_';

let pool;

export function getPool() {
  if (!pool) {
    pool = mysql.createPool({
      host: process.env.DB_HOST,
      user: process.env.DB_USER,
      password: process.env.DB_PASSWORD,
      database: process.env.DB_NAME,
      charset: process.env.DB_CHARSET || 'utf8mb4',
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
    });
  }
  return pool;
}

export function p() {
  return prefix;
}

export async function query(sql, params = []) {
  const conn = await getPool().getConnection();
  try {
    const prepped = sql.replace(/#_/g, prefix);
    const [rows] = await conn.execute(prepped, params);
    return rows;
  } finally {
    conn.release();
  }
}

export async function fetchOne(sql, params = []) {
  const rows = await query(sql, params);
  return Array.isArray(rows) && rows.length ? rows[0] : null;
}

export async function fetchAll(sql, params = []) {
  const rows = await query(sql, params);
  return Array.isArray(rows) ? rows : [];
}
