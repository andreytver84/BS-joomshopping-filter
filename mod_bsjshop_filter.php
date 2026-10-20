<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_bsjshop_filter
 * @author      Andrey Uvikov (order@bestsite-studio.ru)
 */

defined('_JEXEC') or die;

use Bestsite\Module\BsjshopFilter\Site\Helper\BsjshopFilterHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$assetVersion = '1.5.0';
$app = Factory::getApplication();
$doc = $app->getDocument();
$base = Uri::root(true);

if (!class_exists(BsjshopFilterHelper::class)) {
    require_once __DIR__ . '/src/Helper/BsjshopFilterHelper.php';
}

$helper = new BsjshopFilterHelper();
$shopReady = $helper->isShopReady();
$categories = [];
$bounds = [];
$filters = [];
$items = [];
$hasMore = false;
$currencyCode = '';

if ($shopReady) {
    $catalog = $helper->getCatalogData($params, (int) $module->id);
    $categories = $catalog['categories'];
    $bounds = $catalog['bounds'];
    $filters = $catalog['filters'];
    $items = $catalog['items'];
    $hasMore = $catalog['hasMore'];
    $currencyCode = $catalog['currencyCode'];
}

$doc->addStyleSheet($base . '/modules/mod_bsjshop_filter/assets/css/style.css', ['version' => $assetVersion]);
$doc->addScript($base . '/modules/mod_bsjshop_filter/assets/js/script.js', ['version' => $assetVersion], ['defer' => true]);

$layoutPath = ModuleHelper::getLayoutPath('mod_bsjshop_filter', $params->get('layout', 'default'));

if ($layoutPath) {
    require $layoutPath;
} else {
    echo '<div class="bsjf-error">' . Text::_('MOD_BSJSHOP_FILTER_ERROR_LAYOUT') . '</div>';
}
