/*
 * Build des images de marque du site public (frontend).
 *
 *   node scripts/build-brand.mjs       (ou : npm run build:brand)
 *
 * Deux sorties, versionnées dans git comme le reste de public/assets (le
 * serveur de prod n'a ni Node ni Chrome) :
 *
 *   - Favicons : src/brand/icon.svg → public/favicon.ico (16/32/48),
 *     public/assets/brand/icon.svg et apple-touch-icon.png (180).
 *
 *   - Carte de partage : src/brand/og-default.html, rendu par un Chrome sans
 *     interface en 1200 × 630 → public/assets/brand/og-default.png.
 *
 * Séparé de build.mjs (CSS/JS/images) parce qu'il a besoin de Chrome : un build
 * d'assets ordinaire ne doit pas échouer sur une machine qui ne l'a pas. À
 * relancer seulement quand le logo ou le contenu de la carte changent.
 *
 * Chrome est cherché aux emplacements habituels de macOS et Linux, ou dans
 * CHROME_PATH. La page est ouverte en file:// ; les polices et le logo 3D y sont
 * injectés en base64 avant rendu, Chrome refusant les requêtes file:// croisées.
 */

import { execFileSync } from 'node:child_process';
import {
  existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import sharp from 'sharp';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const SRC = join(ROOT, 'src', 'brand');
const PUBLIC = join(ROOT, 'public');
const OUT = join(PUBLIC, 'assets', 'brand');

/* Densité de rastérisation du SVG : large, puis réduit à la taille cible —
   un downscale donne un bord plus net qu'un rendu direct en petite taille. */
const DENSITY = 1200;

/**
 * Les favicons à produire, hors ICO. [fichier, côté en px].
 *
 * Volontairement court : le SVG couvre les navigateurs modernes, l'ICO les
 * autres, et le PNG 180 px l'icône d'écran d'accueil iOS, qui n'accepte ni SVG
 * ni fond transparent. Tout ce qui n'est pas référencé par l'en-tête
 * (frontend/includes/header.php) n'a pas à être produit.
 */
const ICONS = [
  ['apple-touch-icon.png', 180],
];

/** Tailles embarquées dans favicon.ico (onglets, favoris, raccourcis Windows). */
const ICO_SIZES = [16, 32, 48];

/** Dimensions de la carte de partage, format attendu par Open Graph. */
const OG = { width: 1200, height: 630 };

/** Polices du site injectées dans la carte (public/assets/fonts). */
const OG_FONTS = [
  ['Spectral', 'normal', 400, 'spectral-400-latin.woff2'],
  ['Spectral', 'italic', 400, 'spectral-400-italic-latin.woff2'],
  ['Spectral', 'normal', 600, 'spectral-600-latin.woff2'],
  ['Spectral', 'italic', 600, 'spectral-600-italic-latin.woff2'],
  ['Spectral', 'normal', 700, 'spectral-700-latin.woff2'],
  ['Spline Sans Mono', 'normal', 500, 'spline-sans-mono-500-latin.woff2'],
];

/** Logo 3D affiché sur la carte (sortie de build.mjs). */
const OG_LOGO = join(PUBLIC, 'assets', 'img', 'logo3d-hero.webp');

/* ------------------------------------------------------------------ outils */

/**
 * Chemin de l'exécutable Chrome, ou null s'il est introuvable.
 * CHROME_PATH l'emporte, pour une installation hors des sentiers battus.
 */
function findChrome() {
  const candidates = [
    process.env.CHROME_PATH,
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/Applications/Chromium.app/Contents/MacOS/Chromium',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
  ];
  return candidates.find((p) => p && existsSync(p)) ?? null;
}

/**
 * Conteneur ICO enveloppant des PNG.
 *
 * Format : un en-tête de 6 octets, puis une entrée de 16 octets par image
 * (dimensions, taille et position des données), puis les PNG bout à bout.
 * Windows et les navigateurs acceptent le PNG dans un ICO depuis Vista, ce qui
 * évite d'avoir à produire du bitmap brut.
 */
function buildIco(pngs) {
  const header = Buffer.alloc(6);
  header.writeUInt16LE(0, 0);            // réservé
  header.writeUInt16LE(1, 2);            // type : 1 = icône
  header.writeUInt16LE(pngs.length, 4);  // nombre d'images

  let offset = 6 + pngs.length * 16;
  const entries = pngs.map(({ size, data }) => {
    const entry = Buffer.alloc(16);
    entry.writeUInt8(size >= 256 ? 0 : size, 0);  // largeur (0 = 256)
    entry.writeUInt8(size >= 256 ? 0 : size, 1);  // hauteur
    entry.writeUInt8(0, 2);                       // couleurs de palette
    entry.writeUInt8(0, 3);                       // réservé
    entry.writeUInt16LE(1, 4);                    // plans
    entry.writeUInt16LE(32, 6);                   // bits par pixel
    entry.writeUInt32LE(data.length, 8);
    entry.writeUInt32LE(offset, 12);
    offset += data.length;
    return entry;
  });

  return Buffer.concat([header, ...entries, ...pngs.map((p) => p.data)]);
}

/* ------------------------------------------------------------------ étapes */

async function buildFavicons() {
  const source = join(SRC, 'icon.svg');
  if (!existsSync(source)) return;

  mkdirSync(OUT, { recursive: true });

  // Le SVG lui-même : c'est lui que servent les navigateurs modernes.
  writeFileSync(join(OUT, 'icon.svg'), readFileSync(source));

  const png = (size) => sharp(source, { density: DENSITY })
    .resize(size, size)
    .png({ compressionLevel: 9 })
    .toBuffer();

  for (const [file, size] of ICONS) {
    writeFileSync(join(OUT, file), await png(size));
  }

  const ico = [];
  for (const size of ICO_SIZES) {
    ico.push({ size, data: await png(size) });
  }
  // À la racine du site : les navigateurs demandent /favicon.ico d'eux-mêmes.
  writeFileSync(join(PUBLIC, 'favicon.ico'), buildIco(ico));

  console.log(`  favicons ✓ (ico ${ICO_SIZES.join('/')}, ${ICONS.length} png, svg)`);
}

/**
 * Prépare le HTML de la carte : polices et logo en base64 à la place des
 * marqueurs __FONTS__ / __LOGO__ (cf. l'en-tête de src/brand/og-default.html).
 */
function inlineOgAssets(html) {
  const faces = OG_FONTS
    .map(([family, style, weight, file]) => {
      const path = join(PUBLIC, 'assets', 'fonts', file);
      if (!existsSync(path)) return '';
      const data = readFileSync(path).toString('base64');
      return `@font-face{font-family:'${family}';font-style:${style};font-weight:${weight};`
        + `src:url(data:font/woff2;base64,${data}) format('woff2')}`;
    })
    .filter(Boolean)
    .join('\n');

  const logo = existsSync(OG_LOGO)
    ? `data:image/webp;base64,${readFileSync(OG_LOGO).toString('base64')}`
    : '';

  // replaceAll : les marqueurs sont aussi cités dans l'en-tête du gabarit.
  return html
    .replaceAll('__FONTS__', faces ? `<style>\n${faces}\n</style>` : '')
    .replaceAll('__LOGO__', logo);
}

async function buildOgImage() {
  const source = join(SRC, 'og-default.html');
  if (!existsSync(source)) return;

  const chrome = findChrome();
  if (!chrome) {
    console.log('  og ⚠ Chrome introuvable (définissez CHROME_PATH) — image inchangée');
    return;
  }

  mkdirSync(OUT, { recursive: true });

  // Chrome écrit sa capture à côté du HTML ; on travaille donc au propre.
  const work = mkdtempSync(join(tmpdir(), 'sws-og-'));
  try {
    const page = join(work, 'og.html');
    const shot = join(work, 'og.png');
    writeFileSync(page, inlineOgAssets(readFileSync(source, 'utf8')));

    execFileSync(chrome, [
      '--headless=new',
      '--disable-gpu',
      '--hide-scrollbars',
      '--force-device-scale-factor=2',   // rendu 2× : texte net après réduction
      `--window-size=${OG.width},${OG.height}`,
      `--screenshot=${shot}`,
      `--user-data-dir=${join(work, 'profile')}`,
      `file://${page}`,
    ], { stdio: 'ignore' });

    await sharp(shot)
      .resize(OG.width, OG.height)
      .png({ compressionLevel: 9 })
      .toFile(join(OUT, 'og-default.png'));

    console.log(`  og ✓ (${OG.width}×${OG.height})`);
  } finally {
    rmSync(work, { recursive: true, force: true });
  }
}

console.log('build brand:');
await buildFavicons();
await buildOgImage();
