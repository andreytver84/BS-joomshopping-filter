---
description: Поднять версию модуля и синхронизировать update.xml
---

Подними версию `mod_bsjshop_filter`.

Прочитай только `.cursor/skills/joomla-5-6/versions.md`. Номер бери из четырёх файлов, не из справочника.

Если пользователь не назвал номер: patch — исправление, minor — новая возможность. Запиши одно значение в манифест, `$assetVersion`, `BsjshopFilterHelper::VERSION`, `update.xml` и downloadurl (`vX.Y.Z` и `mod_bsjshop_filter_vX.Y.Z.zip`).

`<client>site</client>` и `targetplatform` `(4|5|6)\.` не меняй. Коммит не делай. Ответ — по-русски: старая → новая версия и что залить руками (тег и ZIP).
