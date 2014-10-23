<?php
namespace Ice\MailerBundle\Attachment;

use Ice\MailerBundle\Attachment\Attachment;

interface FileRepository
{

    /**
     * @param Attachment $attachment
     * @return mixed
     *
     * @throws AttachmentException
     */
    public function getFile(Attachment $attachment);
}
