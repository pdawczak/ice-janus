<?php

namespace Ice\MailerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class IceMailerExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $container->setParameter('ice_mailer.cdn_base_url', $config['cdn_base_url']);

        $fileRepositoryClass = 'Ice\MailerBundle\Attachment\FileCDNRepository';
        if ($config['file_repository_type'] && $config['file_repository_type'] == "test")
            $fileRepositoryClass = 'Ice\MailerBundle\Tests\Attachment\FileCDNRepositoryMock';

        $container->setParameter(
            'ice_mailer.files.file_repository.class',
            $fileRepositoryClass
        );

    }
}
