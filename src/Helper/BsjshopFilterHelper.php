<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_bsjshop_filter
 */

namespace Bestsite\Module\BsjshopFilter\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

class BsjshopFilterHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    public const VERSION = '1.5.0';

    public function isShopReady(): bool
    {
        if (!ComponentHelper::isEnabled('com_jshopping')) {
            return false;
        }

        $this->bootstrapShop();

        return $this->tableExists('#__jshopping_products');
    }

    public function getCatalogData(Registry $params, int $moduleId): array
    {
        $categories = $this->getCategories($params);
        $bounds = $this->getBounds($params, $categories);
        $filters = $this->filtersFromRequest($moduleId, $params, $bounds);
        $result = $this->getProducts($params, $filters);

        return [
            'categories' => $categories,
            'bounds'     => $bounds,
            'filters'    => $filters,
            'items'        => $result['items'],
            'hasMore'      => $result['hasMore'],
            'currencyCode' => $this->getCurrencyCode(),
        ];
    }

    public function filterAjax()
    {
        if (!Session::checkToken('request')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $app = Factory::getApplication();
        $app->getLanguage()->load('mod_bsjshop_filter', JPATH_SITE . '/modules/mod_bsjshop_filter');

        $moduleId = $app->input->getInt('module_id', 0);
        $params = $this->loadPublishedModuleParams($moduleId);

        if (!$this->isShopReady()) {
            throw new \RuntimeException(Text::_('MOD_BSJSHOP_FILTER_ERROR_NO_JSHOP'), 500);
        }

        $categories = $this->getCategories($params);
        $bounds = $this->getBounds($params, $categories);
        $filters = $this->filtersFromRequest($moduleId, $params, $bounds);
        $result = $this->getProducts($params, $filters);

        return [
            'html'    => $this->renderProducts($result['items'], $params),
            'hasMore' => $result['hasMore'],
            'count'   => count($result['items']),
        ];
    }

    public function getCategories(Registry $params): array
    {
        if (!$this->tableExists('#__jshopping_categories')) {
            return [];
        }

        $db = $this->db();
        $langCol = $this->getLangColumn('#__jshopping_categories', 'name');
        $explicit = $this->parseIdList($params->get('category_ids', ''));

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('category_id', 'id'),
                $db->quoteName($langCol, 'name'),
                $db->quoteName('category_parent_id', 'parent_id'),
            ])
            ->from($db->quoteName('#__jshopping_categories'))
            ->where($db->quoteName('category_publish') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        $this->applyCategoryAccess($query);

        if ($explicit) {
            $query->whereIn($db->quoteName('category_id'), $explicit, ParameterType::INTEGER);
        } else {
            $parentId = (int) $params->get('parent_category_id', 0);
            $query->where($db->quoteName('category_parent_id') . ' = :parentId')
                ->bind(':parentId', $parentId, ParameterType::INTEGER);
        }

        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        foreach ($rows as $row) {
            $row->id = (int) $row->id;
            $row->name = trim(strip_tags((string) $row->name));
        }

        if ($explicit) {
            $order = array_flip($explicit);
            usort(
                $rows,
                static fn ($a, $b): int => ($order[$a->id] ?? PHP_INT_MAX) <=> ($order[$b->id] ?? PHP_INT_MAX)
            );
        }

        return $rows;
    }

    public function getBounds(Registry $params, array $categories): array
    {
        $categoryIds = $this->showCategories($params)
            ? array_map(static fn ($cat) => (int) $cat->id, $categories)
            : [];
        $areaFieldId = (int) $params->get('extra_field_area_id', 0);

        $price = $this->queryPriceBounds($params, $categoryIds);
        $area = $this->queryAreaBounds($params, $categoryIds, $areaFieldId);

        return [
            'priceMin'    => $price['min'],
            'priceMax'    => $price['max'],
            'areaMin'     => $area['min'],
            'areaMax'     => $area['max'],
            'areaFieldId' => $areaFieldId,
            'hasArea'     => $areaFieldId > 0 && $this->extraFieldColumnExists($areaFieldId),
        ];
    }

    public function filtersFromRequest(int $moduleId, Registry $params, array $bounds): array
    {
        $input = Factory::getApplication()->input;
        $prefix = 'bsjf_' . $moduleId . '_';
        $limit = max(1, (int) $params->get('limit', 6));
        $isAjax = $input->getCmd('option') === 'com_ajax';

        $categoryId = 0;

        if ($this->showCategories($params)) {
            $categoryId = $isAjax
                ? $input->getInt('category_id', 0)
                : $input->getInt($prefix . 'cat', 0);
            $allowed = array_map(static fn ($cat) => (int) $cat->id, $this->getCategories($params));

            if ($categoryId !== 0 && !in_array($categoryId, $allowed, true)) {
                $categoryId = 0;
            }
        }

        $priceFrom = $this->toFloat($input->getString(
            $isAjax ? 'price_from' : $prefix . 'price_from',
            (string) $bounds['priceMin']
        ));
        $priceTo = $this->toFloat($input->getString(
            $isAjax ? 'price_to' : $prefix . 'price_to',
            (string) $bounds['priceMax']
        ));
        $areaFrom = $this->toFloat($input->getString(
            $isAjax ? 'area_from' : $prefix . 'area_from',
            (string) $bounds['areaMin']
        ));
        $areaTo = $this->toFloat($input->getString(
            $isAjax ? 'area_to' : $prefix . 'area_to',
            (string) $bounds['areaMax']
        ));

        if ($priceFrom > $priceTo) {
            [$priceFrom, $priceTo] = [$priceTo, $priceFrom];
        }

        if ($areaFrom > $areaTo) {
            [$areaFrom, $areaTo] = [$areaTo, $areaFrom];
        }

        return [
            'category_id' => $categoryId,
            'price_from'  => $priceFrom,
            'price_to'    => $priceTo,
            'area_from'   => $areaFrom,
            'area_to'     => $areaTo,
            'offset'      => max(0, $input->getInt('offset', 0)),
            'limit'       => $limit,
        ];
    }

    public function getProducts(Registry $params, array $filters): array
    {
        $db = $this->db();
        $filterCats = [];

        if ($this->showCategories($params)) {
            $categories = $this->getCategories($params);
            $tabIds = array_map(static fn ($cat) => (int) $cat->id, $categories);
            $selectedCat = (int) $filters['category_id'];
            $filterCats = $selectedCat > 0 ? [$selectedCat] : $tabIds;
        }

        $nameCol = $this->getLangColumn('#__jshopping_products', 'name');
        $areaFieldId = (int) $params->get('extra_field_area_id', 0);
        $cardFields = $this->getCardFields($params);
        $cardFieldIds = array_map(static fn (array $field): int => $field['id'], $cardFields);
        $efTable = $this->extraFieldsTable();
        $needEf = $efTable !== '' && $efTable !== '#__jshopping_products' && ($areaFieldId > 0 || $cardFieldIds);

        $priceExpr = $this->priceExpr();
        $select = [
            $db->quoteName('p.product_id'),
            $db->quoteName('p.product_price'),
            $db->quoteName('p.min_price'),
            $db->quoteName('p.different_prices'),
            $db->quoteName('p.image'),
            $db->quoteName('p.' . $nameCol, 'name'),
            $priceExpr . ' AS ' . $db->quoteName('display_price'),
        ];

        if ($this->columnExists('#__jshopping_products', 'main_category_id')) {
            $select[] = $db->quoteName('p.main_category_id');
        }

        foreach ($cardFieldIds as $fieldId) {
            $col = $this->extraFieldColumn($fieldId);

            if ($this->extraFieldColumnExists($fieldId)) {
                $select[] = $this->efColumnSql($col) . ' AS ' . $db->quoteName($col);
            }
        }

        $query = $db->getQuery(true)
            ->select($select)
            ->from($db->quoteName('#__jshopping_products', 'p'))
            ->where($db->quoteName('p.product_publish') . ' = 1')
            ->where($db->quoteName('p.parent_id') . ' = 0');

        $this->applyProductAccess($query);
        $this->applyListingScope($query, $params, $filterCats);

        if ($needEf) {
            $query->join(
                'LEFT',
                $db->quoteName($efTable, 'ef')
                . ' ON ' . $db->quoteName('ef.product_id') . ' = ' . $db->quoteName('p.product_id')
            );
        }

        if ((int) $params->get('show_price', 1) === 1) {
            $priceFrom = (float) $filters['price_from'];
            $priceTo = (float) $filters['price_to'];
            $query->where($priceExpr . ' >= :priceFrom')
                ->where($priceExpr . ' <= :priceTo')
                ->bind(':priceFrom', $priceFrom)
                ->bind(':priceTo', $priceTo);
        }

        if (
            (int) $params->get('show_area', 1) === 1
            && $areaFieldId > 0
            && $this->extraFieldColumnExists($areaFieldId)
        ) {
            $this->applyAreaFilter($query, $areaFieldId, (float) $filters['area_from'], (float) $filters['area_to']);
        }

        $ordering = (string) $params->get('ordering', 'name');
        $dir = strtoupper((string) $params->get('ordering_dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        switch ($ordering) {
            case 'price':
                $query->order($priceExpr . ' ' . $dir);
                break;
            case 'date':
                $query->order($db->quoteName('p.product_date_added') . ' ' . $dir);
                break;
            case 'id':
                $query->order($db->quoteName('p.product_id') . ' ' . $dir);
                break;
            default:
                $query->order($db->quoteName('p.' . $nameCol) . ' ' . $dir);
        }

        $query->order($db->quoteName('p.product_id') . ' ASC');

        $limit = max(1, (int) $filters['limit']);
        $offset = max(0, (int) $filters['offset']);
        $query->setLimit($limit + 1, $offset);

        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];
        $hasMore = count($rows) > $limit;

        if ($hasMore) {
            array_pop($rows);
        }

        return [
            'items'   => $this->prepareItems($rows, $params, $cardFields),
            'hasMore' => $hasMore,
        ];
    }

    public function renderProducts(array $items, Registry $params): string
    {
        $layout = ModuleHelper::getLayoutPath('mod_bsjshop_filter', 'products');

        ob_start();
        include $layout;

        return (string) ob_get_clean();
    }

    private function applyAreaFilter($query, int $fieldId, float $from, float $to): void
    {
        $db = $this->db();
        $col = $this->extraFieldColumn($fieldId);
        $quoted = $this->efColumnSql($col);

        if ($this->extraFieldIsList($fieldId)) {
            $nameCol = $this->getLangColumn('#__jshopping_products_extra_field_values', 'name');
            $query->join(
                'LEFT',
                $db->quoteName('#__jshopping_products_extra_field_values', 'ev')
                . ' ON ' . $db->quoteName('ev.id') . ' = ' . $quoted
                . ' AND ' . $db->quoteName('ev.field_id') . ' = :areaFieldId'
            );
            $query->bind(':areaFieldId', $fieldId, ParameterType::INTEGER);
            $expr = $this->numericSql($db->quoteName('ev.' . $nameCol));
        } else {
            $expr = $this->numericSql($quoted);
        }

        $query->where($quoted . ' IS NOT NULL')
            ->where($quoted . ' != ' . $db->quote(''))
            ->where($quoted . ' != ' . $db->quote('0'))
            ->where($expr . ' >= :areaFrom')
            ->where($expr . ' <= :areaTo')
            ->bind(':areaFrom', $from)
            ->bind(':areaTo', $to);
    }

    private function prepareItems(array $rows, Registry $params, array $cardFields): array
    {
        $fieldIds = array_map(static fn (array $field): int => $field['id'], $cardFields);
        $valueMap = $this->loadListValueNames($rows, $fieldIds);
        $items = [];

        foreach ($rows as $row) {
            $item = new \stdClass();
            $item->id = (int) $row->product_id;
            $item->name = (string) $row->name;
            $item->url = $this->productUrl((int) $row->product_id, (int) ($row->main_category_id ?? 0));
            $item->image = $this->productImage((string) $row->image);
            $showFrom = (int) $params->get('price_from_prefix', 1) === 1
                || ((int) $row->different_prices) === 1;
            $item->pricePrefix = $showFrom ? Text::_('MOD_BSJSHOP_FILTER_PRICE_FROM') : '';
            $item->price = $this->formatPrice((float) $row->display_price, $params);
            $item->meta = [];

            foreach ($cardFields as $field) {
                $fieldId = $field['id'];
                $col = $this->extraFieldColumn($fieldId);

                if (!isset($row->{$col}) || $row->{$col} === '' || $row->{$col} === null) {
                    continue;
                }

                $raw = (string) $row->{$col};
                $text = $this->formatCardValue($valueMap[$fieldId][$raw] ?? $raw);

                if ($text === '') {
                    continue;
                }

                $item->meta[] = $this->withAffixes($text, $field['prefix'], $field['postfix']);
            }

            $items[] = $item;
        }

        return $items;
    }

    private function formatCardValue(string $text): string
    {
        $text = trim($text);

        if ($text === '' || $text === '0') {
            return '';
        }

        if (preg_match('/^\d+(?:[.,]\d+)?$/', $text)) {
            $number = $this->toFloat($text);
            $decimals = floor($number) == $number ? 0 : 2;
            $formatted = $this->formatNumber($number, $decimals);

            return $decimals > 0 ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
        }

        return $text;
    }

    private function withAffixes(string $text, string $prefix, string $postfix): string
    {
        $parts = [];

        if ($prefix !== '') {
            $parts[] = $prefix;
        }

        $parts[] = $text;

        if ($postfix !== '') {
            $parts[] = $postfix;
        }

        return implode(' ', $parts);
    }

    private function loadListValueNames(array $rows, array $fieldIds): array
    {
        $ids = [];

        foreach ($rows as $row) {
            foreach ($fieldIds as $fieldId) {
                if (!$this->extraFieldIsList($fieldId)) {
                    continue;
                }

                $col = $this->extraFieldColumn($fieldId);
                $raw = $row->{$col} ?? null;

                if ($raw !== null && $raw !== '' && ctype_digit((string) $raw)) {
                    $ids[] = (int) $raw;
                }
            }
        }

        $ids = array_values(array_unique($ids));

        if (!$ids || !$this->tableExists('#__jshopping_products_extra_field_values')) {
            return [];
        }

        $db = $this->db();
        $nameCol = $this->getLangColumn('#__jshopping_products_extra_field_values', 'name');
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('field_id'),
                $db->quoteName($nameCol, 'name'),
            ])
            ->from($db->quoteName('#__jshopping_products_extra_field_values'))
            ->whereIn($db->quoteName('id'), $ids, ParameterType::INTEGER);

        $db->setQuery($query);
        $map = [];

        foreach ($db->loadObjectList() ?: [] as $value) {
            $map[(int) $value->field_id][(string) $value->id] = (string) $value->name;
        }

        return $map;
    }

    private function queryPriceBounds(Registry $params, array $categoryIds): array
    {
        $db = $this->db();
        $priceExpr = $this->priceExpr();
        $query = $db->getQuery(true)
            ->select('MIN(' . $priceExpr . ') AS ' . $db->quoteName('min_price') . ', MAX(' . $priceExpr . ') AS ' . $db->quoteName('max_price'))
            ->from($db->quoteName('#__jshopping_products', 'p'))
            ->where($db->quoteName('p.product_publish') . ' = 1')
            ->where($db->quoteName('p.parent_id') . ' = 0');

        $this->applyProductAccess($query);
        $this->applyListingScope($query, $params, $categoryIds);
        $db->setQuery($query);
        $row = $db->loadObject();

        $min = isset($row->min_price) ? (float) $row->min_price : 0.0;
        $max = isset($row->max_price) ? (float) $row->max_price : 0.0;

        if ($max < $min) {
            $max = $min;
        }

        return ['min' => $min, 'max' => $max];
    }

    private function queryAreaBounds(Registry $params, array $categoryIds, int $fieldId): array
    {
        $empty = ['min' => 0.0, 'max' => 0.0];

        if ($fieldId < 1 || !$this->extraFieldColumnExists($fieldId)) {
            return $empty;
        }

        $db = $this->db();
        $quoted = $this->efColumnSql($this->extraFieldColumn($fieldId));
        $efTable = $this->extraFieldsTable();

        if ($this->extraFieldIsList($fieldId)) {
            $nameCol = $this->getLangColumn('#__jshopping_products_extra_field_values', 'name');
            $expr = $this->numericSql($db->quoteName('ev.' . $nameCol));
        } else {
            $expr = $this->numericSql($quoted);
        }

        $query = $db->getQuery(true)
            ->select('MIN(' . $expr . ') AS ' . $db->quoteName('min_area') . ', MAX(' . $expr . ') AS ' . $db->quoteName('max_area'))
            ->from($db->quoteName('#__jshopping_products', 'p'))
            ->where($db->quoteName('p.product_publish') . ' = 1')
            ->where($db->quoteName('p.parent_id') . ' = 0');

        $this->applyProductAccess($query);

        if ($efTable !== '#__jshopping_products') {
            $query->join(
                'INNER',
                $db->quoteName($efTable, 'ef')
                . ' ON ' . $db->quoteName('ef.product_id') . ' = ' . $db->quoteName('p.product_id')
            );
        }

        if ($this->extraFieldIsList($fieldId)) {
            $query->join(
                'INNER',
                $db->quoteName('#__jshopping_products_extra_field_values', 'ev')
                . ' ON ' . $db->quoteName('ev.id') . ' = ' . $quoted
                . ' AND ' . $db->quoteName('ev.field_id') . ' = :areaFieldId'
            );
            $query->bind(':areaFieldId', $fieldId, ParameterType::INTEGER);
        } else {
            $query->where($quoted . ' IS NOT NULL')
                ->where($quoted . ' != ' . $db->quote(''))
                ->where($quoted . ' != ' . $db->quote('0'));
        }

        $this->applyListingScope($query, $params, $categoryIds);
        $db->setQuery($query);
        $row = $db->loadObject();

        $min = isset($row->min_area) ? (float) $row->min_area : 0.0;
        $max = isset($row->max_area) ? (float) $row->max_area : 0.0;

        if ($max < $min) {
            $max = $min;
        }

        return ['min' => $min, 'max' => $max];
    }

    private function showCategories(Registry $params): bool
    {
        return (int) $params->get('show_categories', 1) === 1;
    }

    private function applyListingScope($query, Registry $params, array $categoryIds): void
    {
        if ($this->showCategories($params)) {
            $this->applyCategoryFilter($query, $categoryIds);
        }

        $raw = trim((string) $params->get('product_ids', ''));

        if ($raw === '') {
            return;
        }

        $ids = $this->parseIdList($raw);

        if (!$ids) {
            $query->where('0 = 1');

            return;
        }

        $query->whereIn($this->db()->quoteName('p.product_id'), $ids, ParameterType::INTEGER);
    }

    private function applyCategoryFilter($query, array $categoryIds): void
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds), static fn ($id) => $id > 0)));

        if (!$categoryIds) {
            $query->where('0 = 1');

            return;
        }

        $db = $this->db();
        $in = implode(',', $categoryIds);
        $query->where(
            'EXISTS (SELECT 1 FROM ' . $db->quoteName('#__jshopping_products_to_categories', 'pc')
            . ' WHERE ' . $db->quoteName('pc.product_id') . ' = ' . $db->quoteName('p.product_id')
            . ' AND ' . $db->quoteName('pc.category_id') . ' IN (' . $in . '))'
        );
    }

    private function loadPublishedModuleParams(int $moduleId): Registry
    {
        if ($moduleId < 1) {
            throw new \RuntimeException(Text::_('MOD_BSJSHOP_FILTER_ERROR_MODULE'), 404);
        }

        $db = $this->db();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'module', 'params', 'published', 'access', 'language', 'client_id']))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('client_id') . ' = 0')
            ->bind(':id', $moduleId, ParameterType::INTEGER);

        $db->setQuery($query);
        $row = $db->loadObject();

        if (!$row || $row->module !== 'mod_bsjshop_filter' || (int) $row->published !== 1) {
            throw new \RuntimeException(Text::_('MOD_BSJSHOP_FILTER_ERROR_MODULE'), 404);
        }

        $modLang = (string) $row->language;
        $currentLang = Factory::getApplication()->getLanguage()->getTag();

        if ($modLang !== '' && $modLang !== '*' && $modLang !== $currentLang) {
            throw new \RuntimeException(Text::_('MOD_BSJSHOP_FILTER_ERROR_MODULE'), 404);
        }

        if (!in_array((int) $row->access, $this->authorisedViewLevels(), true)) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return new Registry($row->params);
    }

    private function getCardFields(Registry $params): array
    {
        $raw = $params->get('card_fields', []);

        if ($raw instanceof Registry) {
            $raw = $raw->toArray();
        } elseif (is_object($raw)) {
            $raw = (array) $raw;
        }

        $fields = [];
        $seen = [];

        if (is_array($raw)) {
            foreach ($raw as $row) {
                if ($row instanceof Registry) {
                    $row = $row->toArray();
                } elseif (is_object($row)) {
                    $row = (array) $row;
                }

                if (!is_array($row)) {
                    continue;
                }

                $id = (int) ($row['field_id'] ?? 0);

                if ($id < 1 || isset($seen[$id]) || !$this->extraFieldColumnExists($id)) {
                    continue;
                }

                $seen[$id] = true;
                $fields[] = [
                    'id'      => $id,
                    'prefix'  => $this->plainLabel($row['prefix'] ?? ''),
                    'postfix' => $this->plainLabel($row['postfix'] ?? ''),
                ];
            }
        }

        return $fields;
    }

    private function plainLabel($value): string
    {
        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        if (mb_strlen($value) > 40) {
            $value = mb_substr($value, 0, 40);
        }

        return $value;
    }

    private function productUrl(int $productId, int $categoryId): string
    {
        $url = 'index.php?option=com_jshopping&controller=product&task=view&category_id='
            . $categoryId . '&product_id=' . $productId;

        if (class_exists('JSHelper') && method_exists('JSHelper', 'SEFLink')) {
            return (string) \JSHelper::SEFLink($url, 1);
        }

        if (function_exists('SEFLink')) {
            return (string) SEFLink($url, 1);
        }

        return Route::_($url);
    }

    private function productImage(string $image): string
    {
        if ($image === '') {
            return '';
        }

        $file = 'thumb_' . $image;
        $live = Uri::root(true) . '/components/com_jshopping/files/img_products/';

        if (class_exists('JSFactory')) {
            $cfg = \JSFactory::getConfig();

            if (!empty($cfg->image_product_live_path)) {
                $live = rtrim((string) $cfg->image_product_live_path, '/') . '/';
            }
        }

        return $live . rawurlencode($file);
    }

    public function getCurrencyCode(): string
    {
        static $code = null;

        if ($code !== null) {
            return $code;
        }

        $code = '';

        if (!$this->tableExists('#__jshopping_currencies') || !$this->columnExists('#__jshopping_currencies', 'currency_id')) {
            return $code;
        }

        $this->bootstrapShop();
        $mainId = 0;

        if (class_exists('JSFactory')) {
            $cfg = \JSFactory::getConfig();

            if (is_object($cfg) && isset($cfg->mainCurrency)) {
                $mainId = (int) $cfg->mainCurrency;
            }
        }

        $columns = [];

        foreach (['currency_code', 'currency_code_iso'] as $column) {
            if ($this->columnExists('#__jshopping_currencies', $column)) {
                $columns[] = $column;
            }
        }

        if (!$columns) {
            return $code;
        }

        $db = $this->db();
        $query = $db->getQuery(true)
            ->select($db->quoteName($columns))
            ->from($db->quoteName('#__jshopping_currencies'));

        if ($mainId > 0) {
            $query->where($db->quoteName('currency_id') . ' = :cid')
                ->bind(':cid', $mainId, ParameterType::INTEGER);
        } elseif ($this->columnExists('#__jshopping_currencies', 'currency_publish')) {
            $query->where($db->quoteName('currency_publish') . ' = 1');

            if ($this->columnExists('#__jshopping_currencies', 'currency_ordering')) {
                $query->order($db->quoteName('currency_ordering') . ' ASC');
            }
        }

        $db->setQuery($query, 0, 1);
        $row = $db->loadObject();

        if (!$row) {
            return $code;
        }

        $value = trim((string) ($row->currency_code ?? ''));

        if ($value === '') {
            $value = trim((string) ($row->currency_code_iso ?? ''));
        }

        $code = $value;

        return $code;
    }

    private function formatPrice(float $price, Registry $params): string
    {
        $suffix = $this->getCurrencyCode();
        $html = $this->formatNumber($price, (int) $params->get('price_decimals', 0));

        if ($suffix !== '') {
            $html .= ' ' . $suffix;
        }

        return $html;
    }

    private function formatNumber(float $number, int $decimals = 0): string
    {
        return number_format($number, $decimals, '.', ' ');
    }

    private function priceExpr(): string
    {
        $db = $this->db();

        return 'CASE WHEN ' . $db->quoteName('p.min_price') . ' > 0 THEN '
            . $db->quoteName('p.min_price') . ' ELSE ' . $db->quoteName('p.product_price') . ' END';
    }

    private function numericSql(string $quotedColumn): string
    {
        return 'CAST(REPLACE(' . $quotedColumn . ', \',\', \'.\') AS DECIMAL(12,2))';
    }

    private function extraFieldColumn(int $id): string
    {
        return 'extra_field_' . $id;
    }

    private function efColumnSql(string $column): string
    {
        $db = $this->db();
        $efTable = $this->extraFieldsTable();
        $alias = $efTable === '#__jshopping_products' ? 'p' : 'ef';

        return $db->quoteName($alias . '.' . $column);
    }

    private function extraFieldColumnExists(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $table = $this->extraFieldsTable();

        return $table !== '' && $this->columnExists($table, $this->extraFieldColumn($id));
    }

    private function extraFieldIsList(int $id): bool
    {
        static $cache = [];

        if (isset($cache[$id])) {
            return $cache[$id];
        }

        $cache[$id] = false;

        if ($id < 1 || !$this->tableExists('#__jshopping_products_extra_field_values')) {
            return false;
        }

        $db = $this->db();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jshopping_products_extra_field_values'))
            ->where($db->quoteName('field_id') . ' = :fid')
            ->bind(':fid', $id, ParameterType::INTEGER);

        $db->setQuery($query);
        $cache[$id] = ((int) $db->loadResult()) > 0;

        return $cache[$id];
    }

    private function extraFieldsTable(): string
    {
        static $table = null;

        if ($table !== null) {
            return $table;
        }

        if ($this->tableExists('#__jshopping_products_to_extra_fields')) {
            $table = '#__jshopping_products_to_extra_fields';
        } elseif ($this->tableExists('#__jshopping_products')) {
            $table = '#__jshopping_products';
        } else {
            $table = '';
        }

        return $table;
    }

    private function getLangColumn(string $table, string $base): string
    {
        $tag = $this->langTag();
        $preferred = $base . '_' . $tag;

        if ($this->columnExists($table, $preferred)) {
            return $preferred;
        }

        if ($this->columnExists($table, $base . '_en-GB')) {
            return $base . '_en-GB';
        }

        if ($this->columnExists($table, $base . '_ru-RU')) {
            return $base . '_ru-RU';
        }

        return $base;
    }

    private function langTag(): string
    {
        $tag = '';

        if (class_exists('JSFactory')) {
            $lang = \JSFactory::getLang();

            if (is_object($lang) && !empty($lang->lang)) {
                $tag = (string) $lang->lang;
            }
        }

        if ($tag === '') {
            $tag = (string) Factory::getApplication()->getLanguage()->getTag();
        }

        return preg_match('/^[a-z]{2}-[A-Z]{2}$/', $tag) ? $tag : 'en-GB';
    }

    private function authorisedViewLevels(): array
    {
        $user = Factory::getApplication()->getIdentity();
        $levels = $user ? $user->getAuthorisedViewLevels() : [1];

        return array_map('intval', $levels ?: [1]);
    }

    private function applyProductAccess($query): void
    {
        if (!$this->columnExists('#__jshopping_products', 'access')) {
            return;
        }

        $levels = $this->authorisedViewLevels();
        $query->whereIn($this->db()->quoteName('p.access'), $levels, ParameterType::INTEGER);
    }

    private function applyCategoryAccess($query): void
    {
        if (!$this->columnExists('#__jshopping_categories', 'access')) {
            return;
        }

        $levels = $this->authorisedViewLevels();
        $query->whereIn($this->db()->quoteName('access'), $levels, ParameterType::INTEGER);
    }

    private function columnExists(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table;

        if (!isset($cache[$key])) {
            try {
                $cache[$key] = $this->db()->getTableColumns($table) ?: [];
            } catch (\Throwable $e) {
                $cache[$key] = [];
            }
        }

        return array_key_exists($column, $cache[$key]);
    }

    private function tableExists(string $table): bool
    {
        static $cache = [];

        if (!isset($cache[$table])) {
            $db = $this->db();
            $real = str_replace('#__', $db->getPrefix(), $table);
            $cache[$table] = in_array($real, $db->getTableList(), true);
        }

        return $cache[$table];
    }

    private function parseIdList($raw): array
    {
        if (is_array($raw)) {
            $parts = $raw;
        } else {
            $parts = preg_split('/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        $ids = array_map('intval', $parts);

        return array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
    }

    private function toFloat($value): float
    {
        $value = str_replace(["\xC2\xA0", ' ', ','], ['', '', '.'], trim((string) $value));

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function bootstrapShop(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        $done = true;
        $bootstrap = JPATH_SITE . '/components/com_jshopping/bootstrap.php';
        $factory = JPATH_SITE . '/components/com_jshopping/lib/factory.php';

        if (is_file($bootstrap)) {
            require_once $bootstrap;
        } elseif (is_file($factory)) {
            require_once $factory;
        }
    }

    private function db(): DatabaseInterface
    {
        try {
            $current = $this->getDatabase();

            if ($current instanceof DatabaseInterface) {
                return $current;
            }
        } catch (\Throwable $exception) {
            // Legacy helper.php path without HelperFactory injection.
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->setDatabase($db);

        return $db;
    }
}
