<?php

namespace Maks3w\EmailAttachments;

use Zend\Mail\Storage;
use Zend\Mail\Storage\Message;

/**
 * This class gets mail attachments from an IMAP mailbox.
 */
class EmailAttachments
{
    /**
     * Connection options.
     *
     * @var EmailStorage
     */
    protected $storage;

    public function __construct(EmailStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param string $srcFolder
     *
     * @return int
     */
    public function countMessages($srcFolder = 'INBOX')
    {
        $storage = $this->storage;
        $storage->selectFolder($srcFolder);

        return $storage->countMessages();
    }

    /**
     * @param string $srcFolder
     *
     * @return int
     */
    public function countUnseenMessages($srcFolder = 'INBOX')
    {
        $storage = $this->storage;
        $storage->selectFolder($srcFolder);

        $counter = 0;
        for ($i = $storage->countMessages(); $i > 0; --$i) {
            $message = $storage->getMessage($i);
            if (!($this->isSeen($message))) {
                ++$counter;
            }
        }

        return $counter;
    }

    /**
     * Look for mail with attachments on the desired folder (by default INBOX) and invoke the callback function.
     *
     * Callback function signature should be `function(MailMessage $message, EmailStorage $storage, int $messageIndex)`
     *
     * Note: This method filter for recent/unseen/unread messages.
     *
     * @param callable $callback
     * @param string $srcFolder IMAP Source folder.
     */
    public function forEachUnseenMessageWithAttachments(callable $callback, $srcFolder = 'INBOX')
    {
        $storage = $this->storage;
        $storage->selectFolder($srcFolder);

        for ($i = $storage->countMessages(); $i > 0; --$i) {
            $message = $storage->getMessage($i);
            if ($this->isSeen($message)) {
                continue;
            }

            if ($message->hasAttachments()) {
                $storage->noop();
                $callback($message, $storage, $i);
            }
        }
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    protected function isSeen(Message $message)
    {
        return ($message->hasFlag(Storage::FLAG_SEEN));
    }
}
