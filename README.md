# BS JoomShopping Filter — фильтр каталога для JoomShopping 5 / AJAX catalog filter for JoomShopping 5

[🇷🇺 Русский](#ру-русский) | [🇬🇧 English](#en-english)

---

<a name="ру-русский"></a>

## 🇷🇺 Русский

Site-модуль каталога с AJAX-фильтром для **JoomShopping 5** на Joomla 4, 5 и 6. Первая версия ищет по категориям, цене и характеристике «Площадь». Другие характеристики можно вывести на карточке.

### Возможности

- Вкладки категорий, включая пункт «Все»
- Диапазоны цены и площади, каждый можно скрыть
- Карточки товара: фото, название, выбранные характеристики с префиксом и постфиксом, цена, ссылка «Подробнее»
- Кнопка «Показать ещё»: свой текст, CSS-классы, можно отключить
- Сколько товаров показывать за раз — в настройках модуля

### Зависимости

Нужен установленный и опубликованный компонент [JoomShopping](https://www.webdesigner-profi.de/joomla-webdesign/joomshopping.html) версии 5.

Характеристика площади должна существовать в магазине. Для фильтра-диапазона удобнее **текстовое** поле с числом (например `148`). ID смотрите в JoomShopping → Характеристики.

### Установка

1. Соберите ZIP с корнем файлов модуля (`mod_bsjshop_filter.php`, `mod_bsjshop_filter.xml`, `helper.php`, `src`, `services`, `tmpl`, `language`, `assets`).
2. В Joomla: **Система → Установка → Расширения**.
3. Создайте модуль «Фильтр товаров JoomShopping», укажите позицию и страницу каталога.
4. В настройках задайте ID характеристики площади и при необходимости список категорий.

### Использование

Модуль сам выводит и фильтр, и сетку товаров. Отдельная категория JoomShopping на этой странице не обязательна.

---

<a name="en-english"></a>

## 🇬🇧 English

A site module with an AJAX catalog filter for **JoomShopping 5** on Joomla 4, 5 and 6. The first version filters by category, price and the Area extra field. Extra fields can also be shown on product cards.

### Features

- Category tabs, including All
- Price and area ranges, each can be hidden
- Product cards: image, name, chosen extra fields with prefix and postfix, price, details link
- Show more button: custom text, CSS classes, can be turned off
- How many products load at once is set in the module

### Requirements

Published [JoomShopping](https://www.webdesigner-profi.de/joomla-webdesign/joomshopping.html) 5.

The area extra field must exist in the shop. A numeric **text** field (for example `148`) works best for a range. Look up the ID under JoomShopping → Extra fields.

### Install

1. Build a ZIP whose root contains the module files (`mod_bsjshop_filter.php`, `mod_bsjshop_filter.xml`, `helper.php`, `src`, `services`, `tmpl`, `language`, `assets`).
2. In Joomla go to **System → Install → Extensions**.
3. Create a “JoomShopping product filter” module, assign a position and the catalog page.
4. Set the area extra field ID and optional category IDs.

---

## Автор / Author

**Увиков Андрей (Andrey Uvikov)**

- Email: order@bestsite-studio.ru
- Сайт / Website: https://bestsite-tver.ru
