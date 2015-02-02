<?php
namespace Ice\MailerBundle\Attachment;

class AttachmentKeyCDN extends AttachmentKey
{
    private $hashKey;

    /**
     * @param $hashKey string
     */
    function __construct( $hashKey)
    {
        $this->hashKey = $hashKey;
    }

    public function getValue()
    {
        return $this->hashKey;
    }


}
