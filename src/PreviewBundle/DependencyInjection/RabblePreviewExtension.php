<?php

namespace Rabble\PreviewBundle\DependencyInjection;

use Rabble\FieldTypeBundle\VichUploader\RabbleNumberedNamer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class RabblePreviewExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container)
    {
        $currentConfig = $container->getExtensionConfig('doctrine_phpcr')[0];
        if (isset($currentConfig['session']['sessions'])) {
            $currentConfig = $currentConfig['session']['sessions']['default'];
        } else {
            $currentConfig = $currentConfig['session'];
        }
        $newConfig = [
            'session' => [
                'sessions' => [
                    'default' => $currentConfig,
                    'rabble_preview' => array_merge($currentConfig, [
                        'workspace' => 'rabble_preview',
                    ]),
                ],
            ],
        ];
        $container->prependExtensionConfig('doctrine_phpcr', $newConfig);

        $container->prependExtensionConfig('vich_uploader', [
            'mappings' => [
                'rabble_preview' => [
                    'db_driver' => 'orm',
                    'uri_prefix' => '/uploads/rabble_preview',
                    'upload_destination' => '%kernel.project_dir%/public/uploads/rabble_preview',
                    'namer' => RabbleNumberedNamer::class,
                ],
            ],
        ]);
    }
}
