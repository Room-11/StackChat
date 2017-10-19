<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: chris.wright
 * Date: 07/10/2016
 * Time: 19:55
 */

namespace Room11\StackChat\Entities;

use function Room11\DOMUtils\xpath_html_class;

class MainSiteUser
{
    // todo: write this whole class :-P

    /**
     * @var string
     */
    private $twitterHandle;
    private $githubUsername;

    public static function createFromDOMDocument(\DOMDocument $doc): MainSiteUser // very todo: remove horrible static ctor
    {
        $xpath = new \DOMXPath($doc);
        $twitterHandle = ($link = ltrim(self::getUserLink($xpath, 'iconTwitter'), '@')) === '' ? null : $link;

        return new MainSiteUser(
            $twitterHandle,
            self::getUserLink($xpath, 'iconGitHub')
        );
    }

    public function __construct(string $twitterHandle = null, string $githubUsername = null)
    {
        $this->twitterHandle = $twitterHandle;
        $this->githubUsername = $githubUsername;
    }

    /**
     * @return string
     */
    public function getTwitterHandle()
    {
        return $this->twitterHandle;
    }

    public function getGithubUsername()
    {
        return $this->githubUsername;
    }

    private static function getUserLink($xpath, string $class): ?string
    {
        $link = $xpath->query("//li[svg[" . xpath_html_class($class) . "]]/a");

        return $link->length > 0
            ? trim($link->item(0)->textContent)
            : null;
    }
}
