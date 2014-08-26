<?php

namespace Ice\ExternalUserBundle\Tests\Service;

use Ice\ExternalUserBundle\Service\UsernameGenerator;

use Symfony\Component\Validator\Validation;

class UsernameGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UsernameGenerator
     */
    protected $generator;

    /**
     * @var \Symfony\Component\Validator\Validator
     */
    protected $validator;

    protected function setUp()
    {
        $registry = $this->getMock('Symfony\\Bridge\\Doctrine\\RegistryInterface');
        $em = $this->getMock('Doctrine\\ORM\\EntityManager', array(), array(), '', false);
        $repository = $this->getMock('Ice\\ExternalUserBundle\\Entity\\UsernameRepository', array(), array(), '', false);
        $this->generator = new UsernameGenerator($registry, $em, $repository);

        $this->validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
    }

    public function testGetUsernameWithNoInitialsFailsValidation()
    {
        $username = $this->generator->getUsernameForInitials('');
        $errors = $this->validator->validate($username);
        $this->assertContains('Initials cannot be blank', (string)$errors, $errors);
    }

    public function testGetUsernameWithNonLetterCharactersFailsValidation()
    {
        $username = $this->generator->getUsernameForInitials('a*ssa222');
        $errors = $this->validator->validate($username);
        $this->assertContains('Initials can only contain letters', (string)$errors, $errors);
    }

    public function testGetUsernameWithAccentedLettersSucceeds()
    {
        $username = $this->generator->getUsernameForInitials('éíă');
        $errors = $this->validator->validate($username);
        $this->assertNotContains('Initials can only contain letters', (string)$errors, $errors);
    }
}
