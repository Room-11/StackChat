<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

class Room
{
    private $identifier;

    public function __construct(Identifier $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }

    public function __debugInfo()
    {
        return [
            'identifier' => $this->identifier,
        ];
    }
}
