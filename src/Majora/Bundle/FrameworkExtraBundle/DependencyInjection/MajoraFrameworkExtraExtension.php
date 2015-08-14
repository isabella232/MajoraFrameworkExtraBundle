<?php

namespace Majora\Bundle\FrameworkExtraBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MajoraFrameworkExtraExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('serializer.xml');
        $loader->load('services.xml');

        // clock mocker
        if (!empty($config['clock']['enabled'])) {
            $loader->load('clock.xml');
            $container->getDefinition('majora.clock')->replaceArgument(
                0,
                $config['clock']['mock_param']
            );
        }

        // agnostic url generator
        if (!empty($config['agnostic_url_generator']['enabled'])) {
            $loader->load('agnostic_url_generator.xml');
        }

        // web socket server
        if (!empty($config['web_socket']['server']['enabled'])) {
            $container->setParameter('majora.web_socket.server.end_point', sprintf(
                '%s://%s',
                $config['web_socket']['server']['protocol'],
                $config['web_socket']['server']['host']
            ));

            $loader->load('web_socket_server.xml');
        }

        // web socket client
        if (!empty($config['web_socket']['client']['enabled'])) {
            $container->setParameter('majora.web_socket.client.remote_end_point', sprintf(
                '%s://%s',
                $config['web_socket']['client']['remote_protocol'],
                $config['web_socket']['client']['remote_host']
            ));

            $loader->load('web_socket_client.xml');

            $webSocketClientDefinition = $container->getDefinition('majora.web_socket.client');
            foreach ($config['web_socket']['client']['listen'] as $listenedEvent) {
                $webSocketClientDefinition->addTag('kernel.event_listener', array(
                    'event' => $listenedEvent,
                    'method' => 'onBroadcastableEvent',
                ));
            }
        }
    }
}
