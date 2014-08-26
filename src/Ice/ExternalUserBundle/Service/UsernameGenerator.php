<?php

namespace Ice\ExternalUserBundle\Service;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Ice\ExternalUserBundle\Entity\UsernameRepository,
    Ice\ExternalUserBundle\Entity\Username;

class UsernameGenerator
{
    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Entity\UsernameRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $usernameFormat = "%s";

    /**
     * @var integer
     */
    private $sequenceStart = 1;

    public function __construct(RegistryInterface $registry, EntityManager $em, UsernameRepository $repository)
    {
        $this->registry = $registry;
        $this->em = $em;
        $this->repository = $repository;
    }

    public function setUsernameFormat($usernameFormat)
    {
        $this->usernameFormat = $usernameFormat;
        return $this;
    }

    public function getUsernameFormat()
    {
        return $this->usernameFormat;
    }

    public function setSequenceStart($sequenceStart)
    {
        $this->sequenceStart = $sequenceStart;
        return $this;
    }

    public function getSequenceStart()
    {
        return $this->sequenceStart;
    }

    public function getUsernameForInitials($initials)
    {
        $username = new Username($this->getUsernameFormat());
        $username->setInitials($initials);

        // There is potential for a race condition here, whereby two clients could request a
        // username for the same initials before the first clients request had been persisted, causing
        // an integrity constraint violation.
        //
        // This part of the controller keeps trying to persist a new username until it is successful.
        $usernameSuccessfullyGenerated = false;
        do {
            $result = $this->repository->findCurrentSequenceForInitials($initials);
            $sequence = $result[1];

            if ($sequence) {
                $username->setSequence($sequence + 1);
            } else {
                $username->setSequence($this->getSequenceStart());
            }

            $this->em->persist($username);

            try {
                $this->em->flush();
                $usernameSuccessfullyGenerated = true;
            } catch(\Doctrine\DBAL\DBALException $e) {
                // Get the previous exception, which will be a PDOException if there was an integrity constraint
                // violation
                $previousException = $e->getPrevious();

                // Integrity constraint violation as a result of a race condition.
                // The loop will continue to retry until it is successful
                if ("PDOException" === get_class($previousException) && 23000 == $previousException->getCode()) {
                    // EntityManager will closed due to the exception.
                    // Reset so it can be used during the next iteration of the loop.
                    $this->registry->resetManager();
                    $em = $this->registry->getManager();
                }

                throw $e;
            }
        } while ($usernameSuccessfullyGenerated !== true);

        return $username;
    }
}