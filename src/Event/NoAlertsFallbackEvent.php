<?php

namespace Equalify\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event class to collect fallback HTML for reports page when there are
 * no alerts to display.
 *
 * @author Chris Kelly (TolstoyDotCom)
 */
class NoAlertsFallbackEvent extends EqualifyEvent {

    public const NAME = 'no_alerts_fallback';

    /**
     */
    public function __construct() {
    }

}
