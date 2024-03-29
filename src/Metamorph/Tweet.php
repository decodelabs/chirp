<?php

/**
 * @package Metamorph
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Metamorph\Handler;

use DecodeLabs\Chirp\Parser;
use DecodeLabs\Metamorph\Handler;
use Stringable;

class Tweet implements Handler
{
    /**
     * Convert markdown to HTML
     */
    public function convert(
        string $content,
        ?callable $setup = null
    ): string|Stringable|null {
        $parser = new Parser();

        if ($setup) {
            $setup($parser);
        }

        return $parser->parse($content);
    }
}
