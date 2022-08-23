# Chirp

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/chirp?style=flat)](https://packagist.org/packages/decodelabs/chirp)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/chirp.svg?style=flat)](https://packagist.org/packages/decodelabs/chirp)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/chirp.svg?style=flat)](https://packagist.org/packages/decodelabs/chirp)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/decodelabs/chirp/PHP%20Composer)](https://github.com/decodelabs/chirp/actions/workflows/php.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/chirp?style=flat)](https://packagist.org/packages/decodelabs/chirp)

Twitter tools for PHP


## Installation

Install the library via composer:

```bash
composer require decodelabs/chirp
```

## Usage

Parse a tweet into HTML:

```php
use DecodeLabs\Chirp\Parser;

$parser = new Parser();
echo $parser->parse($myTweet);
```

### Metamorph

Chirp also provides a [Metamorph](https://github.com/decodelabs/metamorph/) Handler so that it can be used via its simplified interface:

```php
use DecodeLabs\Metamorph;

echo Metamorph::tweet($myTweet);
```

## Output

The parsed HTML provided by Chirp is now wrapped in a <code>Markup</code> interface from the [Tagged](https://github.com/decodelabs/tagged/) library such that output is handled correctly in all rendering contexts.



## Licensing
Chirp is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
