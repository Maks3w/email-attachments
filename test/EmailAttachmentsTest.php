<?php

namespace Maks3w\EmailAttachments\Tests;

use Maks3w\EmailAttachments\EmailAttachments;
use Maks3w\EmailAttachments\EmailStorage;
use Maks3w\EmailAttachments\MailMessage;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mail\Storage;
use Zend\Mail\Storage\Imap;

class EmailAttachmentsTest extends TestCase
{
    /** @var EmailAttachments */
    protected $emailAttachments;

    protected function setUp()
    {
        $this->emailAttachments = new EmailAttachments($this->createEmailStorageMockUp());
    }

    public function testForEachUnseenMessageWithAttachments()
    {
        $messages = [];
        $callback = function (MailMessage $message, Imap $storage, $msgId) use (&$messages) {
            $messages[] = $message;

            try {
                $this->assertInternalType('integer', $msgId, '$msgId must be integer. received: ' . json_encode($msgId));
                $this->assertFalse($message->hasFlag(Storage::FLAG_SEEN), 'Expect message ' . $msgId . ' to be recent');
                $this->assertTrue($message->hasAttachments(), 'Expect message ' . $msgId . ' to have attachments');
            } finally {
                $storage->setFlags($msgId, [Storage::FLAG_RECENT]);
            }
        };

        $this->emailAttachments->forEachUnseenMessageWithAttachments($callback, getenv('SOURCE_FOLDER'));

        $this->assertCount(2, $messages);
    }

    public function testCountMessages()
    {
        $totalMessages = $this->emailAttachments->countMessages(getenv('SOURCE_FOLDER'));

        $this->assertEquals(5, $totalMessages);
    }

    public function testCountUnseenMessages()
    {
        $totalMessages = $this->emailAttachments->countUnseenMessages(getenv('SOURCE_FOLDER'));

        $this->assertEquals(3, $totalMessages);
    }

    /**
     * @return EmailStorage|MockObject
     */
    protected function createEmailStorageMockUp()
    {
        $contentWithAttachment = __DIR__ . '/_files/mailWithAttachment.eml';
        $contentWithoutAttachment = __DIR__ . '/_files/mailWithoutAttachment.eml';

        $messagesMap = [
            '5. Attachments: T, Seen: F' => [5, $this->createMessage($contentWithAttachment, [])],
            '4. Attachments: F, Seen: T' => [4, $this->createMessage($contentWithoutAttachment, [Storage::FLAG_SEEN])],
            '3. Attachments: T, Seen: F' => [3, $this->createMessage($contentWithAttachment, [])],
            '2. Attachments: T, Seen: T' => [2, $this->createMessage($contentWithAttachment, [Storage::FLAG_SEEN])],
            '1. Attachments: F, Seen: F' => [1, $this->createMessage($contentWithoutAttachment, [])],
        ];

        /** @var EmailStorage|MockObject $storage */
        $storage = $this->getMockBuilder(EmailStorage::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $storage->method('countMessages')
            ->willReturn(5)
        ;

        $storage->method('getMessage')
            ->willReturnMap($messagesMap)
        ;

        return $storage;
    }

    /**
     * @param string $filePathToContent
     * @param array $flags
     *
     * @return MailMessage
     */
    protected function createMessage($filePathToContent, $flags = [])
    {
        $params = [
            'file' => $filePathToContent,
            'flags' => $flags,
        ];
        $message = new MailMessage($params);

        return $message;
    }
}
