# Frontend Architecture

## Build System

**Vite 5** with the `laravel-vite-plugin`. Laravel 8 doesn't have native Vite support, so a custom `@vite` Blade directive is provided by `src/Helpers/ViteHelper.php`, registered in `AppServiceProvider::boot()`.

### Entry Points

| Entry | Path |
|-------|------|
| SCSS | `app/resources/sass/app.scss` |
| JS | `app/resources/js/app.js` |

### Output

- Build directory: `public/assets/app/`
- Manifest: `public/assets/app/.vite/manifest.json`
- Dev server writes a `public/hot` file for HMR detection

### Commands

| Command | Purpose |
|---------|---------|
| `npm run dev` | Vite dev server with HMR |
| `npm run build` | Production build |
| `npm run vendor:copy` | Copy static vendor assets (also runs on `postinstall`) |

### Vendor Assets

Some libraries can't be bundled through Vite and are copied as static files by `scripts/copy-vendor.mjs`:

- **jQuery** (`public/assets/vendor/jquery/`) — loaded as a synchronous `<script>` before the Vite bundle so `window.$` is available to inline page scripts
- **Select2** (`public/assets/vendor/select2/`) — loaded synchronously for the same reason
- **Font Awesome webfonts** (`public/assets/vendor/font-awesome/webfonts/`) — `$fa-font-path` in SCSS points here so Vite doesn't try to resolve font URLs at build time
- **DataTables BS5** (`public/assets/vendor/datatables/`) — loaded per-page, not globally bundled
- **App images** (`public/assets/app/images/`) — static images copied from `app/resources/images/`

## CSS Framework

**Bootstrap 5** via **AdminLTE 4** class structure. Bootstrap variables are overridden before import in `app.scss`.

### SCSS Structure

```
app/resources/sass/
├── app.scss              # Main entry: BS5 vars, imports, global styles
├── _layout.scss          # CSS Grid shell (header, sidebar, main, footer)
├── _cards-ext.scss       # Card title sizing, btn-tool
├── _themes.scss          # Imports all theme partials
├── _theme-classic.scss   # Classic theme
├── _theme-dusk.scss      # Dusk theme
├── _theme-grimoire.scss  # Grimoire theme
└── _theme-parchment.scss # Parchment theme
```

### Layout

The page shell uses **CSS Grid** (defined in `_layout.scss`):

```
┌──────────┬──────────────────────────────┐
│          │        app-header            │
│  app-    ├──────────────────────────────┤
│  sidebar │        app-main              │
│          ├──────────────────────────────┤
│          │        app-footer            │
└──────────┴──────────────────────────────┘
```

Key classes:
- `.app-wrapper` — grid container
- `.app-header` — top navbar
- `.app-sidebar` — 250px fixed-width sidebar, collapses off-screen on mobile
- `.app-main > .app-content > .container-fluid` — page content
- `.app-footer` — bottom bar

Responsive: below 992px the sidebar becomes a fixed overlay toggled by a hamburger button. State is persisted in `localStorage` (`sidebar-collapsed`).

### Fonts

- **Default**: Source Sans 3 (Google Fonts, loaded in `partials/styles.blade.php`)
- **Dusk theme**: DM Sans (body), Space Grotesk (display/headings), JetBrains Mono (mono)
- **Parchment theme**: Crimson Pro (body), Cinzel (display/headings), JetBrains Mono (mono)
- **Grimoire theme**: Crimson Pro (body), Cinzel (display/headings), JetBrains Mono (mono)
- Fonts loaded via a single Google Fonts `<link>` tag

## JavaScript

### Module Structure

```
app/resources/js/
├── app.js            # Main entry: imports, tooltip init, form helpers
├── bootstrap-app.js  # Lodash, helpers, ticker globals on window
├── color-mode.js     # Theme switcher logic
├── sidebar.js        # Sidebar toggle + collapse persistence
├── momentum.js       # Game momentum/ticker display
├── ticker.js         # Server time ticker
├── helpers.js        # Utility functions (formatBytes, etc.)
└── jquery-global.js  # Shim: re-exports window.jQuery for import compatibility
```

### jQuery Strategy

jQuery is loaded as a **synchronous classic `<script>`** from the vendor copy, making `window.$` available immediately to inline page scripts. The Vite bundle imports jQuery through a shim (`jquery-global.js`) that re-exports `window.jQuery`, so bundled code and inline scripts share the same instance.

### Global Objects

Set on `window` by `bootstrap-app.js` and `app.js`:
- `window.$` / `window.jQuery` — jQuery
- `window._` — Lodash
- `window.ticker` — game ticker instance
- `window.formatBytes` — utility function

## Theme System

### How It Works

Three HTML attributes on `<html>` control theming:

