<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Email must be unique, if it is set
 */
class Version20121114095817 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("CREATE UNIQUE INDEX UNIQ_5E75FD27E7927C74 ON ice_user (email)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_5E75FD27A0D96FBF ON ice_user (email_canonical)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("DROP INDEX UNIQ_5E75FD27E7927C74 ON ice_user");
        $this->addSql("DROP INDEX UNIQ_5E75FD27A0D96FBF ON ice_user");
    }
}