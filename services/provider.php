<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_bsjshop_filter
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Bestsite\\Module\\BsjshopFilter'));
        $container->registerServiceProvider(new HelperFactory('\\Bestsite\\Module\\BsjshopFilter\\Site\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
