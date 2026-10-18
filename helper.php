<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_bsjshop_filter
 */

defined('_JEXEC') or die;

use Bestsite\Module\BsjshopFilter\Site\Helper\BsjshopFilterHelper;

if (!class_exists(BsjshopFilterHelper::class)) {
    require_once __DIR__ . '/src/Helper/BsjshopFilterHelper.php';
}

/**
 * Legacy com_ajax entry (Joomla 4 helper.php fallback).
 */
class ModBsjshopFilterHelper
{
    public static function filterAjax()
    {
        return (new BsjshopFilterHelper())->filterAjax();
    }
}

if (!class_exists('ModBsjshop_filterHelper')) {
    class_alias(ModBsjshopFilterHelper::class, 'ModBsjshop_filterHelper');
}

if (!class_exists('ModBsjshop_FilterHelper')) {
    class_alias(ModBsjshopFilterHelper::class, 'ModBsjshop_FilterHelper');
}
