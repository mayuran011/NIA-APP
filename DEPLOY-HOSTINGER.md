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
   | `SITE_URL`     | `https://msdeploy.com` |
   | `DB_HOST`      | `srv1368.hstgr.io` |
   | `DB_NAME`      | `u454323635_niaapp` |
   | `DB_USER`      | `u454323635_niaapp` |
   | `DB_PASSWORD`  | Your MySQL password |
   | `DB_CHARSET`   | `utf8mb4` |
   | `DB_PREFIX`    | `nia_` |
   | `JWT_SECRET`   | A long random string (e.g. 32+ chars) |

   If Hostinger uses a different port, they may set `PORT` automatically; otherwise set it as shown.

4. **Domain:** Point **msdeploy.com** to this Node.js app (in Hostinger: Domains → msdeploy.com → assign to the Node.js application).

5. Deploy / redeploy the app from the Node.js panel. After build and start, the site should be available at **https://msdeploy.com/**.

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
