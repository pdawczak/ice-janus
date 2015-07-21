<?php
namespace Ice\MailerBundle\PreCompiler;

use Ice\MailerBundle\Event\PreCompileEvent;
use Ice\MailerBundle\Template\BookingConfirmation;
use Ice\MailerBundle\Template\CourseApplication;
use Ice\MailerBundle\Template\DisabilityAndSupportNeeds;
use Ice\VeritasClientBundle\Service\VeritasClient;

/**
 * Class CourseInformation
 * @package Ice\MailerBundle\Precompiler
 */
class CourseInformation
{
    /**
     * @var VeritasClient
     */
    private $veritasClient;

    /**
     * @param \Ice\VeritasClientBundle\Service\VeritasClient $veritasClient
     * @return CourseInformation
     */
    public function setVeritasClient($veritasClient)
    {
        $this->veritasClient = $veritasClient;
        return $this;
    }

    /**
     * @return \Ice\VeritasClientBundle\Service\VeritasClient
     */
    public function getVeritasClient()
    {
        return $this->veritasClient;
    }

    /**
     * Inject course information into the template
     *
     * @param PreCompileEvent $event
     */
    public function onPreCompile(PreCompileEvent $event)
    {
        $template = $event->getTemplate();
        if ($template instanceof BookingConfirmation ||
            $template instanceof CourseApplication ||
            $template instanceof DisabilityAndSupportNeeds
        ) {
            $vars = $template->getVars();
            $courseId = $vars['academicInformation']['courseId'];
            $course = $this->getVeritasClient()->getCourse($courseId);
            $vars['course'] = [
                'webUrl' => 'https://www.ice.cam.ac.uk/component/courses/'.$course->getId(),
                'title' => $course->getTitle(),
                'startDate' => $course->getStartDate(),
                'code' => $course->getCode(),
                'endDate' => $course->getEndDate(),
                'isMst' => $course->getLevel()->isMst(),
                'isOnline' => $course->getProgramme()->isOnline(),
                'isCertsAndDips' => $course->getProgramme()->getId() == 25,
                'attributes' => $course->getAttributes()
            ];
            $template->setVars($vars);
        }
    }
}
