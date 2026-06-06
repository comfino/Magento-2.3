<?php

namespace Comfino\ComfinoGateway\Setup\Patch\Data;

use Comfino\Common\Frontend\WidgetInitScriptHelper;
use Comfino\ComfinoGateway\Helper\Data;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/* When the module lives under app/code/, Magento's autoloader does not honour the
  "Comfino\": "src/" PSR-4 mapping from the module's own composer.json.
   Manually load the required class file so it is available regardless of how
   the module is deployed (app/code/ copy vs. Composer vendor/ install).
   class_exists() guard prevents fatal "cannot redeclare" errors when Composer's
   autoloader has already resolved the class from vendor/. */
(static function (): void {
    if (!class_exists(WidgetInitScriptHelper::class, false)) {
        $moduleRoot = dirname(__DIR__, 3);
        require_once $moduleRoot . '/vendor/comfino/shop-plugins-shared/src/Common/Frontend/WidgetInitScriptHelper.php';
    }
})();

/**
 * Populates payment/comfino/widget_code with the default banner init script template on installation and on
 * the first upgrade after it is introduced (3.0.0 -> 3.1.0).
 *
 * Without this the config row is empty on fresh setups, so the admin "widget code" textarea renders blank, and any
 * reader of the stored value (instead of the runtime fallback in ConfigManager::getCurrentWidgetCode()) receives an
 * empty initialization script.
 *
 * Data patches run once per identity (tracked in patch_list); the runtime fallback in getCurrentWidgetCode() keeps the
 * rendered script in sync afterward if the template changes.
 */
class InitWidgetCode implements DataPatchInterface
{
    private WriterInterface $configWriter;

    public function __construct(WriterInterface $configWriter)
    {
        $this->configWriter = $configWriter;
    }

    public function apply(): self
    {
        $this->configWriter->save(Data::XML_PATH_WIDGET_CODE, WidgetInitScriptHelper::getInitialWidgetCode());

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}