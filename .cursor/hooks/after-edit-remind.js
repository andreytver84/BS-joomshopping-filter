const { readInput, writeOutput } = require('./lib.js');

const input = readInput();
const toolInput = input.tool_input || {};
const filePath = String(toolInput.path || toolInput.file_path || '');
const norm = filePath.replace(/\\/g, '/').toLowerCase();

const hints = [];

if (/(^|\/)language\/.+\.ini$/.test(norm)) {
  hints.push('Языки: те же ключи в ru-RU и en-GB; .ini ≠ .sys.ini. См. .cursor/skills/joomla-5-6/language.md');
}

if (/mod_bsjshop_filter\.xml$/.test(norm) || /(^|\/)update\.xml$/.test(norm)) {
  hints.push('XML: версия = php $assetVersion = Helper::VERSION = update.xml + downloadurl. Description без <script>.');
}

if (/mod_bsjshop_filter\.php$/.test(norm) || /(^|\/)tmpl\/.+\.php$/.test(norm) || /bsjshopfilterhelper\.php$/.test(norm)) {
  hints.push('PHP/tmpl: _JEXEC, htmlspecialchars ENT_QUOTES UTF-8, SQL только bind/whereIn.');
}

if (/(^|\/)assets\/js\/script\.js$/.test(norm)) {
  hints.push('JS: com_ajax + CSRF, вставляй только серверный HTML карточек.');
}

if (hints.length) {
  writeOutput({ additional_context: hints.join(' ') });
} else {
  writeOutput({});
}
