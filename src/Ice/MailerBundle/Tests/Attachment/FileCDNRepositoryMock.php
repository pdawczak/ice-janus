<?php

namespace Ice\MailerBundle\Tests\Attachment;


use Ice\MailerBundle\Attachment\Attachment;
use Ice\MailerBundle\Attachment\AttachmentException;
use Ice\MailerBundle\Attachment\FileRepository;


class FileCDNRepositoryMock implements FileRepository
{
    function __construct()
    {
    }


    /**
     * @param Attachment $attachment
     * @return \Swift_Attachment
     *
     */
    public function getFile(Attachment $attachment)
    {

        $swiftAttachment = \Swift_Attachment::newInstance(
            'Born to be wild!',
            'x.pdf'
        );

        $swiftAttachment->setFilename($attachment->getFilename());

        return $swiftAttachment;
    }

} 