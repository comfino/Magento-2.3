# Comfino Shop Plugins Shared Library

A shared PHP library for e-commerce shop plugins integrating with the Comfino payment API. This library provides a common foundation for various shop platform plugins (WooCommerce, PrestaShop, Magento, etc.) by consolidating all dependencies and core functionality for Comfino payment processing.

## Features

- PSR-18/PSR-7 compatible HTTP client for Comfino API.
- Complete API abstraction layer with request/response handling.
- Shop integration interfaces for easy platform-specific implementations.
- Product category management and filtering.
- Financial product eligibility checking.
- Widget and paywall rendering utilities.
- Configuration management with storage adapter pattern.
- PSR-6 compliant caching (filesystem and in-memory).
- Error reporting and logging facilities.
- Support for sandbox and production environments.

## Requirements

- PHP 7.1 or higher
- Composer
- Required PHP extensions:
  - `ext-curl`
  - `ext-json`
  - `ext-zlib`

## Installation

Install via Composer:

```bash
composer require comfino/shop-plugins-shared
```

## Basic usage

### Creating an API client

```php
use Comfino\Common\Backend\Factory\ApiClientFactory;

$factory = new ApiClientFactory();
$client = $factory->createClient(
    $apiKey,                      // Your Comfino API key
    $userAgent,                   // Custom User-Agent string
    null,                         // API host (null for default)
    'pl',                         // Language code
    1,                            // Connection timeout (seconds)
    3,                            // Transfer timeout (seconds)
    3                             // Max connection attempts
);

// Enable sandbox mode for testing.
$client->enableSandboxMode();

// Check if shop account is active.
$isActive = $client->isShopAccountActive();
```

### Implementing shop integration

To integrate with your e-commerce platform, implement the required interfaces:

```php
use Comfino\Shop\Order\OrderInterface;
use Comfino\Shop\Order\CartInterface;
use Comfino\Shop\Order\CustomerInterface;

class MyShopOrder implements OrderInterface
{
    public function getId(): string { /* ... */ }
    public function getCart(): CartInterface { /* ... */ }
    public function getCustomer(): CustomerInterface { /* ... */ }
    // ... implement remaining methods
}
```

### Creating a loan application

```php
use Comfino\Api\Dto\Payment\LoanQueryCriteria;

// Get available financial products.
$queryCriteria = new LoanQueryCriteria($loanAmount, $loanTerm);
$products = $client->getFinancialProducts($queryCriteria);

// Create order (loan application).
$order = new MyShopOrder(/* ... */);
$response = $client->createOrder($order);

// Redirect customer to Comfino.
header('Location: ' . $response->applicationUrl);
```

### Initializing cache

```php
use Comfino\PluginShared\CacheManager;

// Initialize cache with filesystem storage.
CacheManager::init('/path/to/cache/directory');

// Use cache.
CacheManager::set('key', 'value', 3600); // TTL in seconds
$value = CacheManager::get('key', 'default_value');
```

## API environments

- **Production**: `https://api-ecommerce.comfino.pl`
- **Sandbox**: `https://api-ecommerce.craty.pl`

Toggle between environments using `enableSandboxMode()` and `disableSandboxMode()` methods.

## Testing

Run the test suite with PHPUnit:

```bash
# Run all tests.
vendor/bin/phpunit

# Run with coverage.
XDEBUG_MODE=coverage vendor/bin/phpunit

# Run specific test.
vendor/bin/phpunit tests/Path/To/SpecificTest.php
```

## Architecture

### Core components

1. **API client layer** (`src/Api/`)
    - `Client.php`: Main API client for Comfino e-commerce API with PSR-18/PSR-7 HTTP compatibility.
    - `Request.php`: Abstract base for all API request types.
    - `Response.php`: Abstract base for all API response types.
    - Request/Response pairs in `Api/Request/` and `Api/Response/` subdirectories.
    - Supports both production (`api-ecommerce.comfino.pl`) and sandbox (`api-ecommerce.craty.pl`) environments.
    - Serialization via `SerializerInterface` (default: JSON).

