<?php

namespace Maks3w\EmailAttachments\Tests;

use Maks3w\EmailAttachments\MailMessage;
use PHPUnit_Framework_TestCase as TestCase;

class MailMessageTest extends TestCase
{
    public function testWithoutAttachments()
    {
        $message = $this->buildMessageFromFilename(__DIR__ . '/_files/mailWithoutAttachment.eml');

        $this->assertFalse($message->hasAttachments());
    }

    /**
     * @dataProvider messageProvider
     *
     * @param string $mailFile Mail message to test
     * @param string[] $expected Expected filename => content.
     */
    public function testGetAttachmentFilename($mailFile, array $expected)
    {
        $message = $this->buildMessageFromFilename($mailFile);

        $this->assertTrue($message->hasAttachments());
        $result = array_map(
            function ($value) {
                return base64_decode($value);
            },
            $message->getAttachments()
        );
        $this->assertEquals($expected, $result);
    }

    public function testGetFrom()
    {
        $message = $this->buildMessageFromFilename(__DIR__ . '/_files/mailWithoutAttachment.eml');

        $this->assertEquals('sender@example.com', $message->getFrom());
    }

    public function messageProvider()
    {
        $content = file_get_contents(__DIR__ . '/_files/test.pdf');
        $content2 = file_get_contents(__DIR__ . '/_files/test2.pdf');

        // [Mail file, attachment filename, attachment content]
        return [
            [__DIR__ . '/_files/mailWithAttachment.eml', ['test.pdf' => $content]],
            [__DIR__ . '/_files/mailWithAttachmentAndPathInFilename.eml', ['/path/with/slashes/test.pdf' => $content]],
            [__DIR__ . '/_files/mailWith2Attachments.eml', ['test.pdf' => $content, 'test2.pdf' => $content2,]],
        ];
    }

    /**
     * @param string $filename
     *
     * @return MailMessage
     */
    protected function buildMessageFromFilename($filename)
    {
        $message = new MailMessage(['file' => $filename]);

        return $message;
    }
}
