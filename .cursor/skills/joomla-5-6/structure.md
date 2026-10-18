# Структура расширения Joomla 5/6

Актуально для **этого** репозитория. Не описывай CMS целиком.

## Текущее дерево

```
mod_bsjshop_filter.php     вход для Joomla 4 / fallback
mod_bsjshop_filter.xml     манифест
helper.php                 legacy com_ajax (ModBsjshopFilterHelper)
services/provider.php      Dispatcher + HelperFactory
src/Dispatcher/Dispatcher.php
src/Helper/BsjshopFilterHelper.php
tmpl/default.php           фильтр + сетка
tmpl/products.php          карточки (SSR и AJAX HTML)
language/ru-RU|en-GB/      .ini и .sys.ini
assets/css|js/             style.css, script.js
update.xml                 update server
```

Манифест: `type="module" client="site" method="upgrade"`. Namespace: `Bestsite\Module\BsjshopFilter`.

## Совместимость 5 и 6

- PHP 8.1-совместимо (без 8.3-only), если не оговорено иначе.
- API: `Joomla\CMS\*` (Factory, Text, Uri, ModuleHelper, Session).
- Запрещены legacy `JFactory` / `JText` / `JHtml` / `JRequest`.
- Bootstrap 5 / vanilla JS. jQuery не добавлять.
- WebAssetManager не подключать без явной задачи. Ассеты — `addStyleSheet` / `addScript`.

## AJAX

`index.php?option=com_ajax&module=bsjshop_filter&method=filter&format=json&helper=BsjshopFilterHelper`

Метод: `BsjshopFilterHelper::filterAjax()`. CSRF-токен обязателен. Параметры модуля грузить по `module_id` из `#__modules`, не из `ModuleHelper::getModuleById` (на com_ajax список модулей страницы пустой).

## Layout

```php
$layoutPath = ModuleHelper::getLayoutPath('mod_bsjshop_filter', $params->get('layout', 'default'));
```

Override: `templates/<tmpl>/html/mod_bsjshop_filter/default.php`. CSS-классы `bsjf-*` не ломать без задачи.
