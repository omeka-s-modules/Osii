<?php
namespace Osii;

use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        // Set the corresponding visibility rules on OSII items.
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $filter = $entityManager->getFilters()->getFilter('resource_visibility');
        $filter->addRelatedEntity('Osii\Entity\OsiiItem', 'local_item_id');
    }

    public function install(ServiceLocatorInterface $services)
    {
        $sql = <<<'SQL'
CREATE TABLE osii_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, import_id INT UNSIGNED NOT NULL, local_item_id INT DEFAULT NULL, snapshot_item LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', remote_item_id INT UNSIGNED NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, INDEX IDX_F2C53914B6A263D9 (import_id), UNIQUE INDEX UNIQ_F2C539148FBA57D3 (local_item_id), UNIQUE INDEX UNIQ_F2C53914B6A263D956E4E539 (import_id, remote_item_id), UNIQUE INDEX UNIQ_F2C53914B6A263D98FBA57D3 (import_id, local_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE osii_import (id INT UNSIGNED AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, local_item_set_id INT DEFAULT NULL, snapshot_job_id INT DEFAULT NULL, import_job_id INT DEFAULT NULL, `label` VARCHAR(255) NOT NULL, root_endpoint LONGTEXT NOT NULL, key_identity VARCHAR(32) DEFAULT NULL, key_credential VARCHAR(32) DEFAULT NULL, remote_query LONGTEXT DEFAULT NULL, snapshot_items LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_data_types LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_properties LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_classes LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_vocabularies LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', data_type_map LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, snapshot_completed DATETIME DEFAULT NULL, import_completed DATETIME DEFAULT NULL, INDEX IDX_73A097067E3C61F9 (owner_id), INDEX IDX_73A09706FD0B0A79 (local_item_set_id), INDEX IDX_73A0970611143C89 (snapshot_job_id), INDEX IDX_73A0970677D1F4B1 (import_job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
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
        // Add the module namespace to the JSON-LD context.
        $sharedEventManager->attach(
            '*',
            'api.context',
            function (Event $event) {
                $context = $event->getParam('context');
                $context['o-module-osii'] = 'http://omeka.org/s/vocabs/module/osii#';
                $event->setParam('context', $context);
            }
        );
        // Enable searching items by OSII import ID (using osii_import_id).
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            function (Event $event) {
                $request = $event->getParam('request');
                if (!$request->getValue('osii_import_id')) {
                    return;
                }
                $adapter = $event->getTarget();
                $alias = $adapter->createAlias();
                $queryBuilder = $event->getParam('queryBuilder');
                $queryBuilder->innerJoin(
                    'Osii\Entity\OsiiItem', $alias,
                    'WITH', sprintf('omeka_root.id = %s.localItem', $alias)
                );
                $queryBuilder->andWhere($queryBuilder->expr()->eq(
                    sprintf('%s.import', $alias),
                    $adapter->createNamedParameter($queryBuilder, $request->getValue('osii_import_id'))
                ));
            }
        );
    }
}
