# Finesse — Luxury Digital Fashion-Tech Platform

**Tagline:** Plan it. Style it. Own your look.

A premium full-stack wardrobe management & AI-powered outfit planning platform.

## Tech Stack

- **Frontend:** HTML5, CSS3, Vanilla JS (modular), GSAP-style animations
- **Backend:** PHP (Core) + MySQL
- **Environment:** XAMPP (Apache + MySQL + PHP 8+)

## Setup (XAMPP)

1. **Install XAMPP** and start Apache + MySQL.
2. Copy the entire `finesse/` folder into `C:/xampp/htdocs/`.
3. Open phpMyAdmin → create database `finesse_db` → import `database/schema.sql`.
4. Edit `backend/config.php` if your MySQL user/password differ from defaults (`root` / empty).
5. Visit: `http://localhost/finesse/frontend/index.html`
6. Admin panel: `http://localhost/finesse/backend/admin/login.php`
   - Default admin: `admin@finesse.com` / `admin123`

## Project Structure

```
finesse/
├── frontend/         # All public-facing pages
│   ├── index.html        # Landing
│   ├── login.html        # Auth
│   ├── signup.html
│   ├── dashboard.html    # User dashboard
│   ├── closet.html       # Digital wardrobe
│   ├── diva.html         # Diva Studio (drag-drop styling)
│   ├── planner.html      # Calendar + weather
│   ├── css/
│   ├── js/
│   └── assets/
├── backend/
│   ├── config.php
│   ├── db.php
│   ├── auth.php
│   ├── outfit-engine.php
│   ├── weather-api.php
│   └── admin/            # Admin panel
└── database/
    └── schema.sql
```

## Features

- Premium editorial UI (ivory / black / champagne gold)
- Dark / Light mode
- User auth (PHP sessions, hashed passwords)
- Digital closet with categories & image upload
- Diva Studio: drag-drop outfit builder
- AI outfit recommendation engine (color + category + weather)
- Calendar planner & weather-based suggestions
- Floating AI chatbot
- Admin panel (users, items, categories, feedback)
- Accessibility toolbar (TTS, high contrast, keyboard nav)
- WhatsApp floating button

## Security

- `password_hash()` / `password_verify()`
- Prepared statements (PDO) — SQL injection safe
- Session-based auth + CSRF tokens on forms
- Input sanitization helpers in `auth.php`

---

© Finesse 2026 — Luxury fashion, reimagined.
