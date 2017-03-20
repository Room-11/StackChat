<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

class Room
{
    private $identifier;
    private $permanent;

    public function __construct(
        Identifier $identifier,
        bool $permanent
    ) {
        $this->identifier = $identifier;
        $this->permanent = $permanent;
    }

    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }

    public function isPermanent(): bool
    {
        return $this->permanent;
    }

    public function __debugInfo()
    {
        return [
            'identifier' => $this->identifier,
            'isPermanent' => $this->permanent,
        ];
    }
}
