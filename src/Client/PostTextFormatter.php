<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

class PostTextFormatter implements TextFormatter
{
    private const ENCODING = 'UTF-8';
    private const PING_MATCH_EXPR = '#@((?:\p{L}|\p{N})(?:\p{L}|\p{N}|\.|-|_|\')*)#u';
    private const PING_REPLACEMENT = '@' . Utf8Chars::ZWNJ . '$1';
    private const ESCAPE_EXPR = '/\\\\(.)/';
    private const ESCAPE_SEQUENCES = ['n' => "\n"];

    /**
     * {@inheritdoc}
     */
    public function checkAndNormalizeEncoding(string $text): string
    {
        if (!mb_check_encoding($text, self::ENCODING)) {
            throw new TextFormatException('Message text encoding invalid');
        }

        $text = \Normalizer::normalize(rtrim($text), \Normalizer::FORM_C);

        if ($text === false) {
            throw new TextFormatException('Failed to normalize message text');
        }

        return $text;
    }

    /**
     * {@inheritdoc}
     */
    public function stripPingsFromText(string $text): string
    {
        return preg_replace(self::PING_MATCH_EXPR, self::PING_REPLACEMENT, $text);
    }

    /**
     * {@inheritdoc}
     */
    public function truncateText(string $text, int $length = self::TRUNCATION_LIMIT): string
    {
        if (mb_strlen($text, self::ENCODING) <= $length) {
            return $text;
        }

        $text = mb_substr($text, 0, $length, self::ENCODING);

        for ($pos = $length - 1; $pos >= 0; $pos--) {
            if (preg_match('#^\s$#u', mb_substr($text, $pos, 1, self::ENCODING))) {
                break;
            }
        }

        if ($pos === 0) {
            $pos = $length - 1;
        }

        return mb_substr($text, 0, $pos, self::ENCODING) . Utf8Chars::ELLIPSIS;
    }

    public function interpolateEscapeSequences(string $string): string
    {
        return \preg_replace_callback(self::ESCAPE_EXPR, function($match) {
            return \array_key_exists($match[1], self::ESCAPE_SEQUENCES)
                ? self::ESCAPE_SEQUENCES[$match[1]]
                : $match[0];
        }, $string);
    }
}
