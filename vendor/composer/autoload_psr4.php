<?php

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'Comfino\\ComfinoGateway\\' => array($baseDir . '/'),
    'Comfino\\' => array($baseDir . '/src', $vendorDir . '/comfino/shop-plugins-shared/src'),
    'ComfinoExternal\\Symfony\\Polyfill\\Php80\\' => array($vendorDir . '/symfony/polyfill-php80'),
    'ComfinoExternal\\Symfony\\Polyfill\\Ctype\\' => array($vendorDir . '/symfony/polyfill-ctype'),
    'ComfinoExternal\\Symfony\\Component\\Yaml\\' => array($vendorDir . '/symfony/yaml'),
    'ComfinoExternal\\Symfony\\Component\\OptionsResolver\\' => array($vendorDir . '/symfony/options-resolver'),
    'ComfinoExternal\\Sunrise\\Uri\\' => array($vendorDir . '/sunrise/uri/src'),
    'ComfinoExternal\\Sunrise\\Stream\\' => array($vendorDir . '/sunrise/stream/src'),
    'ComfinoExternal\\Sunrise\\Http\\ServerRequest\\' => array($vendorDir . '/sunrise/http-server-request/src'),
    'ComfinoExternal\\Sunrise\\Http\\Message\\' => array($vendorDir . '/sunrise/http-message/src'),
    'ComfinoExternal\\Sunrise\\Http\\Header\\' => array($vendorDir . '/sunrise/http-header/src'),
    'ComfinoExternal\\Sunrise\\Http\\Factory\\' => array($vendorDir . '/sunrise/http-factory/src'),
    'ComfinoExternal\\Sunrise\\Http\\Client\\Curl\\' => array($vendorDir . '/sunrise/http-client-curl/src'),
    'ComfinoExternal\\Psr\\SimpleCache\\' => array($vendorDir . '/psr/simple-cache/src'),
    'ComfinoExternal\\Psr\\Log\\' => array($vendorDir . '/psr/log/Psr/Log'),
    'ComfinoExternal\\Psr\\Http\\Message\\' => array($vendorDir . '/psr/http-factory/src', $vendorDir . '/psr/http-message/src'),
    'ComfinoExternal\\Psr\\Http\\Client\\' => array($vendorDir . '/psr/http-client/src'),
    'ComfinoExternal\\Psr\\Cache\\' => array($vendorDir . '/psr/cache/src'),
    'ComfinoExternal\\Monolog\\' => array($vendorDir . '/monolog/monolog/src/Monolog'),
    'ComfinoExternal\\League\\Flysystem\\' => array($vendorDir . '/league/flysystem/src'),
    'ComfinoExternal\\Fig\\Http\\Message\\' => array($vendorDir . '/fig/http-message-util/src'),
    'ComfinoExternal\\Cache\\TagInterop\\' => array($vendorDir . '/cache/tag-interop'),
    'ComfinoExternal\\Cache\\Hierarchy\\' => array($vendorDir . '/cache/hierarchical-cache'),
    'ComfinoExternal\\Cache\\Adapter\\PHPArray\\' => array($vendorDir . '/cache/array-adapter'),
    'ComfinoExternal\\Cache\\Adapter\\Filesystem\\' => array($vendorDir . '/cache/filesystem-adapter'),
    'ComfinoExternal\\Cache\\Adapter\\Common\\' => array($vendorDir . '/cache/adapter-common'),
);
