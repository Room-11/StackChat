<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

interface TextFormatter
{
    /**
     * The default message length limit for a single line message in the Stack Exchange chat network
     */
    const TRUNCATION_LIMIT = 500;

    /**
     * Validate that a string is correctly encoded and normalize the code points
     *
     * @param string $text
     * @return string
     * @throws TextFormatException
     */
    function checkAndNormalizeEncoding(string $text): string;

    /**
     * Insert a zero-width non-joiner into user pings so users will not receive a notification
     *
     * @param string $text
     * @return string
     */
    function stripPingsFromText(string $text): string;

    /**
     * Truncate a string to 500 characters and append an ellipsis
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    function truncateText(string $text, int $length = self::TRUNCATION_LIMIT): string;
}
