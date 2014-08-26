<?php

namespace Ice\Features\Context;

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\EntityManagerInterface;


class UserFixturesContext extends BehatContext implements KernelAwareInterface
{
    private $kernel;

    /**
     * @Given /^there are users$/
     */
    public function thereAreUsers(TableNode $courses)
    {
        $fixtures = array(
            '\Ice\ExternalUserBundle\Entity\User' => array(),
        );

        foreach ($courses->getHash() as $row) {
            //$id = $this->getRepositories()->getUnits()->getNextId();
            $fixtures['\Ice\ExternalUserBundle\Entity\User']['user:' . $row['username']] = array(
                'username' => $row['username'],
                'usernameCanonical' => $row['username'],
                'plainPassword' => isset($row['password']) ? $row['password'] : 'password',
                'enabled' => isset($row['enabled']) ? $row['enabled'] : true,
                'title' => isset($row['title']) ? $row['title'] : 'Mr',
                'firstNames' => isset($row['first_names']) ? $row['first_names'] : 'Rob',
                'lastNames' => isset($row['last_names']) ? $row['last_names'] : 'Hogan',
                'email' => isset($row['email']) ? $row['email'] : null
            );

            preg_match('#(\w+)(\d+)#', $row['username'], $usernameParts);
            $fixtures['\Ice\ExternalUserBundle\Entity\Username']['username:' . $row['username']] = array(
                'generatedUsername' => $row['username'],
                'initials' => $usernameParts[1],
                'sequence' => intval($usernameParts[2]),
                'generatedAt' => new \DateTime(),
                '__construct' => ['%s']
            );
        }
        $this->getMainContext()->getSubcontext('fixtures')->loadFixtures($fixtures);
    }

    /**
     * Sets Kernel instance.
     *
     * @param KernelInterface $kernel HttpKernel instance
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->kernel->getContainer()->get('doctrine.orm.default_entity_manager');
    }
}
