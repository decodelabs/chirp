<?php
/**
 * This file is part of the Chirp package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Chirp;

class Parser
{
    const AT_SIGNS = '[@＠]';

    const URL_PRE = '(?:[^-\\/"\':!=a-z0-9_@＠]|^|\\:)';
    const URL_DOMAIN = '(?:[^\\p{P}\\p{Lo}\\s][\\.-](?=[^\\p{P}\\p{Lo}\\s])|[^\\p{P}\\p{Lo}\\s])+\\.[a-z]{2,}(?::[0-9]+)?';
    const URL_TLD = '/\\.(?:com|net|org|gov|edu|uk)$/iu';
    const URL_PATH = '(?:(?:\\([a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\))|@[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\/|[\\.\\,]?(?:[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_~]|,(?!\s)))';
    const URL_PATH_END = '[a-z0-9=#\\/]';
    const URL_QUERY = '[a-z0-9!\\*\'\\(\\);:&=\\+\\$\\/%#\\[\\]\\-_\\.,~]';
    const URL_QUERY_END = '[a-z0-9_&=#\\/]';

    const URL_ALL = '/(?:'.
        '('.self::URL_PRE.')'.
        '('.
        '((?:https?:\\/\\/|www\\.)?)'.
        '('.self::URL_DOMAIN.')'.
        '(\\/'.self::URL_PATH.'*'.
        self::URL_PATH_END.'?)?'.
        '(\\?'.self::URL_QUERY.'*'.
        self::URL_QUERY_END.')?'.
        ')'.
        ')/iux';

    const USERNAME_LIST = '/([^a-z0-9_\/]|^|RT:?)([@＠]+)([a-z0-9_]{1,20})(\/[a-z][-_a-z0-9\x80-\xFF]{0,24})?([@＠\xC0-\xD6\xD8-\xF6\xF8-\xFF]?)/iu';
    const USERNAME_MENTION = '/(^|[^a-z0-9_])[@＠]([a-z0-9_]{1,20})([@＠\xC0-\xD6\xD8-\xF6\xF8-\xFF]?)/iu';
    const USERNAME_REPLY = '/^('.self::WHITESPACE.')*[@＠]([a-zA-Z0-9_]{1,20})/';

    const HASHTAG = '/(^|[^0-9A-Z&\/\?]+)([#＃]+)([0-9A-Z_]*[A-Z_]+[a-z0-9_üÀ-ÖØ-öø-ÿ]*)/iu';
    const WHITESPACE = '[\x09-\x0D\x20\x85\xA0]|\xe1\x9a\x80|\xe1\xa0\x8e|\xe2\x80[\x80-\x8a,\xa8,\xa9,\xaf\xdf]|\xe3\x80\x80';

    const BASE_URL = 'https://twitter.com/';
    const SEARCH_PATH = 'search?q=%23';

    /**
     * Convert plaintext tweet to HTML
     */
    public function parse(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $output = htmlspecialchars($text, \ENT_QUOTES, 'UTF-8', false);

        // Urls
        $output = preg_replace_callback(self::URL_ALL, function ($matches) {
            list($all, $before, $url, $protocol, $domain, $path, $query) = array_pad($matches, 7, '');
            $url = htmlspecialchars($url, \ENT_QUOTES, 'UTF-8', false);

            if (!$protocol && !preg_match(self::URL_TLD, $domain)) {
                return $all;
            }

            $href = ((!$protocol || strtolower($protocol) === 'www.') ? 'https://'.$url : $url);
            return $before.$this->wrapUrl($href, 'url', $url);
        }, $output) ?? $output;

        // Hashtags
        $output = preg_replace_callback(self::HASHTAG, function ($matches) {
            $replacement = $matches[1];
            $element = $matches[2].$matches[3];
            $url = self::BASE_URL.self::SEARCH_PATH.$matches[3];
            $replacement .= $this->wrapUrl($url, 'hashtag', $element);
            return $replacement;
        }, $output) ?? $output;

        // Usernames
        $output = preg_replace_callback(self::USERNAME_LIST, function ($matches) {
            list($all, $before, $at, $username, $listname, $after) = array_pad($matches, 6, '');

            if (!empty($after)) {
                return $all;
            }

            if (!empty($listname)) {
                $element = $username.substr($listname, 0, 26);
                $class = 'list';
                $url = self::BASE_URL.$element;
                $suffix = substr($listname, 26);
            } else {
                $element = $username;
                $class = 'user';
                $url = self::BASE_URL.$element;
                $suffix = '';
            }

            return $before.$this->wrapUrl($url, $class, $at.$element).$suffix.$after;
        }, $output) ?? $output;

        return $output;
    }

    /**
     * Generate a link tag for URL
     */
    protected function wrapUrl(string $url, ?string $class, string $content): string
    {
        $output = '<a';

        if ($class !== null) {
            $output .= ' class="'.$class.'"';
        }

        $output .= ' href="'.$url.'"';
        $output .= ' rel="external nofollow"';
        $output .= ' target="_blank"';
        $output .= '>'.$content.'</a>';

        return $output;
    }
}
