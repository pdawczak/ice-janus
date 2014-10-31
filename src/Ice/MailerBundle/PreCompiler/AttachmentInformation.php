<?php

namespace Ice\MailerBundle\PreCompiler;

use Ice\MailerBundle\Attachment\Attachment;
use Ice\MailerBundle\Attachment\AttachmentException;
use Ice\MailerBundle\Attachment\AttachmentFactory;
use Ice\MailerBundle\Attachment\AttachmentKeyCDN;
use Ice\MailerBundle\Event\PreCompileEvent;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class AttachmentInformation
 * @package Ice\MailerBundle\PreCompiler
 */
class AttachmentInformation
{

    /**
     *
     * @param PreCompileEvent $event
     * @throws AttachmentException
     */
    public function onPreCompile(PreCompileEvent $event)
    {
        $vars = $event->getTemplate()->getVars();
        /** @var \Ice\MailerBundle\Entity\Mail $mail */
        $mail = $event->getMail();

        if (isset($vars['attachments']) && count($vars['attachments'])) {
            $attachmentsInstances = [];
            foreach ($vars['attachments'] as $i => $attach) {

                $attachment = AttachmentFactory::build($attach);

                $attachmentsInstances[] = $attachment;
            }

            $mail->setAttachments(new ArrayCollection($attachmentsInstances));
        }
    }
} 