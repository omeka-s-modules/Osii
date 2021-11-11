<?php
namespace Osii;

use Omeka\Module\AbstractModule;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function install(ServiceLocatorInterface $services)
    {
        $sql = <<<'SQL'
CREATE TABLE osii_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, import_id INT UNSIGNED NOT NULL, local_item_id INT NOT NULL, snapshot_item LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', remote_item_id INT UNSIGNED NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, INDEX IDX_F2C53914B6A263D9 (import_id), UNIQUE INDEX UNIQ_F2C539148FBA57D3 (local_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE osii_import (id INT UNSIGNED AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, local_item_set_id INT DEFAULT NULL, snapshot_job_id INT DEFAULT NULL, import_job_id INT DEFAULT NULL, `label` VARCHAR(255) NOT NULL, root_endpoint LONGTEXT NOT NULL, key_identity VARCHAR(32) DEFAULT NULL, key_credential VARCHAR(32) DEFAULT NULL, remote_query LONGTEXT DEFAULT NULL, snapshot_data_types LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_properties LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_classes LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', data_type_map LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, INDEX IDX_73A097067E3C61F9 (owner_id), INDEX IDX_73A09706FD0B0A79 (local_item_set_id), INDEX IDX_73A0970611143C89 (snapshot_job_id), INDEX IDX_73A0970677D1F4B1 (import_job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE osii_item ADD CONSTRAINT FK_F2C53914B6A263D9 FOREIGN KEY (import_id) REFERENCES osii_import (id) ON DELETE CASCADE;
ALTER TABLE osii_item ADD CONSTRAINT FK_F2C539148FBA57D3 FOREIGN KEY (local_item_id) REFERENCES item (id) ON DELETE CASCADE;
ALTER TABLE osii_import ADD CONSTRAINT FK_73A097067E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;
ALTER TABLE osii_import ADD CONSTRAINT FK_73A09706FD0B0A79 FOREIGN KEY (local_item_set_id) REFERENCES item_set (id) ON DELETE SET NULL;
ALTER TABLE osii_import ADD CONSTRAINT FK_73A0970611143C89 FOREIGN KEY (snapshot_job_id) REFERENCES job (id) ON DELETE SET NULL;
ALTER TABLE osii_import ADD CONSTRAINT FK_73A0970677D1F4B1 FOREIGN KEY (import_job_id) REFERENCES job (id) ON DELETE SET NULL;
SQL;
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec($sql);
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec('DROP TABLE IF EXISTS osii_item;');
        $conn->exec('DROP TABLE IF EXISTS osii_import;');
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
    }
}
