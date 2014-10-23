<?php
namespace Ice\MailerBundle\Attachment;

class FileCDNRepository implements FileRepository
{

    private $baseUrl;

    function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @param Attachment $attachment
     * @return Object
     *
     * @throws AttachmentException
     */
    public function getFile(Attachment $attachment)
    {
        $location = $this->baseUrl . $attachment->getKey()->getValue();

        if (!$this->fileExists($location))
            throw new AttachmentException(sprintf('Attachment file %s not found', $location));

        $swiftAttachment = \Swift_Attachment::newInstance(
            file_get_contents($location)
        );

        $swiftAttachment->setFilename($attachment->getFilename());

        return $swiftAttachment;
    }

    public function fileExists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code == 200);
    }


}
