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
        // Import the OSII vocabulary if it doesn't already exist.
        $apiManager = $services->get('Omeka\ApiManager');
        $response = $apiManager->search('vocabularies', [
            'namespace_uri' => 'http://omeka.org/s/vocabs/o-module-osii#',
            'limit' => 0,
        ]);
        if (0 === $response->getTotalResults()) {
            $rdfImporter = $services->get('Omeka\RdfImporter');
            $rdfImporter->import(
                'file',
                [
                    'o:namespace_uri' => 'http://omeka.org/s/vocabs/o-module-osii#',
                    'o:prefix' => 'osii',
                    'o:label' => 'Omeka S Item Importer',
                    'o:comment' => null,
                ],
                [
                    'file' => sprintf('%s/vocabs/osii.n3', __DIR__),
                    'format' => 'turtle',
                ]
            );
        }

        $sql = <<<'SQL'
CREATE TABLE osii_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, import_id INT UNSIGNED NOT NULL, local_item_id INT DEFAULT NULL, snapshot_item LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', remote_item_id INT UNSIGNED NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, INDEX IDX_F2C53914B6A263D9 (import_id), UNIQUE INDEX UNIQ_F2C539148FBA57D3 (local_item_id), UNIQUE INDEX UNIQ_F2C53914B6A263D956E4E539 (import_id, remote_item_id), UNIQUE INDEX UNIQ_F2C53914B6A263D98FBA57D3 (import_id, local_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE osii_media (id INT UNSIGNED AUTO_INCREMENT NOT NULL, import_id INT UNSIGNED NOT NULL, osii_item_id INT UNSIGNED NOT NULL, local_media_id INT DEFAULT NULL, snapshot_media LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', remote_media_id INT UNSIGNED NOT NULL, position INT UNSIGNED NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, INDEX IDX_8A14960EB6A263D9 (import_id), INDEX IDX_8A14960E98E475EB (osii_item_id), UNIQUE INDEX UNIQ_8A14960E790BF7ED (local_media_id), UNIQUE INDEX UNIQ_8A14960E98E475EB390DA239 (osii_item_id, remote_media_id), UNIQUE INDEX UNIQ_8A14960E98E475EB790BF7ED (osii_item_id, local_media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE osii_import (id INT UNSIGNED AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, local_item_set_id INT DEFAULT NULL, snapshot_job_id INT DEFAULT NULL, import_job_id INT DEFAULT NULL, `label` VARCHAR(255) NOT NULL, root_endpoint LONGTEXT NOT NULL, key_identity VARCHAR(32) DEFAULT NULL, key_credential VARCHAR(32) DEFAULT NULL, remote_query LONGTEXT DEFAULT NULL, keep_removed_resources TINYINT(1) NOT NULL, add_source_resource TINYINT(1) NOT NULL, source_site LONGTEXT DEFAULT NULL, snapshot_items LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_media LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_item_sets LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_data_types LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_properties LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_classes LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_vocabularies LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', snapshot_media_ingesters LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', data_type_map LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, snapshot_completed DATETIME DEFAULT NULL, import_completed DATETIME DEFAULT NULL, INDEX IDX_73A097067E3C61F9 (owner_id), INDEX IDX_73A09706FD0B0A79 (local_item_set_id), INDEX IDX_73A0970611143C89 (snapshot_job_id), INDEX IDX_73A0970677D1F4B1 (import_job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE osii_item_set (id INT UNSIGNED AUTO_INCREMENT NOT NULL, import_id INT UNSIGNED NOT NULL, local_item_set_id INT DEFAULT NULL, snapshot_item_set LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', remote_item_set_id INT UNSIGNED NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, INDEX IDX_BA8092CAB6A263D9 (import_id), UNIQUE INDEX UNIQ_BA8092CAFD0B0A79 (local_item_set_id), UNIQUE INDEX UNIQ_BA8092CAB6A263D959249BF1 (import_id, remote_item_set_id), UNIQUE INDEX UNIQ_BA8092CAB6A263D9FD0B0A79 (import_id, local_item_set_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE osii_item ADD CONSTRAINT FK_F2C53914B6A263D9 FOREIGN KEY (import_id) REFERENCES osii_import (id) ON DELETE CASCADE;
ALTER TABLE osii_item ADD CONSTRAINT FK_F2C539148FBA57D3 FOREIGN KEY (local_item_id) REFERENCES item (id) ON DELETE SET NULL;
ALTER TABLE osii_media ADD CONSTRAINT FK_8A14960EB6A263D9 FOREIGN KEY (import_id) REFERENCES osii_import (id) ON DELETE CASCADE;
ALTER TABLE osii_media ADD CONSTRAINT FK_8A14960E98E475EB FOREIGN KEY (osii_item_id) REFERENCES osii_item (id) ON DELETE CASCADE;
ALTER TABLE osii_media ADD CONSTRAINT FK_8A14960E790BF7ED FOREIGN KEY (local_media_id) REFERENCES media (id) ON DELETE SET NULL;
ALTER TABLE osii_import ADD CONSTRAINT FK_73A097067E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;
ALTER TABLE osii_import ADD CONSTRAINT FK_73A09706FD0B0A79 FOREIGN KEY (local_item_set_id) REFERENCES item_set (id) ON DELETE SET NULL;
ALTER TABLE osii_import ADD CONSTRAINT FK_73A0970611143C89 FOREIGN KEY (snapshot_job_id) REFERENCES job (id) ON DELETE SET NULL;
ALTER TABLE osii_import ADD CONSTRAINT FK_73A0970677D1F4B1 FOREIGN KEY (import_job_id) REFERENCES job (id) ON DELETE SET NULL;
ALTER TABLE osii_item_set ADD CONSTRAINT FK_BA8092CAB6A263D9 FOREIGN KEY (import_id) REFERENCES osii_import (id) ON DELETE CASCADE;
ALTER TABLE osii_item_set ADD CONSTRAINT FK_BA8092CAFD0B0A79 FOREIGN KEY (local_item_set_id) REFERENCES item_set (id) ON DELETE SET NULL;
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
        $conn->exec('DROP TABLE IF EXISTS osii_media;');
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
        // Enable searching item sets by OSII import ID (using osii_import_id).
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
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
                    'Osii\Entity\OsiiItemSet', $alias,
                    'WITH', sprintf('omeka_root.id = %s.localItemSet', $alias)
                );
                $queryBuilder->andWhere($queryBuilder->expr()->eq(
                    sprintf('%s.import', $alias),
                    $adapter->createNamedParameter($queryBuilder, $request->getValue('osii_import_id'))
                ));
            }
        );
        // Enable searching media by OSII import ID (using osii_import_id).
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
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
                    'Osii\Entity\OsiiMedia', $alias,
                    'WITH', sprintf('omeka_root.id = %s.localMedia', $alias)
                );
                $queryBuilder->andWhere($queryBuilder->expr()->eq(
                    sprintf('%s.import', $alias),
                    $adapter->createNamedParameter($queryBuilder, $request->getValue('osii_import_id'))
                ));
            }
        );
    }
}
