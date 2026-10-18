---
description: Сверить языки, версию и вывод модуля
---

Проверь модуль перед сдачей, код без явной поломки не переписывай.

1. Ключи `language/ru-RU` и `language/en-GB`: отдельно `.ini` и `.sys.ini`.
2. Один SemVer в `mod_bsjshop_filter.xml`, `$assetVersion`, `BsjshopFilterHelper::VERSION`, `update.xml` и downloadurl.
3. Вывод params и классов — `htmlspecialchars`. Классы кнопок и `moduleclass_sfx` — только `[A-Za-z0-9 _-]`.
4. SQL фильтра — bind / `whereIn` / `intval`. Колонка extra field после проверки таблицы.

Ответ — по-русски, коротко: что совпало и что починить. Версию сам не поднимай.
