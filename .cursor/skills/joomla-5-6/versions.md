# Версии и обновления

Источник правды — SemVer в четырёх местах **одним значением**. Номер в этот файл не записывай: читай файлы.

| Место | Что править |
|---|---|
| `mod_bsjshop_filter.xml` | `<version>` |
| `mod_bsjshop_filter.php` | `$assetVersion` (cache-bust CSS/JS) |
| `src/Helper/BsjshopFilterHelper.php` | `VERSION` |
| `update.xml` | `<version>` и URL ZIP |

`update.xml`:

- `<element>mod_bsjshop_filter</element>` `<type>module</type>` `<client>site</client>` (не `0`: иначе Joomla 6 не матчит site-модуль)
- `<targetplatform name="joomla" version="(4|5|6)\." />` — как в файле, на `^[4-9]` не менять
- downloadurl: `.../releases/download/vX.Y.Z/mod_bsjshop_filter_vX.Y.Z.zip`
- infourl: страница релизов GitHub

Сервер в манифесте:

`https://raw.githubusercontent.com/andreytver84/BS-joomshopping-filter/main/update.xml`

После пуша `update.xml` в `main` Joomla видит новую версию. Номер поднимай, когда просят. ZIP и тег заливает человек — в ответе укажи `vX.Y.Z` и `mod_bsjshop_filter_vX.Y.Z.zip`.

## Релиз-чеклист

1. Согласовать X.Y.Z (patch = фикс, minor = фича).
2. Заменить версию в xml/php/helper/update.xml (включая downloadurl и тег `vX.Y.Z`).
3. Языки ru+en синхронны.
4. Description/README не противоречат возможностям.
5. Собрать ZIP **с корнем файлов модуля**, не с лишней обёрткой папки проекта и без `.cursor/`.
6. GitHub Release tag `vX.Y.Z`, asset как в downloadurl.
