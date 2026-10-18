<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_bsjshop_filter
 */

namespace Bestsite\Module\BsjshopFilter\Site\Dispatcher;

\defined('_JEXEC') or die;

use Bestsite\Module\BsjshopFilter\Site\Helper\BsjshopFilterHelper;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Uri\Uri;

class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $helper = $this->getHelperFactory()->getHelper('BsjshopFilterHelper');
        $params = $data['params'];
        $module = $data['module'];
        $doc = $this->getApplication()->getDocument();
        $base = Uri::root(true);
        $version = BsjshopFilterHelper::VERSION;

        $doc->addStyleSheet($base . '/modules/mod_bsjshop_filter/assets/css/style.css', ['version' => $version]);
        $doc->addScript($base . '/modules/mod_bsjshop_filter/assets/js/script.js', ['version' => $version], ['defer' => true]);

        $data['assetVersion'] = $version;
        $data['shopReady'] = $helper->isShopReady();
        $data['base'] = $base;

        if (!$data['shopReady']) {
            $data['categories'] = [];
            $data['bounds'] = [];
            $data['filters'] = [];
            $data['items'] = [];
            $data['hasMore'] = false;

            return $data;
        }

        $catalog = $helper->getCatalogData($params, (int) $module->id);

        return array_merge($data, $catalog);
    }
}
