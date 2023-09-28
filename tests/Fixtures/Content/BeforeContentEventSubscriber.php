<?php

namespace Equalify\Test\Fixtures\Content;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Equalify\Event\BeforeContentEvent;

/**
 * A sample subscriber to the BeforeContentEvent.
 *
 * This just changes the header, ContentEventsTest will test it works.
 *
 * @author Chris Kelly (TolstoyDotCom)
 */
class BeforeContentEventSubscriber implements EventSubscriberInterface {

	/**
	 * In this method, put the events you want to subscribe to.
	 */
    public static function getSubscribedEvents() {
        return [
            BeforeContentEvent::NAME => 'onBeforeContentEvent',
        ];
    }

	/**
	 * This will be run in response to the BeforeContentEvent event.
	 */
    public function onBeforeContentEvent(BeforeContentEvent $event) {
        $content = $event->getContent();

        $content['header'] = 'NEW HEADER';

        $event->setContent($content);
    }

}
