<?php

namespace Equalify\Entity;

/**
 * Represents a user in Equalify.
 *
 * @author Chris Kelly (TolstoyDotCom)
 */
interface IUser {

    /**
     * Return the ID of this user.
     *
     * @return int
     *   The ID.
     */
    public function id();

    /**
     * Indicate if this this user has the given permission.
     *
     * @param string $permission
     *   A text permission, such as 'access content'
     *
     * @return bool
     *   TRUE if the user has that permission, FALSE otherwise.
     */
    public function hasPermission($permission) : bool;

}
