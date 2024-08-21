<?php

/**
 * @package Chirp
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chirp;

use DecodeLabs\Tagged\Buffer;
use DecodeLabs\Tagged\Element;
use DecodeLabs\Tagged\Markup;

class Parser
{
    protected const UrlPre = '(?:[^-\\/"\':!=a-z0-9_@＠]|^|\\:)'; // @ignore-non-ascii
    protected const Domain = '(?:[^\\p{P}\\p{Lo}\\s][\\.-](?=[^\\p{P}\\p{Lo}\\s])|[^\\p{P}\\p{Lo}\\s])+\\.[a-z]{2,}(?::[0-9]+)?';
    protected const Tld = '/\\.(?:com|net|org|gov|edu|uk)$/iu';
    protected const Path = '(?:(?:\\([a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\))|@[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\/|[\\.\\,]?(?:[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_~]|,(?!\s)))';
    protected const PathEnd = '[a-z0-9=#\\/]';
    protected const Query = '[a-z0-9!\\*\'\\(\\);:&=\\+\\$\\/%#\\[\\]\\-_\\.,~]';
    protected const QueryEnd = '[a-z0-9_&=#\\/]';

    protected const Url = '/(?:' .
        '(' . self::UrlPre . ')' .
        '(' .
        '((?:https?:\\/\\/|www\\.)?)' .
        '(' . self::Domain . ')' .
        '(\\/' . self::Path . '*' .
        self::PathEnd . '?)?' .
        '(\\?' . self::Query . '*' .
        self::QueryEnd . ')?' .
        ')' .
        ')/iux';

    protected const UserList = '/([^a-z0-9_\/]|^|RT:?)([@＠]+)([a-z0-9_]{1,20})(\/[a-z][-_a-z0-9\x80-\xFF]{0,24})?([@＠\xC0-\xD6\xD8-\xF6\xF8-\xFF]?)/iu'; // @ignore-non-ascii
    protected const Mention = '/(^|[^a-z0-9_])[@＠]([a-z0-9_]{1,20})([@＠\xC0-\xD6\xD8-\xF6\xF8-\xFF]?)/iu'; // @ignore-non-ascii
    protected const Reply = '/^(' . self::WhiteSpace . ')*[@＠]([a-zA-Z0-9_]{1,20})/'; // @ignore-non-ascii

    protected const HashTag = '/(^|[^0-9A-Z&\/\?]+)([#＃]+)([0-9A-Z_]*[A-Z_]+[a-z0-9_üÀ-ÖØ-öø-ÿ]*)/iu'; // @ignore-non-ascii
    protected const WhiteSpace = '[\x09-\x0D\x20\x85\xA0]|\xe1\x9a\x80|\xe1\xa0\x8e|\xe2\x80[\x80-\x8a,\xa8,\xa9,\xaf\xdf]|\xe3\x80\x80';

    protected const BaseUrl = 'https://x.com/';
    protected const SearchPath = 'search?q=%23';

    /**
     * Convert plaintext tweet to HTML
     */
    public function parse(
        ?string $text
    ): ?Markup {
        if (empty($text)) {
            return null;
        }

        $text = $this->esc($text);

        // Urls
        $text = $this->processUrls($text);

        // HashTags
        $text = $this->processHashTags($text);

        // Usernames
        $text = $this->processUsernames($text);

        return new Buffer($text);
    }

    /**
     * Escape HTML
     */
    protected function esc(
        string $text
    ): string {
        return htmlspecialchars($text, \ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Process URLs
     */
    protected function processUrls(
        string $text
    ): string {
        return preg_replace_callback(self::Url, function ($matches) {
            list($all, $before, $url, $protocol, $domain) = array_pad($matches, 7, '');
            $url = $this->esc($url);

            if (!$protocol && !preg_match(self::Tld, $domain)) {
                return $all;
            }

            $href = (
                !$protocol ||
                strtolower($protocol) === 'www.'
            ) ?
                'https://' . $url :
                $url;

            return $before . $this->wrapUrl($href, 'url', $url);
        }, $text) ?? $text;
    }


    /**
     * Process hash tags
     */
    protected function processHashTags(
        string $text
    ): string {
        return preg_replace_callback(self::HashTag, function ($matches) {
            $replacement = $matches[1];
            $element = $matches[2] . $matches[3];
            $url = self::BaseUrl . self::SearchPath . $matches[3];
            return $replacement . $this->wrapUrl($url, 'hashtag', $element);
        }, $text) ?? $text;
    }


    /**
     * Process usernames
     */
    protected function processUsernames(
        string $text
    ): string {
        return preg_replace_callback(self::UserList, function ($matches) {
            /**
             * @var string $before
             * @var string $after
             */
            list($all, $before, $at, $username, $listname, $after) = array_pad($matches, 6, '');

            if (!empty($after)) {
                return $all;
            }

            if (!empty($listname)) {
                $element = $username . substr($listname, 0, 26);
                $class = 'list';
                $url = self::BaseUrl . $element;
                $suffix = substr($listname, 26);
            } else {
                $element = $username;
                $class = 'user';
                $url = self::BaseUrl . $element;
                $suffix = '';
            }

            return $before . $this->wrapUrl($url, $class, $at . $element) . $suffix . $after;
        }, $text) ?? $text;
    }


    /**
     * Generate a link tag for URL
     */
    protected function wrapUrl(
        string $url,
        ?string $class,
        string $content
    ): Element {
        $output = Element::create('a', $content, [
            'href' => $url,
            'rel' => 'external nofollow',
            'target' => '_blank'
        ]);

        if ($class !== null) {
            $output->addClass($class);
        }

        return $output;
    }
}
