# Comfino Payment Gateway for Magento 2

[![PHP Version](https://img.shields.io/badge/php-7.4%20to%208.4-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-OSL--3.0-green.svg)](LICENSE)

> **Notice:** Version 3.1.0 is the **last release compatible with PHP 7.4** and **Magento 2.3.7**. The upcoming version 4.0.0 will require **PHP 8.1 or higher**, **Magento 2.4.4 or higher**, and is actively developed in the separate [magento2](https://github.com/comfino/magento2) repository. It is also compatible with Hyvä Theme and supports Hyvä Checkout via additional module [magento2-hyva-checkout](https://github.com/comfino/magento2-hyva-checkout). Please plan your environment upgrade accordingly. The new module will be available as an official Composer package from the `comfino/magento2` channel.

Magento 2 payment module for Comfino deferred payments gateway - installment payments, buy now pay later (BNPL) and corporate payments.

## Installation

### Polish

[Installation guide (Polish)](https://github.com/comfino/Magento-2.3/blob/master/docs/comfino.pl.md)

### English

[Installation guide (English)](https://github.com/comfino/Magento-2.3/blob/master/docs/comfino.en.md)

## Compatibility

### Current version (3.1.0 — last PHP 7.4 compatible release)

- **Magento**: 2.3.7 or higher
- **PHP**: 7.4 or higher
- **PHP extensions**: ctype, curl, json, zlib

### Upcoming version (4.0.0)

- **Magento**: 2.4.4 or higher
- **PHP**: 8.1 or higher
- **PHP extensions**: ctype, curl, json, sodium, zlib
- Repository: [github.com/comfino/magento2](https://github.com/comfino/magento2)
- Composer: `comfino/magento2` channel

## Development

### Requirements

- PHP 7.4 or higher
- Magento 2.3.7 or higher
- PHP extensions: ctype, curl, json, zlib
- Docker and Docker Compose (for local development)

### Local development setup

```bash
# Start development environment.
docker-compose up -d

# Install dependencies.
./bin/composer install

# Run tests.
./bin/phpunit

# Run tests with coverage.
XDEBUG_MODE=coverage ./bin/phpunit --coverage-html coverage
```

### Running tests

```bash
# Direct PHPUnit execution.
./bin/phpunit

# With specific test class.
./bin/phpunit --filter FooTest

# With coverage report.
XDEBUG_MODE=coverage ./bin/phpunit --coverage-html coverage
```

## Contributing

1. Fork the repository.
2. Create your feature branch (`git checkout -b feature/amazing-feature`).
3. Commit your changes (`git commit -m 'Add amazing feature'`).
4. Push to the branch (`git push origin feature/amazing-feature`).
5. Open a Pull Request.

## License

This project is licensed under the Open Software License 3.0 - see the [LICENSE](LICENSE) file for details.

## Support

- Documentation (Polish): [Comfino Magento plugin documentation](https://comfino.pl/plugins/Magento/pl)
- Documentation (English): [Comfino Magento plugin documentation](https://comfino.pl/plugins/Magento/en)
- Issues: [GitHub Issues](https://github.com/comfino/Magento-2.3/issues)
- Website: https://comfino.pl
