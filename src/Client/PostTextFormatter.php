<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

class PostTextFormatter implements TextFormatter
{
    private const ENCODING = 'UTF-8';

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
        return preg_replace('#@((?:\p{L}|\p{N})(?:\p{L}|\p{N}|\.|-|_|\')*)#u', "@\u{2060}$1", $text);
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

        return mb_substr($text, 0, $pos, self::ENCODING) . Chars::ELLIPSIS;
    }
}
