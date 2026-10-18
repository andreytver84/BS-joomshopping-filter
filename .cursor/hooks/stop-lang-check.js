const { writeOutput, langDrift } = require('./lib.js');

try {
  const missing = langDrift();

  if (!missing.length) {
    writeOutput({});
    process.exit(0);
  }

  writeOutput({
    followup_message:
      'Ключи языков разошлись: ' +
      missing.slice(0, 12).join('; ') +
      '. Выровняй ru-RU и en-GB по .cursor/skills/joomla-5-6/language.md.',
  });
} catch {
  writeOutput({});
}
