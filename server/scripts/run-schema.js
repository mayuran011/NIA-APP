/**
 * Run lib/schema.sql to create or update database tables (prefix: nia_).
 * Usage: node server/scripts/run-schema.js
 * Requires: .env with DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PREFIX (optional)
 */
import 'dotenv/config';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import mysql from 'mysql2/promise';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const prefix = process.env.DB_PREFIX || 'nia_';

function loadSchema() {
  const schemaPath = path.join(__dirname, '..', '..', 'lib', 'schema.sql');
  if (!fs.existsSync(schemaPath)) {
    throw new Error(`Schema file not found: ${schemaPath}`);
  }
  return fs.readFileSync(schemaPath, 'utf8');
}

function getStatements(sql) {
  const withPrefix = sql.replace(/#_/g, prefix);
  return withPrefix
    .split(';')
    .map((s) => s.trim())
    .filter((s) => s.length > 0 && !s.startsWith('--'));
}

async function run() {
  if (!process.env.DB_HOST || !process.env.DB_USER || !process.env.DB_NAME) {
    console.error('Missing DB_HOST, DB_USER, or DB_NAME in environment. Set them in .env or export.');
    process.exit(1);
  }

  const schema = loadSchema();
  const statements = getStatements(schema);

  const conn = await mysql.createConnection({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    charset: process.env.DB_CHARSET || 'utf8mb4',
    multipleStatements: false,
  });

  console.log(`Database: ${process.env.DB_NAME} @ ${process.env.DB_HOST} (prefix: ${prefix})`);
  console.log(`Running ${statements.length} statements...`);

  let done = 0;
  let errors = 0;

  for (const stmt of statements) {
    if (stmt.startsWith('--')) continue;
    try {
      await conn.execute(stmt);
      done++;
      const preview = stmt.slice(0, 50).replace(/\s+/g, ' ');
      console.log(`  [${done}] ${preview}...`);
    } catch (err) {
      if (err.code === 'ER_TABLE_EXISTS_ERROR' || err.message?.includes('already exists')) {
        done++;
        console.log(`  [${done}] (skipped - exists) ${stmt.slice(0, 40)}...`);
      } else {
        errors++;
        console.error(`  ERROR: ${err.message}`);
        console.error(`  Statement: ${stmt.slice(0, 80)}...`);
      }
    }
  }

  await conn.end();
  console.log(`Done. ${done} statements executed, ${errors} errors.`);
  if (errors > 0) process.exit(1);
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