| Attribute | Purpose | Example values |
|-----------|---------|----------------|
| `data-bs-theme` | Bootstrap's built-in light/dark toggle | `light`, `dark` |
| `data-color-mode` | Raw user selection (stored in localStorage) | `light`, `dark`, `grimoire`, `classic`, `parchment`, `dusk`, `auto` |
| `data-color-scheme` | Resolved theme name (never `auto`) | `light`, `dark`, `grimoire`, `classic`, `parchment`, `dusk` |

### Flash Prevention

An inline `<script>` in `layouts/master.blade.php` reads `localStorage` and sets all three attributes **before** CSS loads, preventing a flash of the wrong theme on page load.

### Theme Modes

| Mode | Base (`data-bs-theme`) | Description |
|------|----------------------|-------------|
| Light | `light` | Default light, dark sidebar/header |
| Dark | `dark` | Bootstrap's native dark mode |
| Classic | `dark` | Teal/cyan dark theme |
| Dusk | `dark` | Purple-on-black dark theme |
| Grimoire | `dark` | Warm brown/amber palette, serif fonts |
| Parchment | `light` | Warm parchment/paper tones |
| Auto | varies | Follows OS `prefers-color-scheme` |

### Theme File Structure

Each theme SCSS file is a self-contained `[data-color-scheme="<name>"]` selector block containing:

1. **Custom properties** (`--od-*`) defining the palette
2. **Bootstrap overrides** (`--bs-*`) mapping palette to Bootstrap's variable system
3. **Component overrides** for cards, tables, forms, buttons, etc.

### Custom Properties Convention

Themes define `--od-*` properties for their palette, then map them to `--bs-*` for Bootstrap integration:

| Property | Purpose |
|----------|---------|
| `--od-body-bg` | Page background |
| `--od-surface` | Card/panel background |
| `--od-surface-alt` | Alternate surface (slightly different shade) |
| `--od-muted-surface` | Muted surface (card headers, etc.) |
| `--od-deep` | Deepest shade |
| `--od-border-accent` | Primary border color |
| `--od-border-soft` | Subtle border color |
| `--od-text-body` | Body text |
| `--od-text-secondary` | Muted text |
| `--od-text-emphasis` | Emphasized text |
| `--od-text-subtle` | Very muted text |
| `--od-primary` | Brand/accent color |
| `--od-link-color` | Link color |
| `--od-font-display` | Display/heading font family |
| `--od-font-sans` | Body font family |
| `--od-font-mono` | Monospace font family |

### Color Mode Switcher

The UI is a dropdown in the navbar (`partials/color-mode-nav.blade.php`). Logic lives in `color-mode.js`:

- Stores selection in `localStorage` as `color-mode`
- `applyMode()` sets all three HTML attributes and updates `<meta name="theme-color">`
- `updateUI()` toggles active states and checkmark visibility in the dropdown
- Listens for OS `prefers-color-scheme` changes when in `auto` mode

### Light Theme Sidebar

The light theme has a special dark sidebar/header defined directly in `app.scss` under `[data-color-scheme="light"]`, not in a separate theme file.

## Key Blade Partials

| Partial | Purpose |
|---------|---------|
| `partials/styles.blade.php` | Vite CSS, Google Fonts, page style stacks |
| `partials/scripts.blade.php` | Vendor JS (jQuery, Select2), Vite JS, page script stacks |
| `partials/main-header.blade.php` | Top navbar (hamburger, tickers, dropdowns) |
| `partials/main-sidebar.blade.php` | Sidebar navigation |
| `partials/main-footer.blade.php` | Footer |
| `partials/tickers.blade.php` | Server time / round tickers in navbar |
| `partials/color-mode-nav.blade.php` | Theme switcher dropdown |
| `partials/links-nav.blade.php` | Collapsed utility links dropdown |
| `partials/notification-nav.blade.php` | Notifications dropdown |
| `partials/auth-user-nav.blade.php` | User account dropdown |

## Adding a New Theme

1. Create `app/resources/sass/_theme-<name>.scss` with a `[data-color-scheme="<name>"]` block
2. Import it in `_themes.scss`
3. Add the theme to the `darkModes` array (if dark-based) in:
   - `color-mode.js` (`applyMode` function)
   - `layouts/master.blade.php` (flash-prevention script)
   - Any other layout files using the inline flash-prevention pattern (`topnav.blade.php`, `staff.blade.php`, `errors/503.blade.php`)
4. Add a `THEME_COLORS` entry in `color-mode.js` for `<meta name="theme-color">`
5. Add an `ICONS` entry in `color-mode.js`
6. Add a button to `partials/color-mode-nav.blade.php`
7. Optionally add `.app-header .ticker` overrides for navbar ticker styling
