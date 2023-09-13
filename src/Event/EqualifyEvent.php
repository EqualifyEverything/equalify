<?php

namespace Equalify\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base class for events used in Equalify.
 *
 * @author Chris Kelly (TolstoyDotCom)
 */
class EqualifyEvent extends Event {

    /**
     */
    public function __construct() {
    }

}
