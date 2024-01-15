<?php

namespace Equalify\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event class used before content is displayed.
 *
 * @author Chris Kelly (TolstoyDotCom)
 */
class BeforeContentEvent extends EqualifyEvent {

    public const NAME = 'content.before';

    private $content;

    /**
     */
    public function __construct($content = []) {
		$this->content = $content;
    }

    /**
     */
    public function getContent() {
		return $this->content;
    }

    /**
     */
    public function setContent($content) {
		$this->content = $content;
    }

}
