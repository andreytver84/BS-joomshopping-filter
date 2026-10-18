<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_bsjshop_filter
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

$e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$app = $app ?? Factory::getApplication();
$moduleId = (int) $module->id;
$sfx = preg_replace('/[^A-Za-z0-9 _-]/', '', (string) $params->get('moduleclass_sfx', ''));
$sfx = trim(preg_replace('/\s+/', ' ', (string) $sfx));
$title = trim((string) $params->get('catalog_title', ''));
$showAll = (int) $params->get('show_all_tab', 1) === 1;
$showPrice = (int) $params->get('show_price', 1) === 1;
$showArea = (int) $params->get('show_area', 1) === 1 && !empty($bounds['hasArea']);
$shopReady = !empty($shopReady);
$categories = $categories ?? [];
$bounds = $bounds ?? [];
$filters = $filters ?? [];
$items = $items ?? [];
$hasMore = !empty($hasMore);
$token = Session::getFormToken();
$itemId = (int) $app->input->getInt('Itemid', 0);
$ajaxUrl = Uri::root(true) . '/index.php';
$priceMin = $bounds['priceMin'] ?? 0;
$priceMax = $bounds['priceMax'] ?? 0;
$areaMin = $bounds['areaMin'] ?? 0;
$areaMax = $bounds['areaMax'] ?? 0;
$priceFrom = $filters['price_from'] ?? $priceMin;
$priceTo = $filters['price_to'] ?? $priceMax;
$areaFrom = $filters['area_from'] ?? $areaMin;
$areaTo = $filters['area_to'] ?? $areaMax;
$activeCat = (int) ($filters['category_id'] ?? 0);
$limit = (int) ($filters['limit'] ?? $params->get('limit', 6));
$areaUnit = trim((string) $params->get('extra_field_area_unit', 'м²'));
?>
<div
    class="bsjf<?php echo $sfx !== '' ? ' ' . $e($sfx) : ''; ?>"
    data-bsjf
    data-module-id="<?php echo $moduleId; ?>"
    data-ajax-url="<?php echo $e($ajaxUrl); ?>"
    data-token="<?php echo $e($token); ?>"
    data-itemid="<?php echo $itemId; ?>"
    data-limit="<?php echo $limit; ?>"
    data-price-min="<?php echo $e((string) $priceMin); ?>"
    data-price-max="<?php echo $e((string) $priceMax); ?>"
    data-area-min="<?php echo $e((string) $areaMin); ?>"
    data-area-max="<?php echo $e((string) $areaMax); ?>"
>
    <?php if ($title !== '') : ?>
        <h2 class="bsjf-title"><?php echo $e($title); ?></h2>
    <?php endif; ?>

    <?php if (!$shopReady) : ?>
        <p class="bsjf-error"><?php echo $e(Text::_('MOD_BSJSHOP_FILTER_ERROR_NO_JSHOP')); ?></p>
    <?php else : ?>
        <?php if ($showAll || $categories) : ?>
            <div class="bsjf-tabs" role="tablist">
                <?php if ($showAll) : ?>
                    <button type="button" class="bsjf-tab<?php echo $activeCat === 0 ? ' is-active' : ''; ?>" data-bsjf-cat="0">
                        <?php echo $e(Text::_('MOD_BSJSHOP_FILTER_CAT_ALL')); ?>
                    </button>
                <?php endif; ?>
                <?php foreach ($categories as $category) : ?>
                    <button
                        type="button"
                        class="bsjf-tab<?php echo $activeCat === (int) $category->id ? ' is-active' : ''; ?>"
                        data-bsjf-cat="<?php echo (int) $category->id; ?>"
                    >
                        <?php echo $e($category->name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="bsjf-form" data-bsjf-form action="" method="get">
            <input type="hidden" name="<?php echo 'bsjf_' . $moduleId . '_cat'; ?>" value="<?php echo $activeCat; ?>" data-bsjf-category>
            <div class="bsjf-filters">
                <?php if ($showPrice) : ?>
                    <div class="bsjf-range">
                        <div class="bsjf-range-label"><?php echo $e(Text::_('MOD_BSJSHOP_FILTER_PRICE_LABEL')); ?></div>
                        <div class="bsjf-range-row">
                            <input
                                type="number"
                                name="<?php echo 'bsjf_' . $moduleId . '_price_from'; ?>"
                                data-bsjf-field="price_from"
                                class="bsjf-input"
                                min="0"
                                step="1"
                                value="<?php echo $e((string) (int) $priceFrom); ?>"
                                aria-label="<?php echo $e(Text::_('MOD_BSJSHOP_FILTER_PRICE_FROM_ARIA')); ?>"
                            >
                            <input
                                type="number"
                                name="<?php echo 'bsjf_' . $moduleId . '_price_to'; ?>"
                                data-bsjf-field="price_to"
                                class="bsjf-input"
                                min="0"
                                step="1"
                                value="<?php echo $e((string) (int) $priceTo); ?>"
                                aria-label="<?php echo $e(Text::_('MOD_BSJSHOP_FILTER_PRICE_TO_ARIA')); ?>"
                            >
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($showArea) : ?>
                    <div class="bsjf-range">
                        <div class="bsjf-range-label">
                            <?php echo $e(Text::_('MOD_BSJSHOP_FILTER_AREA_LABEL')); ?><?php echo $areaUnit !== '' ? ', ' . $e($areaUnit) : ''; ?>
                        </div>
                        <div class="bsjf-range-row">
                            <input
                                type="number"
                                name="<?php echo 'bsjf_' . $moduleId . '_area_from'; ?>"
                                data-bsjf-field="area_from"
                                class="bsjf-input"
                                min="0"
                                step="0.01"
                                value="<?php echo $e((string) $areaFrom); ?>"
                                aria-label="<?php echo $e(Text::_('MOD_BSJSHOP_FILTER_AREA_FROM_ARIA')); ?>"
                            >
                            <input
                                type="number"
                                name="<?php echo 'bsjf_' . $moduleId . '_area_to'; ?>"
                                data-bsjf-field="area_to"
                                class="bsjf-input"
                                min="0"
                                step="0.01"
                                value="<?php echo $e((string) $areaTo); ?>"
                                aria-label="<?php echo $e(Text::_('MOD_BSJSHOP_FILTER_AREA_TO_ARIA')); ?>"
                            >
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bsjf-actions">
                    <button type="submit" class="bsjf-btn"><?php echo $e(Text::_('MOD_BSJSHOP_FILTER_APPLY')); ?></button>
                    <button type="button" class="bsjf-btn-reset" data-bsjf-reset><?php echo $e(Text::_('MOD_BSJSHOP_FILTER_RESET')); ?></button>
                </div>
            </div>
        </form>

        <div class="bsjf-grid" data-bsjf-grid>
            <?php
            $productsLayout = ModuleHelper::getLayoutPath('mod_bsjshop_filter', 'products');
            include $productsLayout;
            ?>
        </div>

        <p class="bsjf-empty<?php echo $items ? ' is-hidden' : ''; ?>" data-bsjf-empty>
            <?php echo $e(Text::_('MOD_BSJSHOP_FILTER_EMPTY')); ?>
        </p>

        <div class="bsjf-more-wrap">
            <button type="button" class="bsjf-btn bsjf-more<?php echo $hasMore ? '' : ' is-hidden'; ?>" data-bsjf-more>
                <?php echo $e(Text::_('MOD_BSJSHOP_FILTER_MORE')); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
