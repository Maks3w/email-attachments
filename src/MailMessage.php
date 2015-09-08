<?php

namespace Maks3w\EmailAttachments;

use Maks3w\EmailAttachments\Header\ContentDisposition;
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
            $partHeaders->getPluginClassLoader()->registerPlugin('contentdisposition', ContentDisposition::class);
            if (!$partHeaders->has('content-disposition')) {
                continue;
            }

            /** @var ContentDisposition $header */
            $header = $part->getHeader('content-disposition');
            $type = $header->getDisposition();

            if (empty($type) || $type != 'attachment') {
                continue;
            }
            $this->attachments[$header->getParameter('filename')] = $part->getContent();
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
