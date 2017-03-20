<?php declare(strict_types=1);

namespace Room11\StackChat\Event;

use Room11\StackChat\Event\Traits\RoomSource;
use Room11\StackChat\Event\Traits\UserSource;
use Room11\StackChat\Room\Room as ChatRoom;
use function Room11\DOMUtils\domdocument_load_html;

abstract class MessageEvent extends BaseEvent implements UserSourcedEvent, RoomSourcedEvent
{
    use RoomSource, UserSource;

    private $messageId;
    private $rawMessageContent;
    private $messageContent;
    private $parentId;
    private $showParent;

    public function __construct(array $data, ChatRoom $room)
    {
        parent::__construct($data, $room->getHost());

        $this->room = $room;

        $this->userId = (int)$data['user_id'];
        $this->userName = (string)$data['user_name'];

        $this->messageId = (int)$data['message_id'];
        $this->rawMessageContent = $data['content'] ?? '';

        $this->parentId = (int)($data['parent_id'] ?? -1);
        $this->showParent = (bool)($data['show_parent'] ?? false);
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }

    public function getRawMessageContent(): string
    {
        return $this->rawMessageContent;
    }

    public function getMessageContent(): \DOMDocument
    {
        if (!isset($this->messageContent)) {
            $this->messageContent = domdocument_load_html(
                "<p>{$this->rawMessageContent}</p>",
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
        }

        return $this->messageContent;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function shouldShowParent(): bool
    {
        return $this->showParent;
    }
}
