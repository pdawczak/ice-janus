<?php

namespace Ice\MailerBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Ice\MailerBundle\Attachment\Attachment;
use Ice\MailerBundle\Attachment\FileRepository;
use Ice\MailerBundle\Entity\Mail;
use Ice\MailerBundle\Entity\MailRequest;
use Ice\MailerBundle\Event\MailerEvents;
use Ice\MailerBundle\Event\RequestEvent;
use Ice\MailerBundle\Event\PreCompileEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Ice\MailerBundle\Template\Manager;
use Swift_Mailer;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class MailerService implements EventSubscriberInterface
{
    /**
     * @var Registry;
     */
    private $doctrine;

    /**
     * @var Manager;
     */
    private $templateManager;

    /**
     * @var Swift_Mailer
     */
    private $swiftMailer;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var FileRepository
     */
    private $fileRepository;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     * @return MailerService
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
        return $this;
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    public function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @param \Swift_Mailer $swiftMailer
     * @return MailerService
     */
    public function setSwiftMailer($swiftMailer)
    {
        $this->swiftMailer = $swiftMailer;
        return $this;
    }

    /**
     * @return \Swift_Mailer
     */
    public function getSwiftMailer()
    {
        return $this->swiftMailer;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            MailerEvents::POST_CREATE_REQUEST => 'onPostCreateRequest'
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onPostCreateRequest(RequestEvent $event)
    {
        try {
            $mailRequest = $event->getRequest();

            $this->compileMailRequest($mailRequest);

            foreach ($mailRequest->getMails() as $mail) {
                $this->sendCompiledMail($mail);
            }

            $this->getDoctrine()->getManager()->persist($mailRequest);
            $this->getDoctrine()->getManager()->flush();
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
        }
    }

    /**
     * Attempt to compile each Mail in a MailRequest and set the compiled datetime
     *
     * @param MailRequest $mailRequest
     */
    public function compileMailRequest(MailRequest $mailRequest)
    {
        $manager = $this->getTemplateManager();

        /** @var Mail $mail */
        foreach ($mailRequest->getMails() as $mail) {

            $template = $manager->getTemplateByMail($mail);

            $this->getEventDispatcher()->dispatch(
                MailerEvents::PRE_COMPILE_MAIL,
                (new PreCompileEvent())
                    ->setTemplate($template)
                    ->setMail($mail)
            );

            $mail
                ->setCompiledBodyPlain($template->getBodyPlain())
                ->setCompiledBodyHtml($template->getBodyHtml())
                ->setCompiledSubject($template->getSubject())
                ->setSendersByArray($template->getFrom())
                ->setToRecipientsByArray($template->getTo())
                ->setCcRecipientsByArray($template->getCC())
                ->setBccRecipientsByArray($template->getBCC())
                ->setCompiled(new \DateTime())
            ;
        }
    }

    /**
     * @param Mail $mail
     */
    public function sendCompiledMail(Mail $mail)
    {
        if($mail->getRecipient()->getEmail()) {
            /** @var \Swift_Mime_Message $message */
            $message = \Swift_Message::newInstance()
                ->setSubject($mail->getCompiledSubject())
                ->setFrom($mail->getSendersByArray())
                ->setCC($mail->getCcRecipientsByArray())
                ->setBCC($mail->getBccRecipientsByArray())
                ->setTo($mail->getToRecipientsByArray())
                ->setBody(
                    $mail->getCompiledBodyPlain()
                )
                ->setBody(
                    $mail->getCompiledBodyHtml(),
                    'text/html'
                )
            ;

            $message = $this->attachFiles($message, $mail->getAttachments());

            if ($this->getSwiftMailer()->send($message)) {
               $mail->setSent(new \DateTime());
            }
            else {

                $this->logger->warn('Message not sent to '.print_r($mail->getToRecipientsByArray(), true));
            }
        }
    }

    /**
     * @param \Ice\MailerBundle\Template\Manager $templateManager
     * @return MailerService
     */
    public function setTemplateManager($templateManager)
    {
        $this->templateManager = $templateManager;
        return $this;
    }

    /**
     * @return \Ice\MailerBundle\Template\Manager
     */
    public function getTemplateManager()
    {
        return $this->templateManager;
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher
     * @return MailerService
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @return FileRepository
     */
    public function getFileRepository()
    {
        return $this->fileRepository;
    }

    /**
     * @param FileRepository $fileRepository
     */
    public function setFileRepository($fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param ArrayCollection $attachments
     */
    private function attachFiles($message, $attachments)
    {
        if ($attachments->count()) {
            /**
             * @var int $i
             * @var Attachment $attachment
             */
            foreach ($attachments as $i => $attachment) {

                $file = $this->getFileRepository()->getFile($attachment);

                $message->attach(
                    $file,
                    $attachment->getFilename()
                );
            }
        }

        return $message;
    }
}