<?php

namespace Equalify\Entity;

use Equalify\Entity\IUser;

/**
 * Represents the service that returns the current logged-in user.
 *
 * @author Chris Kelly (TolstoyDotCom)
 */
interface ICurrentUserService {

    /**
     * Return the current user.
     *
     * This will always return an object.
     *
     * @return \Equalify\Entity\IUser
     *   The user.
     */
    public function getUser() : IUser;

}
