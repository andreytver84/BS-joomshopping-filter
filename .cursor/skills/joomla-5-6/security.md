# Безопасность Joomla 5/6 (этот модуль)

AJAX идёт через `com_ajax`. Метод `filterAjax` проверяет CSRF (`Session::checkToken('request')`), published/access модуля и view levels товара.

## Обязательно

```php
defined('_JEXEC') or die;
```

В namespaced-файлах: `\defined('_JEXEC') or die;`.

Вывод в HTML:

```php
htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
```

## SQL

Только query/bind/`whereIn`. ID категорий в EXISTS — только после `intval`. Имя колонки extra field: `extra_field_{id}` после проверки `getTableColumns`. Языковой суффикс — только `^[a-z]{2}-[A-Z]{2}$`.

## Запреты

- `echo $params->get(...)` без санитайза.
- `eval`, `unserialize` пользовательских данных, `innerHTML` из params в JS.
- Конкатенация пользовательской строки в SQL.
- Произвольный URL из params в `addScript`.
- `ModuleHelper::getModuleById` как источник params в AJAX.

## Ассеты

Пути только через `Uri::root(true) . '/modules/mod_bsjshop_filter/assets/...'`.
