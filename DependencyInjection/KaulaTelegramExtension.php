<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 17:10
 */

namespace Kaula\TelegramBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class KaulaTelegramExtension extends Extension {

  /**
   * @param array $configs
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   */
  public function load(array $configs, ContainerBuilder $container) {
    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);
    // Set parameters
    $container->setParameter('kaula_telegram', $config);

    $loader = new YamlFileLoader($container,
      new FileLocator(__DIR__.'/../Resources/config'));
    $loader->load('services.yml');
  }
}