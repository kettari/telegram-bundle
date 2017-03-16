<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 17:13
 */

namespace Kaula\TelegramBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {

  /**
   * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder
   */
  public function getConfigTreeBuilder() {
    $treeBuilder = new TreeBuilder();
    $rootNode = $treeBuilder->root('kaula_telegram');

    // @formatter:off
    /** @noinspection PhpUndefinedMethodInspection */
    $rootNode
      ->children()
          ->scalarNode('api_token')->end()
          ->scalarNode('certificate_file')->end()
        ->end() // twitter
      ->end();
    // @formatter:on

    return $treeBuilder;
  }

}