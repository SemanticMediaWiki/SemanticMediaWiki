<?php

namespace SMW\MediaWiki\Preference;

use User;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PreferenceExaminer {

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @since 3.2
	 *
	 * @param User|null $user
	 */
	public function __construct( User $user = null ) {
		$this->user = $user;
	}

	/**
	 * @since 3.2
	 *
	 * @return User $user
	 */
	public function setUser( User $user ) {
		$this->user = $user;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $right
	 *
	 * @return bool
	 */
	public function hasPreferenceOf( string $key ) : bool {

		if ( $this->user === null ) {
			return false;
		}

		return $this->user->getOption( $key, false );
	}

}
