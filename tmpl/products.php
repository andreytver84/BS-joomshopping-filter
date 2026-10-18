<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_bsjshop_filter
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$items = $items ?? [];

foreach ($items as $item) :
    ?>
    <article class="bsjf-card">
        <a class="bsjf-card-media" href="<?php echo $e($item->url); ?>">
            <?php if ($item->image !== '') : ?>
                <img src="<?php echo $e($item->image); ?>" alt="<?php echo $e($item->name); ?>" loading="lazy">
            <?php else : ?>
                <span class="bsjf-card-placeholder" aria-hidden="true"></span>
            <?php endif; ?>
        </a>
        <h3 class="bsjf-card-title">
            <a href="<?php echo $e($item->url); ?>"><?php echo $e($item->name); ?></a>
        </h3>
        <?php if (!empty($item->meta)) : ?>
            <p class="bsjf-card-meta">
                <?php foreach ($item->meta as $index => $meta) : ?>
                    <?php if ($index > 0) : ?>
                        <span class="bsjf-meta-sep" aria-hidden="true"></span>
                    <?php endif; ?>
                    <span class="bsjf-meta-item"><?php echo $e($meta); ?></span>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
        <div class="bsjf-card-footer">
            <div class="bsjf-card-price">
                <?php if (!empty($item->pricePrefix)) : ?>
                    <span class="bsjf-price-prefix"><?php echo $e($item->pricePrefix); ?></span>
                <?php endif; ?>
                <span class="bsjf-price-value"><?php echo $e($item->price); ?></span>
            </div>
            <a class="bsjf-card-link" href="<?php echo $e($item->url); ?>">
                <?php echo $e(Text::_('MOD_BSJSHOP_FILTER_DETAILS')); ?>
            </a>
        </div>
    </article>
<?php endforeach; ?>
