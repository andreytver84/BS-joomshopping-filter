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
  hints.push('XML: версия = php $assetVersion = Helper::VERSION = update.xml + downloadurl. Новое поле — ключи LABEL/DESC в обоих .ini. update.xml: client=site, targetplatform (4|5|6)\\. Description без <script>.');
}

if (/mod_bsjshop_filter\.php$/.test(norm) || /(^|\/)tmpl\/.+\.php$/.test(norm) || /bsjshopfilterhelper\.php$/.test(norm)) {
  hints.push('PHP/tmpl: _JEXEC, htmlspecialchars ENT_QUOTES UTF-8, SQL только bind/whereIn. Классы из params — $safeClass [A-Za-z0-9 _-], служебные bsjf-* и data-bsjf-* оставь.');
}

if (/style\.css$/.test(norm)) {
  hints.push('Вёрстка: один фон #E8E3D6 на .bsjf-filters (--bsjf-panel). У .bsjf-range фона нет. Контракт: .cursor/skills/joomla-5-6/ui.md');
}

if (/(^|\/)assets\/js\/script\.js$/.test(norm)) {
  hints.push('JS: com_ajax + CSRF, вставляй только серверный HTML карточек.');
}

if (hints.length) {
  writeOutput({ additional_context: hints.join(' ') });
} else {
  writeOutput({});
}
