<?php
namespace Ice\MailerBundle\Template;

class TimelyPaymentNotification extends DefaultTemplate
{
    /**
     * @return array
     */
    public function getFrom()
    {
        return [
            'ice.admissions@ice.cam.ac.uk' => 'ICE Admissions'
        ];
    }

    /**
     * Returns an array in the form:
     *
     * array(
     *      'john@doe.com' => 'John Doe'
     * )
     *
     * @return array
     */
    public function getBCC()
    {
        return [
//            'ice.admissions@ice.cam.ac.uk' => 'ICE Admissions'
        ];
    }
}
