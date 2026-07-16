<?php

namespace Comfino\Tests\Configuration;

use Comfino\Configuration\StorageAdapter;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Comfino\Configuration\StorageAdapter
 *
 * All three Magento interfaces are faked by hand (instead of PHPUnit's getMockBuilder()) because
 * the ancient phpunit/phpunit-mock-objects 3.x mock generator bundled with PHPUnit 5.7 throws a
 * fatal "ReflectionType::__toString() is deprecated" error under this repo's PHP 7.4 Docker test
 * runtime when generating a mock for these typed interface methods (scalar parameter/return type
 * declarations, incl. nullable types and `void` returns).
 */
class StorageAdapterTest extends TestCase
{
    private const XML_PATH_API_KEY = 'payment/comfino/api_key';

    private FakeScopeConfig $scopeConfig;
    private FakeConfigWriter $configWriter;
    private FakeTypeList $cacheTypeList;

    protected function setUp(): void
    {
        $this->scopeConfig = new FakeScopeConfig();
        $this->configWriter = new FakeConfigWriter();
        $this->cacheTypeList = new FakeTypeList();
    }

    /**
     * With no explicit store ID given (legacy/default behavior), load() must keep relying on
     * Magento's ambient current-store resolution: ScopeConfigInterface::getValue() called with
     * only ($path, SCOPE_STORE), exactly as before the scoping feature was added.
     */
    public function testLoadWithoutExplicitStoreUsesAmbientScopeResolution(): void
    {
        $this->scopeConfig->values[self::XML_PATH_API_KEY] = 'ambient-api-key';

        $adapter = new StorageAdapter($this->scopeConfig, $this->configWriter, $this->cacheTypeList);
        $configuration = $adapter->load();

        self::assertSame('ambient-api-key', $configuration['COMFINO_API_KEY']);

        foreach ($this->scopeConfig->calls as $call) {
            self::assertSame(ScopeInterface::SCOPE_STORE, $call['scope']);
            self::assertNull($call['scopeCode']);
        }

        self::assertNotEmpty($this->scopeConfig->calls);
    }

    /**
     * When an explicit store ID is supplied via the constructor, load() must pass it through as
     * the third argument to ScopeConfigInterface::getValue().
     */
    public function testLoadWithExplicitStoreIdPassesStoreScope(): void
    {
        $this->scopeConfig->values[self::XML_PATH_API_KEY] = 'store-7-api-key';

        $adapter = new StorageAdapter($this->scopeConfig, $this->configWriter, $this->cacheTypeList, 7);
        $configuration = $adapter->load();

        self::assertSame('store-7-api-key', $configuration['COMFINO_API_KEY']);

        foreach ($this->scopeConfig->calls as $call) {
            self::assertSame(ScopeInterface::SCOPE_STORE, $call['scope']);
            self::assertSame('7', $call['scopeCode']);
        }

        self::assertNotEmpty($this->scopeConfig->calls);
    }

    /**
     * setStoreScope() must let an already-constructed adapter be scoped afterwards, and clearing
     * it (passing null) must restore ambient resolution.
     */
    public function testSetStoreScopeAppliesAndClearsExplicitScope(): void
    {
        $this->scopeConfig->values[self::XML_PATH_API_KEY] = 'website-3-api-key';

        $adapter = new StorageAdapter($this->scopeConfig, $this->configWriter, $this->cacheTypeList);

        $adapter->setStoreScope(3, ScopeInterface::SCOPE_WEBSITES);
        self::assertSame('website-3-api-key', $adapter->load()['COMFINO_API_KEY']);

        $lastCall = end($this->scopeConfig->calls);
        self::assertSame(ScopeInterface::SCOPE_WEBSITES, $lastCall['scope']);
        self::assertSame('3', $lastCall['scopeCode']);

        $this->scopeConfig->values[self::XML_PATH_API_KEY] = 'ambient-api-key';
        $adapter->setStoreScope(null);
        self::assertSame('ambient-api-key', $adapter->load()['COMFINO_API_KEY']);

        $lastCall = end($this->scopeConfig->calls);
        self::assertSame(ScopeInterface::SCOPE_STORE, $lastCall['scope']);
        self::assertNull($lastCall['scopeCode']);
    }

    /**
     * With no explicit store scope given, save() must keep writing to the default (global) scope
     * exactly as before — no scope/scopeId arguments passed to WriterInterface::save().
     */
    public function testSaveWithoutExplicitStoreWritesDefaultScope(): void
    {
        $adapter = new StorageAdapter($this->scopeConfig, $this->configWriter, $this->cacheTypeList);
        $adapter->save(['COMFINO_API_KEY' => 'secret']);

        self::assertCount(1, $this->configWriter->calls);
        self::assertSame(
            ['path' => self::XML_PATH_API_KEY, 'value' => 'secret', 'scope' => 'default', 'scopeId' => 0],
            $this->configWriter->calls[0]
        );
        self::assertSame(['config'], $this->cacheTypeList->cleanedTypes);
    }

    /**
     * When an explicit store ID is set, save() must forward scope + scopeId to
     * WriterInterface::save() so writes land in the intended store scope instead of default.
     */
    public function testSaveWithExplicitStoreIdWritesScopedValue(): void
    {
        $adapter = new StorageAdapter($this->scopeConfig, $this->configWriter, $this->cacheTypeList, 5);
        $adapter->save(['COMFINO_API_KEY' => 'secret']);

        self::assertCount(1, $this->configWriter->calls);
        self::assertSame(
            ['path' => self::XML_PATH_API_KEY, 'value' => 'secret', 'scope' => ScopeInterface::SCOPE_STORE, 'scopeId' => 5],
            $this->configWriter->calls[0]
        );
        self::assertSame(['config'], $this->cacheTypeList->cleanedTypes);
    }
}

/**
 * Hand-written ScopeConfigInterface double, see class docblock above for why this isn't a
 * PHPUnit-generated mock.
 */
class FakeScopeConfig implements ScopeConfigInterface
{
    /** @var array<string, mixed> */
    public array $values = [];

    /** @var array<int, array{path: string, scope: string, scopeCode: ?string}> */
    public array $calls = [];

    public function getValue(string $path, string $scopeType = 'default', ?string $scopeCode = null)
    {
        $this->calls[] = ['path' => $path, 'scope' => $scopeType, 'scopeCode' => $scopeCode];

        return $this->values[$path] ?? null;
    }

    public function isSetFlag(string $path, string $scopeType = 'default', ?string $scopeCode = null): bool
    {
        return (bool) $this->getValue($path, $scopeType, $scopeCode);
    }
}

/**
 * Hand-written WriterInterface double, see StorageAdapterTest's class docblock for why this isn't
 * a PHPUnit-generated mock.
 */
class FakeConfigWriter implements WriterInterface
{
    /** @var array<int, array{path: string, value: mixed, scope: string, scopeId: int}> */
    public array $calls = [];

    public function save(string $path, $value, string $scope = 'default', int $scopeId = 0): void
    {
        $this->calls[] = ['path' => $path, 'value' => $value, 'scope' => $scope, 'scopeId' => $scopeId];
    }

    public function delete(string $path, string $scope = 'default', int $scopeId = 0): void
    {
        // Not exercised by StorageAdapter.
    }
}

/**
 * Hand-written TypeListInterface double, see StorageAdapterTest's class docblock for why this
 * isn't a PHPUnit-generated mock.
 */
class FakeTypeList implements TypeListInterface
{
    /** @var string[] */
    public array $cleanedTypes = [];

    public function cleanType($typeCode): void
    {
        $this->cleanedTypes[] = $typeCode;
    }
}