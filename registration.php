<?php

use Magento\Framework\Component\ComponentRegistrar;

require_once __DIR__ . '/vendor/autoload.php';

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Comfino_ComfinoGateway', __DIR__);
