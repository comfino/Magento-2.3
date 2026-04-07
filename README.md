# Comfino Payment Gateway for Magento 2

[![Tests](https://github.com/comfino/Magento-2.3/workflows/Tests/badge.svg)](https://github.com/comfino/Magento-2.3/actions)
[![PHP Version](https://img.shields.io/badge/php-7.4%20to%208.4-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-OSL--3.0-green.svg)](LICENSE)

Magento 2 payment module for Comfino deferred payments gateway - installment payments, buy now pay later (BNPL) and corporate payments.

## Installation

### Polish

[Installation guide (Polish)](https://github.com/comfino/Magento-2.3/blob/master/docs/comfino.pl.md)

### English

[Installation guide (English)](https://github.com/comfino/Magento-2.3/blob/master/docs/comfino.en.md)

## Compatibility

- **Magento**: 2.3.5 or higher
- **PHP**: 7.4 or higher
- **PHP extensions**: ctype, curl, json, zlib

## Development

### Requirements

- PHP 7.4 or higher
- Magento 2.3.5 or higher
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