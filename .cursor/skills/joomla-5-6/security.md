# Безопасность Joomla 5/6 (этот модуль)

AJAX идёт через `com_ajax`. Метод `filterAjax` проверяет CSRF (`Session::checkToken('request')`), site-модуль (`client_id = 0`), language, published/access модуля и view levels товара и категории.

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

Только query/bind/`whereIn`. ID категорий в EXISTS — только после `intval`. Пустой список после intval — `WHERE 0 = 1`, не снимать EXISTS. Имя колонки extra field: `extra_field_{id}` после проверки `getTableColumns`. Языковой суффикс — только `^[a-z]{2}-[A-Z]{2}$` и для `JSFactory::getLang()`, не только для `Language::getTag()`.

## ACL витрины

- Категории: `category_publish = 1` и `access` через `whereIn` по `getAuthorisedViewLevels()`, если колонка есть.
- Товары и bounds (min/max цены и площади): `product_publish`, `parent_id = 0`, тот же `p.access`.
- AJAX-модуль: `client_id = 0`; `language` только `*`, пусто или текущий tag.

## Запреты

- `echo $params->get(...)` без санитайза.
- `eval`, `unserialize` пользовательских данных, `innerHTML` из params в JS.
- Конкатенация пользовательской строки в SQL.
- Произвольный URL из params в `addScript`.
- `ModuleHelper::getModuleById` как источник params в AJAX.

## CSS-классы из params

`moduleclass_sfx`, `apply_class`, `reset_class`, `more_class` и любой новый `*_class`: оставить `[A-Za-z0-9 _-]`, схлопнуть пробелы, в HTML через `htmlspecialchars`. Служебный класс (`bsjf-btn` и аналоги) параметром не заменять.

## Ассеты

Пути только через `Uri::root(true) . '/modules/mod_bsjshop_filter/assets/...'`.
