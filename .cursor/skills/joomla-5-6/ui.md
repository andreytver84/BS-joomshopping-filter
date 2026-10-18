# Вёрстка фильтра и карточек

Меняй внешний вид здесь. Классы `bsjf-*` и атрибуты `data-bsjf*` — контракт с `assets/js/script.js`.

## Панель фильтра

Один фон на `.bsjf-filters`:

- `--bsjf-panel: #E8E3D6`
- `border-radius: 8px`
- `padding: 1.5rem` (24px); на телефоне при открытии — `1rem`

У `.bsjf-range` своего фона нет. Цена и площадь стоят внутри общей панели.

На ширине до 767px панель скрыта, пока у корня нет `.is-filter-open`. Кнопка `.bsjf-filter-toggle` тоже красит фон через `--bsjf-panel`.

## Кнопки

| Кнопка | Служебные классы | Параметр классов | Хук JS |
|---|---|---|---|
| Применить | `bsjf-btn` | `apply_class` | submit формы |
| Сбросить | `bsjf-btn-reset` | `reset_class` | `data-bsjf-reset` |
| Показать ещё | `bsjf-btn bsjf-more` | `more_class` | `data-bsjf-more` |

Параметр дописывается после служебного класса. Пустой параметр ничего не добавляет.

## Что не переименовывать

`data-bsjf`, `data-bsjf-form`, `data-bsjf-grid`, `data-bsjf-field`, `data-bsjf-cat`, `data-bsjf-category`, `data-bsjf-toggle`, `data-bsjf-reset`, `data-bsjf-more`, `data-bsjf-empty`, `data-module-id`, `data-token`, `data-price-min`, `data-price-max`, `data-area-min`, `data-area-max`.

Колонки сетки: `--bsjf-cols`, `--bsjf-cols-md`, `--bsjf-cols-sm` на корне `.bsjf`.

Карточка: `tmpl/products.php`, классы `bsjf-card*`. Этот layout же уходит в AJAX-поле `html`.
