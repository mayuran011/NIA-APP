# Deploy Nia App (React + Node.js) on Hostinger via GitHub

This app runs as a **Node.js web app** on Hostinger (hPanel → Node.js), using **Remote MySQL** and deployment from **GitHub**. Domain: **https://msdeploy.com/**

**If you see "Unsupported framework or invalid project structure":** The repo has a root `package.json` (with `main`, `dependencies.express`) and a root `vite.config.js` so Hostinger can detect **Vite + Express**. Ensure you selected **Node.js Apps** (not PHP) when adding the website. Then set the build commands below manually if needed.

## 1. Database (already set up)

- **MySQL host:** `srv1368.hstgr.io`
- **Database:** `u454323635_niaapp`
- **User:** `u454323635_niaapp`
- **Password:** (use the one you have)

Ensure the schema is applied (same as the PHP app): run `lib/schema.sql` on this database if you haven’t already (e.g. from phpMyAdmin or MySQL client).

## 2. GitHub

1. Create a repo on GitHub (e.g. `yourusername/nia-app`).
2. Push this project:

   ```bash
   git remote add origin https://github.com/yourusername/nia-app.git
   git branch -M main
   git push -u origin main
   ```

3. Do **not** commit `.env`. It’s listed in `.gitignore`. Use Hostinger environment variables instead.

## 3. Hostinger (hPanel) – Node.js app

1. In **hPanel**, go to **Advanced** → **Node.js** (or **Node.js web app**).
2. **Create application:**
   - Connect **GitHub**: choose the repo and branch (e.g. `main`).
   - **Install command:** `npm install` (root install + postinstall installs server deps).
   - **Build command:** `npm run build` (runs Vite; builds React app to `client/dist`).
   - **Start command:** `npm start` (runs `node server/index.js`; serves API + static React app).
   - **Application root:** leave default (repository root).
   - If the UI only shows one “Build” field, use: `npm install && npm run build`.

3. **Environment variables** (in the same Node.js app settings):

   | Name           | Value |
   |----------------|--------|
   | `NODE_ENV`     | `production` |
   | `PORT`         | `3000` (or the port Hostinger assigns) |
   | `SITE_URL`     | Your app URL (e.g. `https://mediumslateblue-hawk-820028.hostingersite.com`) |
   | `DB_HOST`      | `srv1368.hstgr.io` or `195.35.59.7` (MySQL server IP if hostname fails) |
   | `DB_NAME`      | `u454323635_niaapp` |
   | `DB_USER`      | `u454323635_niaapp` |
   | `DB_PASSWORD`  | Your MySQL password |
   | `DB_CHARSET`   | `utf8mb4` |
   | `DB_PREFIX`    | `nia_` |
   | `JWT_SECRET`   | A long random string (e.g. 32+ chars) |

   If Hostinger uses a different port, they may set `PORT` automatically; otherwise set it as shown.

4. **Domain:** Point **msdeploy.com** to this Node.js app (in Hostinger: Domains → msdeploy.com → assign to the Node.js application).

5. Deploy / redeploy the app from the Node.js panel. After build and start, the site should be available at **https://msdeploy.com/**.

## 3b. If you see **503 Service Unavailable**

The app is not running or not reachable. Do this:

1. **Set SITE_URL to your actual domain**  
   For this app use: `https://mediumslateblue-hawk-820028.hostingersite.com` (no trailing slash). If DB connection fails, try `DB_HOST=195.35.59.7` instead of `srv1368.hstgr.io`.

2. **Check deployment / build logs**  
   In hPanel → your Node.js app → **Deployments** (or **Build logs**). Open the latest deployment and look for errors **after** the build step (e.g. "Database connection failed", "Server failed to start", or a stack trace). The app exits on DB failure, so a wrong DB host/password will cause 503.

2. **Environment variables**  
   Confirm in the Node.js app **Environment variables** that all of these are set and correct:
   - `NODE_ENV` = `production`
   - `PORT` = the value Hostinger gives you (often they inject this; if there’s a placeholder like `$PORT`, use that or leave PORT unset only if their docs say so)
   - `SITE_URL` = `https://mediumslateblue-hawk-820028.hostingersite.com`
   - `DB_HOST` = `srv1368.hstgr.io` or `195.35.59.7`
   - `DB_NAME`, `DB_USER`, `DB_PASSWORD` (e.g. `u454323635_niaapp`)
   - `JWT_SECRET` = a long random string (32+ chars)

3. **Database**  
   Remote MySQL must accept connections from Hostinger’s IP. In hPanel → **Databases** → Remote MySQL, add Hostinger’s outbound IP or use “Allow all” for testing. If the app can’t connect, it exits on startup and you get 503.

4. **Redeploy**  
   After changing env vars or DB access, use **Redeploy** (or trigger a new deployment) so the app restarts with the new config.

## 4. Local development

```bash
# Install dependencies (root + server + client)
npm run install:all

# Create .env from .env.example and set DB_* and JWT_SECRET
cp .env.example .env

# Run API (port 3000) and React dev server (port 5173) with proxy to /api
npm run dev
```

- Frontend: http://localhost:5173  
- API: http://localhost:3000/api  

## 5. Project layout

| Path        | Purpose |
|------------|---------|
| `server/`  | Node.js (Express) API; MySQL; serves built client in production |
| `client/`  | React (Vite) SPA; build output in `client/dist` |
| `lib/schema.sql` | MySQL schema (same as PHP app; prefix `nia_`) |
| `.env.example` | Template for env vars; copy to `.env` locally |

The PHP app (views, moderator, ajax, etc.) remains in the repo for reference or gradual migration; the live stack is React + Node.js using the same database.

## 6. Update database (schema)

To create or sync tables (e.g. after a new DB or schema change):

- **From your machine:** ensure `.env` has `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` (and optional `DB_PREFIX`). Then run:
  ```bash
  npm run db:schema
  ```
  This runs `lib/schema.sql` (CREATE TABLE IF NOT EXISTS, INSERT IGNORE) against your database.

- **Existing installs:** if tables already exist and you only need new columns, run `lib/schema-updates.sql` in phpMyAdmin (it uses stored procedures and is intended for manual run).
