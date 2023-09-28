<?php

namespace Equalify\Entity;

/**
 * An Equalify user that has all permissions.
 *
 * @author Chris Kelly (TolstoyDotCom)
 */
class SoloUser implements IUser {

    /**
     */
    public function __construct() {
    }

    /**
     * {@inheritdoc}
     */
    public function id() {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission($permission) : bool {
        return TRUE;
    }

}
