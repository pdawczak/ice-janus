<?php

namespace Ice\MailerBundle\Attachment;


class AttachmentFactory {

    /**
     * @param array $params
     * @return Attachment
     */
    public static function build($params = array())
    {
        if (!$params['origin']) throw new AttachmentException('Invalid attachment origin');
        $attachmentType = strtolower($params['origin']);

        switch ($attachmentType) {

            case 'cdn':
                if (! ($params['key']['hash'] || $params['filename']) )
                    throw new AttachmentException('Invalid attachment parameters');

                $key = new AttachmentKeyCDN($params['key']['hash']);

                return new Attachment(
                    $key,
                    $params['filename'],
                    $attachmentType
                );
            default:
                throw new AttachmentException('Invalid attachment origin');
        }
    }

} 