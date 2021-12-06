<?php
namespace Osii;

use Laminas\Router\Http;

return [
    'osii_media_ingester_mappers' => [
        'invokables' => [
            'html' => MediaIngesterMapper\Html::class,
            'iiif' => MediaIngesterMapper\Iiif::class,
            'oembed' => MediaIngesterMapper\Oembed::class,
            'upload' => MediaIngesterMapper\Upload::class,
            'url' => MediaIngesterMapper\Url::class,
            'youtube' => MediaIngesterMapper\Youtube::class,
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
            'Osii\MediaIngesterMapperManager' => Service\MediaIngesterMapper\ManagerFactory::class,
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'osii_imports' => Api\Adapter\OsiiImportAdapter::class,
            'osii_items' => Api\Adapter\OsiiItemAdapter::class,
            'osii_media' => Api\Adapter\OsiiMediaAdapter::class,
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
