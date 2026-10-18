# Как развивать модуль

Один сценарий за раз. Версию не меняй, пока пользователь не попросил.

## Поле в админке

1. `<field>` в `mod_bsjshop_filter.xml`: `label` и `description` — ключи `MOD_BSJSHOP_FILTER_FIELD_*`.
2. Тот же ключ в `language/ru-RU/*.ini` и `language/en-GB/*.ini`. В `.sys.ini` только имя модуля и `XML_DESCRIPTION`.
3. Чтение: `$params->get()`, затем санитайз. В HTML — `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
4. Текст по умолчанию: ключ + `translate_default="true"`.

## CSS-класс на кнопку

Поля уже есть: `apply_class`, `reset_class`, `more_class`. Новый класс — по тому же пути.

В `tmpl/default.php` пропускай строку через `$safeClass`: оставить только `[A-Za-z0-9 _-]`, схлопнуть пробелы. Служебный класс (`bsjf-btn`, `bsjf-btn-reset`, `bsjf-more`) остаётся в разметке.

## Новый фильтр

Цепочка одна, разрывать нельзя:

1. Переключатель `show_*` в XML и оба `.ini`.
2. `BsjshopFilterHelper::filtersFromRequest()`: на странице имя `bsjf_{moduleId}_{field}`, в `com_ajax` — короткое (`price_from`). Если «от» больше «до», поменять местами.
3. SQL только в Helper: bind / `whereIn` / `intval`. Колонка extra field — `extra_field_{id}` после `getTableColumns`.
4. Поле внутри `.bsjf-filters`, атрибут `data-bsjf-field`. Своего фона у блока нет — см. [ui.md](ui.md).
5. `assets/js/script.js`: добавить значение в `payload()` и сброс в обработчике `[data-bsjf-reset]`. Границы, если нужны, — `data-*` на корне `.bsjf`.

Характеристики карточки (`card_fields`) в фильтр не попадают. Их читает `getCardFields()` и рисует `tmpl/products.php`.

## Карточка

Новая характеристика — строка subform `card_fields` (ID, префикс, постфикс), не новый фильтр. Пустое значение на карточке не выводить.

## После правки

- Ключи ru и en совпадают.
- Четыре номера версии совпадают, если пользователь просил поднять версию. Номер в этот справочник не записывать.
- Текст установщика и README — только когда меняется способ настройки. Тогда [description.md](description.md).
