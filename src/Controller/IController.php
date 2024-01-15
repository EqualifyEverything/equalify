<?php

namespace Equalify\Controller;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @author Chris Kelly (TolstoyDotCom)
 */
interface IController {

    /**
     */
    public function run() : void;

}

