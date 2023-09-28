<?php

namespace Equalify\Controller;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @author Chris Kelly (TolstoyDotCom)
 */
abstract class BaseController implements IController {

    /**
     * The service container.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $container;

    /**
     * The database service.
     *
     * @var \Equalify\Storage\Database\IDatabase
     */
    protected $db;

    /**
     */
    public function __construct() {
        $this->container = new ContainerBuilder();

        $yamlLoader = new YamlFileLoader($this->container, new FileLocator($GLOBALS['BASE_PATH']));
        $yamlLoader->load('services.yml');

        $fsLoader = new FilesystemLoader($GLOBALS['BASE_PATH'] . '/templates');
        $twig = new Environment($fsLoader, [
            //'cache' => $GLOBALS['BASE_PATH'] . '/compilation_cache',
            'cache' => FALSE,
        ]);

        $this->container->set('twig', $twig);

        $this->db = $this->container->get('db_service');
    }

    /**
     */
    abstract public function run() : void;

}
