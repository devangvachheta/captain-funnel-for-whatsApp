# Build Instructions — Captain Funnel for WhatsApp

The `src/` directory contains the React + Redux source for the admin panel.  
The compiled files (`admin/js/capfw-react-app.js`, `admin/css/capfw-react-app.css`) are **already included** in the plugin zip — no build needed to use the plugin.

These instructions are for developers who want to modify the admin UI.

---

## Requirements

| Tool    | Version   |
|---------|-----------|
| Node.js | 18 or later |
| npm     | 9 or later  |

---

## Steps

```bash
# 1. Install dependencies
npm install

# 2. Production build (minified, for release)
npm run build

# 3. Development build with file watcher
npm run dev
```

Output files after build:

- `admin/js/capfw-react-app.js`
- `admin/css/capfw-react-app.css`

---

## Notes

- `react` and `react-dom` are **not bundled** — they come from WordPress core (`wp-element`).
- `@wordpress/i18n` is also external (`wp-i18n`), loaded by WordPress.
- All other dependencies (`@reduxjs/toolkit`, `react-redux`, `react-router-dom`) are bundled.
