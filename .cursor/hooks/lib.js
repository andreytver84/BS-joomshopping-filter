const fs = require('fs');
const path = require('path');

function readInput() {
  try {
    const raw = fs.readFileSync(0, 'utf8').trim();
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
}

function writeOutput(obj) {
  process.stdout.write(JSON.stringify(obj));
}

function repoRoot() {
  return path.resolve(__dirname, '..', '..');
}

function readRepoFile(rel) {
  return fs.readFileSync(path.join(repoRoot(), rel), 'utf8');
}

function versions() {
  const xml = readRepoFile('mod_bsjshop_filter.xml');
  const php = readRepoFile('mod_bsjshop_filter.php');
  const helper = readRepoFile('src/Helper/BsjshopFilterHelper.php');
  const upd = readRepoFile('update.xml');
  const manifest = (xml.match(/<version>([^<]+)<\/version>/) || [])[1] || '';
  const asset = (php.match(/\$assetVersion\s*=\s*'([^']+)'/) || [])[1] || '';
  const helperVer = (helper.match(/VERSION\s*=\s*'([^']+)'/) || [])[1] || '';
  const update = (upd.match(/<version>([^<]+)<\/version>/) || [])[1] || '';
  const url = (upd.match(/<downloadurl[^>]*>([^<]+)<\/downloadurl>/) || [])[1] || '';
  const urlOk = !update || (url.includes('v' + update) && url.includes(update));
  return {
    manifest,
    asset,
    helperVer,
    update,
    url,
    urlOk,
    synced: manifest && manifest === asset && asset === update && asset === helperVer && urlOk,
  };
}

module.exports = { readInput, writeOutput, repoRoot, readRepoFile, versions };
