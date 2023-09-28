<?php

declare(strict_types=1);

namespace Equalify\Test\Content;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Equalify\Event\BeforeContentEvent;
use Equalify\Test\Fixtures\Content\BeforeContentEventSubscriber;
use PHPUnit\Framework\TestCase;

final class ContentEventsTest extends TestCase {

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
     * This tests sending and receiving a BeforeContentEvent.
     *
     * First, get the event dispatcher service from the container.
     *
     * Then, tell the event dispatcher about the BeforeContentEventSubscriber
     * subscriber and dispatch the event.
     *
     * The subscriber should have modified the header.
     */
    public function testBeforeContentEventWorks(): void {
        $eventDispatcher = $this->container->get('event_dispatcher');

        $event = new BeforeContentEvent();

        $event->setContent([
            'header' => 'THIS IS THE HEADER',
            'body' => 'THIS IS THE BODY',
            'footer' => 'THIS IS THE FOOTER',
        ]);

        $eventDispatcher->addSubscriber(new BeforeContentEventSubscriber());

        $eventDispatcher->dispatch($event, BeforeContentEvent::NAME);

        $content = $event->getContent();

        $this->assertEquals($content['header'], 'NEW HEADER');
    }
}
