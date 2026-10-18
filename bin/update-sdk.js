#!/usr/bin/env node
'use strict';
// Installs the Davix H2I PHP SDK into vendor/davix-h2i-sdk/ EXACTLY as the app publishes it (sdk/php): no rewriting, no trimming.
// To update the plugin to a newer SDK, run this again (or simply replace the folder with the new SDK release); nothing else changes.
//
//   node bin/update-sdk.js [path-to-sdk/php]     (default: ../Davix-h2i-app/sdk/php)
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const SDK = path.resolve(process.argv[2] || path.join(ROOT, '..', 'Davix-h2i-app', 'sdk', 'php'));
const OUT = path.join(ROOT, 'vendor', 'davix-h2i-sdk');
if (!fs.existsSync(path.join(SDK, 'autoload.php'))) { console.error('Not an SDK folder (autoload.php missing): ' + SDK); process.exit(1); }

fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });
for (const item of ['autoload.php', 'composer.json', 'README.md', 'src', 'resources']) {
  const from = path.join(SDK, item);
  if (fs.existsSync(from)) fs.cpSync(from, path.join(OUT, item), { recursive: true });
}
const client = fs.readFileSync(path.join(OUT, 'src', 'Client.php'), 'utf8');
const contract = JSON.parse(fs.readFileSync(path.join(OUT, 'resources', 'contract.json'), 'utf8'));
const version = (/SDK_VERSION = '([^']+)'/.exec(client) || [])[1] || 'unknown';
fs.writeFileSync(path.join(OUT, 'VERSION'), `sdk ${version}\ncontract ${contract.contractVersion}\n`);
console.log(`SDK ${version} (contract ${contract.contractVersion}, ${contract.endpoints.length} endpoints) -> ${path.relative(ROOT, OUT)}`);