2. **Extended API client** (`src/Extended/Api/`)
    - `Client.php`: Extends base API client with additional functionality:
        - Error reporting (`sendLoggedError`)
        - Plugin removal notification (`notifyPluginRemoval`)
        - Abandoned cart tracking (`notifyAbandonedCart`)
    - Uses enhanced JSON serializer with extended DTO support

3. **Shop integration interfaces** (`src/Shop/Order/`)
    - `OrderInterface`: Shop order abstraction with cart, customer, loan parameters.
    - `CartInterface`, `CartItemInterface`, `ProductInterface`: Shopping cart abstractions.
    - `CustomerInterface`, `AddressInterface`: Customer data abstractions.
    - `LoanParametersInterface`, `SellerInterface`: Financial transaction details.
    - These interfaces must be implemented by consuming plugins to integrate with their shop platforms.

4. **Backend utilities** (`src/Common/Backend/`)
    - `ConfigurationManager.php`: Centralized configuration management with type-safe options and storage adapter pattern.
    - `ErrorLogger.php`, `DebugLogger.php`: Logging facilities.
    - `RestEndpointManager.php`: Manages REST endpoints for shop plugin callbacks.
    - `Factory/`: Factory classes for creating API clients and order objects.
    - `Payment/ProductTypeFilter/`: Filters for financial product eligibility based on cart value and product categories.

5. **Frontend rendering** (`src/Common/Frontend/`)
    - `WidgetIframeRenderer.php`: Renders Comfino payment widgets.
    - `PaywallIframeRenderer.php`: Renders payment selection paywalls.
    - `WidgetInitScriptHelper.php`: JavaScript initialization helpers.

6. **Caching** (`src/PluginShared/CacheManager.php`)
    - PSR-6 cache implementation with filesystem and array adapters.
    - Supports cache tags and TTL.
    - Falls back to in-memory cache if filesystem unavailable.

7. **Product categories** (`src/Common/Shop/Product/`)
    - `CategoryManager.php`: Manages product category hierarchies.
    - `CategoryTree.php`, `CategoryTree/Node.php`: Tree structure for category filtering.
    - `CategoryFilter.php`: Category-based product eligibility logic.

### API versioning
The client supports multiple API versions. Default is v1. Endpoints are constructed as: `{host}/v{version}/{endpoint-path}`

### Exception handling
API calls throw specific exceptions from `src/Api/Exception/`:
- `RequestValidationError`: Invalid request data.
- `ResponseValidationError`: Invalid response from API.
- `AuthorizationError`: API key issues.
- `AccessDenied`: Insufficient permissions.
- `ServiceUnavailable`: API service down.

All exceptions implement `HttpErrorExceptionInterface` and may also throw PSR-18 `ClientExceptionInterface`.

### Shop plugin implementation
Consuming plugins must:
1. Implement shop-specific interfaces (`OrderInterface`, `CartInterface`, etc.).
2. Use factories to create API clients.
3. Initialize `CacheManager` with appropriate cache root path.
4. Implement `StorageAdapterInterface` for `ConfigurationManager`.
5. Set up REST endpoints using `RestEndpointManager` for status notifications.

## Important notes

- This is a **library**, not a standalone application - it's consumed by various shop platform plugins.
- API credentials (API keys) are passed at runtime and should never be committed.
- The library supports multi-language and multi-currency operations.
- Sandbox mode can be toggled via `enableSandboxMode()` / `disableSandboxMode()` on the client.
- All API requests include tracking IDs for debugging (`Comfino-Track-Id` header).

## License

Proprietary. See LICENSE file for details.

## Author

**Artur Kozubski** - [akozubski@comperia.pl](mailto:akozubski@comperia.pl)

Homepage: [https://comfino.pl/plugins](https://comfino.pl/plugins)
