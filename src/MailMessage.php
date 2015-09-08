<?php

namespace Maks3w\EmailAttachments;

use Zend\Mail\Header\From;
use Zend\Mail\Storage\Exception\InvalidArgumentException;
use Zend\Mail\Storage\Message;

/**
 * Facade of `Zend\Mail\Storage\Message` with methods for retrieve attachments easily.
 */
class MailMessage extends Message
{
    /**
     * @var string[] filename => content.
     */
    protected $attachments = [];

    public function __construct(array $params)
    {
        parent::__construct($params);

        if (!($this->isMultipart())) {
            return;
        }

        for ($counter = 1; $counter <= $this->countParts(); ++$counter) {
            $part = $this->getPart($counter);
            $partHeaders = $part->getHeaders();
            if (!$partHeaders->has('content-disposition')) {
                continue;
            }

            $type = $part->getHeader('content-disposition');
            $fileString = explode(';', $type->getFieldValue());
            $type = $fileString[0];

            if (empty($type) || $type != 'attachment') {
                continue;
            }
            $this->attachments[$this->getFileName($fileString[1])] = $part->getContent();
        }
    }

    /**
     * @return bool
     */
    public function hasAttachments()
    {
        return !empty($this->attachments);
    }

    /**
     * @return string[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param string $fileString
     *
     * @return string
     */
    protected function getFileName($fileString)
    {
        $name = explode('=', $fileString);
        $filename = $name[1]; // File name as see by the mail client.
        // Workaround for extra double quotes (") in the file name when sent from Yahoo Mail.
        $filename = str_replace('"', ' ', $filename);
        $filename = trim(str_replace(['/', '\\', '"', ':', '*', '?', '<>', '|', "\t"], '_', $filename));

        return $filename;
    }

    /**
     * From an MailMessage object, this method returns the sender's email address.
     *
     * @return string from mail address
     *
     * @throws InvalidArgumentException If From header does not exists.
     */
    public function getFrom()
    {
        /** @var From $from */
        $from = $this->getHeader('from');

        return $from->getAddressList()->rewind()->getEmail();
    }
}
