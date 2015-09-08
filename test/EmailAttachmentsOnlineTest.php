<?php

namespace Maks3w\EmailAttachments\Tests;

use Maks3w\EmailAttachments\EmailAttachments;
use Maks3w\EmailAttachments\EmailStorage;
use Maks3w\EmailAttachments\MailMessage;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mail\Storage;

class EmailAttachmentsOnlineTest extends TestCase
{
    /** @var EmailAttachments */
    protected $emailAttachments;

    protected function setUp()
    {
        $expectedTestConfig = [
            'user' => 'IMAP_USER',
            'password' => 'IMAP_PASS',
            'host' => 'IMAP_HOST',
            'port' => 'IMAP_PORT',
            'ssl' => 'IMAP_SSL',
        ];

        $options = [];

        foreach ($expectedTestConfig as $option => $constant) {
            if (!getenv($constant)) {
                $this->markTestSkipped($constant . ' constant must be defined for execute this test.');
            }

            $options[$option] = getenv($constant);
        }

        $this->emailAttachments = new EmailAttachments(new EmailStorage($options));
    }

    public function testForEachUnseenMessageWithAttachments()
    {
        $expectedCountMessages = 1;
        $messages = [];
        $callback = function (MailMessage $message, EmailStorage $storage, $msgId) use (&$messages) {
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

        $this->assertCount($expectedCountMessages, $messages);
    }

    public function testCountMessages()
    {
        $totalMessages = $this->emailAttachments->countMessages(getenv('SOURCE_FOLDER'));

        $this->assertEquals($totalMessages, 3);
    }

    public function testCountUnseenMessages()
    {
        $totalMessages = $this->emailAttachments->countUnseenMessages(getenv('SOURCE_FOLDER'));

        $this->assertEquals($totalMessages, 0);
    }
}
