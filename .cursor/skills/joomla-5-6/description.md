# Description модуля (установщик)

Два слоя, не смешивать:

1. **`<description>` в `mod_bsjshop_filter.xml`** — HTML на экране установки/модуля. Сейчас CDATA, русский.
2. **`MOD_BSJSHOP_FILTER_XML_DESCRIPTION` в `.sys.ini`** — короткий текст в списках расширений (RU и EN).

Инструкции для админки полей — XML `label`/`description` через `.ini`, не копируй туда весь HTML.

## HTML в `<description>`

- Только CDATA. Без `<script>`, `<iframe>`, внешних stylesheet.
- Инлайн-стили допустимы (установщик не грузит CSS модуля).
- Структура: что делает модуль, как указать категории и ID площади, зависимость JoomShopping 5.
- Не дублируй весь README. 1 экран, списки короткие.

## Когда править

| Изменение | XML description | sys.ini | README |
|---|---|---|---|
| Новая возможность, видимая админу | да, 1 абзац | да, если меняется суть | да, коротко RU+EN |
| Внутренний фикс | нет | нет | нет |
| Смена зависимости/AJAX-контракта | да | да | да |

Автор в XML: Andrey Uvikov, `order@bestsite-studio.ru`, `https://bestsite-tver.ru`. Не затирать.
