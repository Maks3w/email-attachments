<?php

namespace Maks3w\EmailAttachments;

use Zend\Mail\Storage\Imap;

/**
 * @method MailMessage getMessage($id = null)
 */
class EmailStorage extends Imap
{
    protected $messageClass = MailMessage::class;
}
