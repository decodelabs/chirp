# Chirp
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
echo $parser->parser($myTweet);
```


## Licensing
Chirp is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
