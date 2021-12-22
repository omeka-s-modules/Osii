<?php
namespace Osii;

use Laminas\Router\Http;
use Osii\MediaIngesterMapper;
use Osii\ResourceMappers;
use Osii\Service\MediaIngesterMapper\MediaIngesterMapperFactory;
use Osii\Service\ResourceMapper\ResourceMapperFactory;

return [
    'osii_resource_mappers' => [
        'factories' => [
            ResourceMapper\ResourceOwner::class => ResourceMapperFactory::class,
            ResourceMapper\ResourceVisibility::class => ResourceMapperFactory::class,
            ResourceMapper\ResourceClass::class => ResourceMapperFactory::class,
            ResourceMapper\ResourceTemplate::class => ResourceMapperFactory::class,
            ResourceMapper\ResourceValues::class => ResourceMapperFactory::class,
            ResourceMapper\ResourceSourceUrls::class => ResourceMapperFactory::class,
            ResourceMapper\ItemMedia::class => ResourceMapperFactory::class,
            ResourceMapper\ItemItemSets::class => ResourceMapperFactory::class,
        ],
    ],
    'osii_media_ingester_mappers' => [
        'factories' => [
            MediaIngesterMapper\Html::class => MediaIngesterMapperFactory::class,
            MediaIngesterMapper\Iiif::class => MediaIngesterMapperFactory::class,
            MediaIngesterMapper\Oembed::class => MediaIngesterMapperFactory::class,
            MediaIngesterMapper\Youtube::class => MediaIngesterMapperFactory::class,
            MediaIngesterMapper\Upload::class => MediaIngesterMapperFactory::class,
            MediaIngesterMapper\Url::class => MediaIngesterMapperFactory::class,
        ],
        'aliases' => [
            'html' => MediaIngesterMapper\Html::class,
            'iiif' => MediaIngesterMapper\Iiif::class,
            'oembed' => MediaIngesterMapper\Oembed::class,
            'youtube' => MediaIngesterMapper\Youtube::class,
            'upload' => MediaIngesterMapper\Upload::class,
            'url' => MediaIngesterMapper\Url::class,
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => sprintf('%s/../language', __DIR__),
                'pattern' => '%s.mo',
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            sprintf('%s/../view', __DIR__),
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            sprintf('%s/../src/Entity', __DIR__),
        ],
        'proxy_paths' => [
            sprintf('%s/../data/doctrine-proxies', __DIR__),
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Osii\ResourceMapperManager' => Service\ResourceMapper\ManagerFactory::class,
            'Osii\MediaIngesterMapperManager' => Service\MediaIngesterMapper\ManagerFactory::class,
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'osii_imports' => Api\Adapter\OsiiImportAdapter::class,
            'osii_items' => Api\Adapter\OsiiItemAdapter::class,
            'osii_media' => Api\Adapter\OsiiMediaAdapter::class,
            'osii_item_sets' => Api\Adapter\OsiiItemSetAdapter::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Osii\Controller\Admin\Index' => Controller\Admin\IndexController::class,
        ],
        'factories' => [
            'Osii\Controller\Admin\Import' => Service\Controller\Admin\ImportControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'osii' => Service\ControllerPlugin\OsiiFactory::class,
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'osii' => Service\ViewHelper\OsiiFactory::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            'Osii\Form\ImportForm' => Service\Form\ImportFormFactory::class,
            'Osii\Form\PrepareImportForm' => Service\Form\PrepareImportFormFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Omeka S Item Importer', // @translate
                'route' => 'admin/osii',
                'controller' => 'index',
                'action' => 'index',
                'resource' => 'Osii\Controller\Admin\Import',
                'useRouteMatch' => true,
                'pages' => [
                    [
                        'route' => 'admin/osii-import',
                        'controller' => 'import',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/osii-import-id',
                        'controller' => 'import',
                        'visible' => false,
                    ],
                ],
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'osii' => [
                        'type' => Http\Segment::class,
                        'options' => [
                            'route' => '/osii[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Osii\Controller\Admin',
                                'controller' => 'index',
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'osii-import' => [
                        'type' => Http\Segment::class,
                        'options' => [
                            'route' => '/osii/import[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Osii\Controller\Admin',
                                'controller' => 'import',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'osii-import-id' => [
                        'type' => Http\Segment::class,
                        'options' => [
                            'route' => '/osii/import/:import-id[/:action]',
                            'constraints' => [
                                'import-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Osii\Controller\Admin',
                                'controller' => 'import',
                                'action' => 'show',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
