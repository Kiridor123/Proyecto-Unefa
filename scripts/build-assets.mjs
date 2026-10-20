import { copyFileSync, cpSync, mkdirSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const vendor = resolve(root, 'src/vendor');
mkdirSync(vendor, { recursive: true });
const assets = [
  ['chart.js/dist/chart.umd.js', 'chart.umd.js'],
  ['chart.js/LICENSE.md', 'chart.LICENSE.md'],
  ['sweetalert2/dist/sweetalert2.all.min.js', 'sweetalert2.all.min.js'],
  ['sweetalert2/LICENSE', 'sweetalert2.LICENSE'],
  ['@phosphor-icons/web/LICENSE', 'phosphor.LICENSE'],
];
for (const [source, target] of assets) copyFileSync(resolve(root, 'node_modules', source), resolve(vendor, target));
cpSync(resolve(root, 'node_modules/@phosphor-icons/web/src/regular'), resolve(vendor, 'phosphor'), { recursive: true });
const result = spawnSync(process.execPath, [
  resolve(root, 'node_modules/tailwindcss/lib/cli.js'),
  '-i', 'scripts/tailwind-input.css', '-o', 'src/css/tailwind.css', '--minify',
], { cwd: root, stdio: 'inherit' });
if (result.status !== 0) process.exit(result.status || 1);
console.log('Local styles, icons, alerts and charts are ready.');
