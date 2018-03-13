<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class KettariTelegramExtension extends Extension {

  /**
   * @param array $configs
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   */
  public function load(array $configs, ContainerBuilder $container) {
    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);
    // Set parameters
    $container->setParameter('kettari_telegram', $config);

    $loader = new YamlFileLoader($container,
      new FileLocator(__DIR__.'/../Resources/config'));
    $loader->load('services.yml');

    // Once the services definition are read, get your service and add a method call to setConfig()
    /*$communicatorServiceDefinition = $container->getDefinition( 'kettari_telegram.communicator' );
    $communicatorServiceDefinition->addMethodCall( 'setConfig', array( $config[ 'api_token' ] ) );*/
  }
}