# Языковые константы

## Файлы (держать ключи синхронно)

| Файл | Назначение |
|---|---|
| `language/ru-RU/ru-RU.mod_bsjshop_filter.ini` | runtime RU |
| `language/en-GB/en-GB.mod_bsjshop_filter.ini` | runtime EN |
| `language/ru-RU/ru-RU.mod_bsjshop_filter.sys.ini` | установщик/список модулей RU |
| `language/en-GB/en-GB.mod_bsjshop_filter.sys.ini` | установщик/список модулей EN |

Не переименовывать в `mod_bsjshop_filter.ini` без задачи: текущие имена с префиксом локали рабочие.

## Правила

- UTF-8 без BOM, `\n` переводы строк.
- `KEY="value"`. Ключ: `MOD_BSJSHOP_FILTER_` + UPPER_SNAKE.
- В XML: `label="MOD_BSJSHOP_FILTER_FIELD_…_LABEL"`, `description="…_DESC"`.
- Дефолты полей: ключ + `translate_default="true"` (`MOD_BSJSHOP_FILTER_DEFAULT_*`).
- В PHP/tmpl: `Text::_('MOD_BSJSHOP_FILTER_…')`.
- `.sys.ini` минимум: `MOD_BSJSHOP_FILTER`, `MOD_BSJSHOP_FILTER_XML_DESCRIPTION`.
- HTML в значениях — только `*_DESC`. Внутри кавычки удваивать: `"Say ""Hello"""`.

## Чеклист новой строки

1. Ключ в RU `.ini` и EN `.ini` (или оба `.sys.ini`).
2. Использование в XML или `Text::_`.
3. Карта ключей совпадает (можно сравнить `^[A-Z0-9_]+` без значений).
4. Не оставлять ключ только в одной локали.
