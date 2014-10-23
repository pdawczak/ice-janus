<?php
namespace Ice\MailerBundle\Attachment;


/**
 * Class Attachment
 * @package Ice\MailerBundle\Attachment
 */
class Attachment
{
    /**
     * @var AttachmentKey
     */
    private $key;

    /**
     * @var string
     */
    private $origin;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @param AttachmentKey $key
     * @param $fileName
     * @param $origin
     */
    public function __construct(AttachmentKey $key, $fileName, $origin)
    {
        $this->key = $key;
        $this->fileName = $fileName;
        $this->origin = $origin;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->fileName;
    }

    /**
     * @return AttachmentKey
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

}
