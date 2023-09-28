<?php

namespace Equalify\Service;

use Equalify\Entity\IUser;
use Equalify\Entity\SoloUser;

/**
 * An IUserService implementation that returns a user with all permissions.
 *
 * @author Chris Kelly (TolstoyDotCom)
 */
class SoloCurrentUserService {

	public function __construct() {
		$this->user = new SoloUser();
	}

    /**
     * {@inheritdoc}
     */
    public function getUser() : IUser {
		return $this->user;
	}

}
