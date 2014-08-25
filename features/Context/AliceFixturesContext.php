<?php

namespace Ice\Features\Context;

use Behat\Behat\Context\BehatContext;

use Behat\Behat\Event\SuiteEvent;
use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Behat\Event\ScenarioEvent;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareInterface;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpKernel\KernelInterface;
use Nelmio\Alice\LoaderInterface;
use Nelmio\Alice\ORMInterface;
use Nelmio\Alice\Loader\Base as Loader;
use Nelmio\Alice\ORM\Doctrine as Persister;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManager;


class AliceFixturesContext extends BehatContext implements FixturesContext, KernelAwareInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var ORMInterface
     */
    private $persister;

    public function __construct()
    {
        $this->useContext('userFixtures', new UserFixturesContext());
        $this->useContext('courseFixtures', new VeritasClientFixturesContext());
        $this->useContext('bookingFixtures', new MinervaClientFixturesContext());
    }

    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @BeforeScenario
     */
    public function loadPersister()
    {
        $this->loader = new Loader();
        $this->persister = new Persister($this->getEntityManager());
        $this->loader->setORM($this->persister);
    }

    public function getEntityManager()
    {
        return $this->kernel->getContainer()->get('doctrine')->getManager();
    }

    public function loadFixtures(array $fixtures)
    {
        $objects = $this->loader->load($fixtures);
        $this->persister->persist($objects);
        $this->getEntityManager()->clear();
    }

    /**
     * @BeforeScenario
     */
    public function buildSchema()
    {
        foreach ($this->getEntityManagers() as $name => $entityManager) {
            $connection = $entityManager->getConnection();
            $dbPlatform = $connection->getDatabasePlatform();

            if ($dbPlatform->getName() == 'sqlite') {
                $this->prepareEmptySqliteSchema($name, $entityManager, $connection, $dbPlatform);
                continue;
            }

            $metadata = $this->getMetadata($entityManager);
            if (!empty($metadata)) {
                $tool = new SchemaTool($entityManager);
                $tool->dropSchema($metadata);
                $tool->createSchema($metadata);
            }
        }
    }

    /**
     * @param string $entityManagerName
     * @param EntityManager $entityManager
     * @param Connection $connection
     * @param SqlitePlatform $dbPlatform
     */
    private function prepareEmptySqliteSchema($entityManagerName, $entityManager, $connection, SqlitePlatform $dbPlatform)
    {
        static $schemaCreated = [];
        $metadata = $this->getMetadata($entityManager);

        if (!isset($schemaCreated[$entityManagerName])) {
            $tool = new SchemaTool($entityManager);
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
            $schemaCreated[$entityManagerName] = true;
            return;
        }

        /*
         * If the schema has already been created it's much faster to delete all of the data than to drop and recreate
         * the schema.
         */
        foreach ($metadata as $classMetaData) {
            try {
                $connection->beginTransaction();
                $connection->query('PRAGMA foreign_keys = OFF');
                $connection->executeUpdate('DELETE FROM '.$dbPlatform->quoteIdentifier($classMetaData->getTableName()));
                $connection->query('PRAGMA foreign_keys = ON');
                $connection->commit();
            }
            catch (\Exception $e) {
                $connection->rollback();
            }
        }

        try {
            $connection->executeUpdate('DELETE FROM sqlite_sequence');
        }
        catch (\Exception $e) {
            //No sqlite_sequence table exists - that's ok, it just means we have no auto incrementing columns
        }
    }

    /**
     * @AfterScenario
     */
    public function closeDBALConnections()
    {
        foreach ($this->getEntityManagers() as $entityManager) {
            $entityManager->clear();
        }

        foreach ($this->getConnections() as $connection) {
            $connection->close();
        }
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return array
     */
    protected function getMetadata($entityManager)
    {
        return $entityManager->getMetadataFactory()->getAllMetadata();
    }

    /**
     * @return EntityManager[]
     */
    protected function getEntityManagers()
    {
        return $this->kernel->getContainer()->get('doctrine')->getManagers();
    }

    /**
     * @return Connection[]
     */
    protected function getConnections()
    {
        return $this->kernel->getContainer()->get('doctrine')->getConnections();
    }
}
