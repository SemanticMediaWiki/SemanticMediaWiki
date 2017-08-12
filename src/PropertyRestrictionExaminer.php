<?php

namespace SMW;

use Title;
use User;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyRestrictionExaminer {

	const CREATE_RESTRICTION = 'smw-datavalue-property-create-restriction';

	/**
	 * @var array
	 */
	private $error = array();

	/**
	 * @var User|null
	 */
	private $user;

	/**
	 * @var boolean
	 */
	private $createProtectionRight = false;

	/**
	 * @var boolean
	 */
	private $isQueryContext = false;

	/**
	 * @var array
	 */
	private $propertyList = array();

	/**
	 * @since 2.5
	 *
	 * @param User $user
	 */
	public function setUser( User $user ) {
		$this->user = $user;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|boolean $createProtectionRight
	 */
	public function setCreateProtectionRight( $createProtectionRight ) {
		$this->createProtectionRight = $createProtectionRight;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isQueryContext
	 */
	public function isQueryContext( $isQueryContext ) {
		$this->isQueryContext = (bool)$isQueryContext;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function hasRestriction() {
		return $this->error !== array();
	}

	/**
	 * @since 3.0
	 *
	 * @param array
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $error
	 *
	 * @return DIProperty|null
	 */
	public static function grepPropertyFromRestrictionErrorMsg( $errorMsg ) {

		if ( strpos( $errorMsg, self::CREATE_RESTRICTION ) === false ) {
			return null;
		}

		$error = json_decode( $errorMsg, true );

		return isset( $error[2] ) ? DIProperty::newFromUserLabel( $error[2] ) : null;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIProperty $property
	 */
	public function checkRestriction( DIProperty $property ) {

		$this->error = array();

		if ( !$this->isQueryContext && !$property->isUserDefined() && !$property->isUnrestricted() ) {
			return $this->error = array( 'smw-datavalue-property-restricted-use', $property->getLabel() );
		}

		if ( $this->user === null || $this->createProtectionRight === false ) {
			return;
		}

		$key = $property->getKey();

		// Non-existing property?
		if ( !isset( $this->propertyList[$key] ) ) {
			$this->propertyList[$key] = $property->isUserDefined() && !$property->getDiWikiPage()->getTitle()->exists();
		}

		// A user without the approriate right cannot use a non-existing property
		if ( $this->user && $this->propertyList[$key] && !$this->user->isAllowed( $this->createProtectionRight ) ) {
			return $this->error = Message::encode(
				array(
					self::CREATE_RESTRICTION,
					$property->getLabel(),
					$this->createProtectionRight
				),
				Message::PARSE
			);
		}
	}

}
