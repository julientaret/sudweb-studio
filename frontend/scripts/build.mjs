/*
 * Build des assets du site public (frontend).
 *
 *   node scripts/build.mjs            build unique
 *   node scripts/build.mjs --watch    rebuild à chaque modification de src/
 *
 * Trois étapes, toutes facultatives selon ce qui existe dans src/ :
 *   - CSS    : src/css/site.css → public/assets/site.css  (Lightning CSS : bundle, autoprefix, minify)
 *   - JS     : src/js/site.js   → public/assets/site.js   (esbuild : bundle, minify)
 *   - Images : src/img/*.{png,jpg} → public/assets/img/*.webp  (sharp)
 *
 * Les sorties dans public/assets sont VERSIONNÉES (le serveur de prod n'a pas
 * Node) ; seul node_modules est ignoré par git.
 */

import { mkdirSync, writeFileSync, existsSync, readdirSync, statSync } from 'node:fs';
import { dirname, join, basename } from 'node:path';
import { fileURLToPath } from 'node:url';

import { bundle, browserslistToTargets } from 'lightningcss';
import browserslist from 'browserslist';
import * as esbuild from 'esbuild';
import sharp from 'sharp';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const SRC = join(ROOT, 'src');
const OUT = join(ROOT, 'public', 'assets');

const targets = browserslistToTargets(
  browserslist('> 0.5%, last 2 versions, Firefox ESR, not dead')
);

function buildCss() {
  const input = join(SRC, 'css', 'site.css');
  if (!existsSync(input)) return;
  const { code } = bundle({ filename: input, minify: true, targets });
  mkdirSync(OUT, { recursive: true });
  writeFileSync(join(OUT, 'site.css'), code);
  console.log('  css ✓');
}

async function buildJs() {
  const input = join(SRC, 'js', 'site.js');
  if (!existsSync(input)) return;
  mkdirSync(OUT, { recursive: true });
  await esbuild.build({
    entryPoints: [input],
    bundle: true,
    minify: true,
    target: 'es2019',
    outfile: join(OUT, 'site.js'),
  });
  console.log('  js ✓');
}

async function buildImages() {
  const dir = join(SRC, 'img');
  if (!existsSync(dir)) return;
  const outDir = join(OUT, 'img');
  mkdirSync(outDir, { recursive: true });
  let n = 0;
  for (const file of walk(dir)) {
    if (!/\.(png|jpe?g)$/i.test(file)) continue;
    const out = join(outDir, basename(file).replace(/\.(png|jpe?g)$/i, '.webp'));
    await sharp(file).webp({ quality: 82 }).toFile(out);
    n++;
  }
  if (n) console.log(`  img ✓ (${n})`);
}

function* walk(dir) {
  for (const entry of readdirSync(dir)) {
    if (entry.startsWith('.')) continue;
    const full = join(dir, entry);
    if (statSync(full).isDirectory()) yield* walk(full);
    else yield full;
  }
}

async function buildAll() {
  console.log('build:');
  buildCss();
  await buildJs();
  await buildImages();
}

await buildAll();

if (process.argv.includes('--watch')) {
  const chokidar = (await import('chokidar')).default;
  console.log('watch src/ … (Ctrl+C pour arrêter)');
  let timer;
  chokidar.watch(SRC, { ignoreInitial: true }).on('all', () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      buildAll().catch((e) => console.error(e));
    }, 100);
  });
}
