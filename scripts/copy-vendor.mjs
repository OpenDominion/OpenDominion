/**
 * Copies static vendor assets that cannot be bundled through Vite's
 * SCSS/JS pipeline (e.g. DataTables, which is loaded per-page).
 *
 * Run via: npm run vendor:copy
 * Also runs automatically on: npm install (postinstall)
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');

/**
 * Recursively copy a directory.
 */
function copyDir(src, dest) {
    fs.mkdirSync(dest, { recursive: true });
    for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
        const srcPath = path.join(src, entry.name);
        const destPath = path.join(dest, entry.name);
        if (entry.isDirectory()) {
            copyDir(srcPath, destPath);
        } else {
            fs.copyFileSync(srcPath, destPath);
        }
    }
}

/**
 * Copy a single file, creating the destination directory if needed.
 */
function copyFile(src, dest) {
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    fs.copyFileSync(src, dest);
}

const nm = path.join(root, 'node_modules');
const vendor = path.join(root, 'public', 'assets', 'vendor');
const app = path.join(root, 'public', 'assets', 'app');

const copies = [
    // App images — static assets not processed by Vite (replaces Laravel Mix's mix.copy)
    [path.join(root, 'app', 'resources', 'images'), path.join(app, 'images')],

    // DataTables Bootstrap 5 — loaded as a static asset per-page, not bundled
    [path.join(nm, 'datatables.net-bs5', 'js'),  path.join(vendor, 'datatables', 'js')],
    [path.join(nm, 'datatables.net-bs5', 'css'), path.join(vendor, 'datatables', 'css')],

    // Font Awesome webfonts — $fa-font-path in app.scss points here so Vite
    // doesn't attempt to resolve the font URLs at build time.
    [path.join(nm, '@fortawesome', 'fontawesome-free', 'webfonts'), path.join(vendor, 'font-awesome', 'webfonts')],

    // jQuery — loaded as a classic synchronous <script> before the Vite module
    // bundle, so window.$ is available to inline page scripts immediately.
    [path.join(nm, 'jquery', 'dist', 'jquery.min.js'), path.join(vendor, 'jquery', 'jquery.min.js')],
];

let ok = true;
for (const [src, dest] of copies) {
    const rel = path.relative(root, src);
    if (!fs.existsSync(src)) {
        console.error(`  [skip] ${rel} not found — run npm install first`);
        ok = false;
        continue;
    }
    const stat = fs.statSync(src);
    if (stat.isDirectory()) {
        copyDir(src, dest);
    } else {
        copyFile(src, dest);
    }
    console.log(`  [ok]   ${rel} -> ${path.relative(root, dest)}`);
}

if (!ok) {
    process.exit(1);
}
