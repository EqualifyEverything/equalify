<?php

declare(strict_types=1);

namespace Equalify\Test\Entity;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Equalify\Event\BeforeContentEvent;
use Equalify\Test\Fixtures\Content\BeforeContentEventSubscriber;
use PHPUnit\Framework\TestCase;

final class SoloUserTest extends TestCase {

    /**
     * The service container.
     *
     * This is basically a mapping between a key (like 'logger') and a
     * service (like a logger service).
     *
     * This container takes care of creating the services as necessary.
     *
     * While services can be added to the container by calling methods,
     * in our case the services are listed in services.yml
     *
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private $container;

    /**
     * Build the container from services.yml
     *
     * The code inside this method will be used in other files that want
     * to use the container.
     */
    protected function setUp(): void {
        $this->container = new ContainerBuilder();

        $loader = new YamlFileLoader($this->container, new FileLocator(__DIR__ . '/../..'));
        $loader->load('services.yml');
    }

    /**
     * This tests that the solo user has the 'access content' permission.
     *
     * This test will fail if the site isn't in solo mode.
     */
    public function testBeforeContentEventWorks(): void {
        $currentUserService = $this->container->get('current_user_service');
        $this->assertNotEmpty($currentUserService, 'currentUserService');

        $user = $currentUserService->getUser();
        $this->assertNotEmpty($user, 'user');

        $this->assertTrue($user->hasPermission('access content'), 'Has "access content" permission');
    }
}
