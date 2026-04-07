<?php
/**
 * PHPUnit bootstrap for Comfino Magento plugin unit tests.
 *
 * Defines lightweight Magento framework stubs so that src/ classes can be
 * loaded and tested without a full Magento installation.
 *
 * Each namespace block below mirrors the real Magento interface/class just
 * enough to satisfy PHP's type system and the import statements used in src/.
 *
 * Tests that exercise code calling ObjectManager::getInstance()->get($class)
 * must register their own doubles via:
 *
 *   \Magento\Framework\App\ObjectManager::getInstance()->bind(Foo::class, $mock);
 */

// ─── Magento\Framework\Component ─────────────────────────────────────────────

namespace Magento\Framework\Component
{
    /**
     * Stub for ComponentRegistrar - called by registration.php (autoload.files).
     */
    class ComponentRegistrar
    {
        public const MODULE = 'module';

        public static function register(string $type, string $moduleName, string $path): void
        {
            // No-op in test context.
        }
    }
}

// ─── Magento\Store\Model ─────────────────────────────────────────────────────

namespace Magento\Store\Model
{
    interface ScopeInterface
    {
        public const SCOPE_STORE = 'store';
        public const SCOPE_STORES = 'stores';
        public const SCOPE_WEBSITE = 'website';
        public const SCOPE_WEBSITES = 'websites';
    }
}

// ─── Magento\Framework\App\Config ────────────────────────────────────────────

namespace Magento\Framework\App\Config
{
    interface ScopeConfigInterface
    {
        /**
         * @param string $path
         * @param string $scopeType
         * @param string|null $scopeCode
         *
         * @return mixed
         */
        public function getValue(string $path, string $scopeType = 'default', ?string $scopeCode = null);

        /**
         * @param string $path
         * @param string $scopeType
         * @param string|null $scopeCode
         *
         * @return bool
         */
        public function isSetFlag(string $path, string $scopeType = 'default', ?string $scopeCode = null): bool;
    }
}

// ─── Magento\Framework\App\Config\Storage ────────────────────────────────────

namespace Magento\Framework\App\Config\Storage
{
    interface WriterInterface
    {
        /**
         * @param string $path
         * @param mixed $value
         * @param string $scope
         * @param int $scopeId
         */
        public function save(string $path, $value, string $scope = 'default', int $scopeId = 0): void;

        /**
         * @param string $path
         * @param string $scope
         * @param int $scopeId
         */
        public function delete(string $path, string $scope = 'default', int $scopeId = 0): void;
    }
}

// ─── Magento\Framework\Filesystem ────────────────────────────────────────────

namespace Magento\Framework\Filesystem
{
    class DirectoryList
    {
        /** @var string */
        private string $root;

        public function __construct(string $root)
        {
            $this->root = rtrim($root, '/');
        }

        public function getPath(string $code): string
        {
            return $this->root . '/' . $code;
        }
    }
}

// ─── Magento\Framework ───────────────────────────────────────────────────────

namespace Magento\Framework
{
    interface UrlInterface
    {
        /**
         * @param string|null $routePath
         * @param array|null $routeParams
         * @return string
         */
        public function getUrl(?string $routePath = null, ?array $routeParams = null): string;
    }
}

// ─── Magento\Framework\App ───────────────────────────────────────────────────

namespace Magento\Framework\App
{
    /**
     * Minimal ObjectManager stub.
     *
     * Tests register doubles before exercising code that calls
     * ObjectManager::getInstance()->get(SomeClass::class).
     *
     * Example:
     *   $om = \Magento\Framework\App\ObjectManager::getInstance();
     *   $om->bind(\Comfino\ComfinoGateway\Helper\Data::class, $helperMock);
     */
    class ObjectManager
    {
        /** @var ObjectManager|null */
        private static ?ObjectManager $instance;

        /** @var array<string, object> */
        private array $bindings = [];

        private function __construct() {}

        public static function getInstance(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Replace the singleton (useful in tearDown to reset state between tests).
         */
        public static function setInstance(self $instance): void
        {
            self::$instance = $instance;
        }

        /**
         * Register a test double for a given class/interface name.
         *
         * @param string $class Fully qualified class or interface name.
         * @param object $object The double (mock/stub/spy) to return.
         */
        public function bind(string $class, object $object): void
        {
            $this->bindings[$class] = $object;
        }

        /**
         * @param string $type
         *
         * @return object
         *
         * @throws \RuntimeException when no double has been registered for $type.
         */
        public function get(string $type): object
        {
            if (isset($this->bindings[$type])) {
                return $this->bindings[$type];
            }

            throw new \RuntimeException(
                "ObjectManager: no binding registered for '{$type}'. "
                . "Call ObjectManager::getInstance()->bind('{$type}', \$double) in your test setUp()."
            );
        }

        /**
         * @param string $type
         * @param array $arguments
         *
         * @return object
         */
        public function create(string $type, array $arguments = []): object
        {
            return $this->get($type);
        }
    }
}

// ─── Autoloader ──────────────────────────────────────────────────────────────

namespace
{
    require_once __DIR__ . '/../vendor/autoload.php';
}
