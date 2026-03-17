<?php

namespace SMW\MediaWiki\Preference;

use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class PreferenceExaminer {

	/**
	 * @since 3.2
	 */
	public function __construct(
		private ?User $user = null,
		private readonly ?UserOptionsLookup $userOptionsLookup = null,
	) {
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
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasPreferenceOf( string $key ): bool {
		if ( $this->user === null ) {
			return false;
		}

		if ( $this->userOptionsLookup === null ) {
			return $this->user->getOption( $key, false );
		} else {
			return $this->userOptionsLookup->getOption( $this->user, $key, false );
		}
	}

}
