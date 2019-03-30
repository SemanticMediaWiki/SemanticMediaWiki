<?php

namespace SMW\Property;

use SMW\Message;
use SMW\DIProperty;
use SMW\PropertyRegistry;
use SMW\DIWikiPage;
use Title;
use User;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RestrictionExaminer {

	const CREATE_RESTRICTION = 'smw-datavalue-property-create-restriction';

	/**
	 * @var array
	 */
	private $error = [];

	/**
	 * @var User|null
	 */
	private $user;

	/**
	 * @var boolean|string
	 */
	private $createProtectionRight = false;

	/**
	 * @var boolean
	 */
	private $isQueryContext = false;

	/**
	 * @var array
	 */
	private $exists = [];

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
		return $this->error !== [];
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
	 * @param DIWikiPage|null $contextPage
	 */
	public function checkRestriction( DIProperty $property, DIWikiPage $contextPage = null ) {

		$this->error = [];

		if ( $this->isDeclarative( $property, $contextPage ) ) {
			return;
		}

		if ( $this->isAnnotationRestricted( $property ) ) {
			return;
		}

		if ( $this->isCreateProtected( $property ) ) {
			return;
		}
	}

	private function isDeclarative( $property, $contextPage = null ) {

		if ( $this->isQueryContext || $contextPage === null ) {
			return false;
		}

		$ns = $contextPage->getNamespace();

		// Property, category page are allowed to carry declarative properties
		if ( $ns === SMW_NS_PROPERTY || $ns === NS_CATEGORY ) {
			return false;
		}

		if ( !PropertyRegistry::getInstance()->isDeclarative( $property->getKey() ) ) {
			return false;
		}

		return $this->error = Message::encode(
			[
				'smw-datavalue-property-restricted-declarative-use',
				$property->getLabel()
			],
			Message::PARSE
		);
	}

	private function isAnnotationRestricted( $property ) {

		if ( $this->isQueryContext || $property->isUserDefined() ) {
			return false;
		}

		if ( $property->isUserAnnotable() ) {
			return false;
		}

		return $this->error = [
			'smw-datavalue-property-restricted-annotation-use',
			$property->getLabel()
		];
	}

	private function isCreateProtected( $property ) {

		if ( $this->user === null || $this->createProtectionRight === false ) {
			return false;
		}

		$key = $property->getKey();

		// Non-existing property?
		if ( !isset( $this->exists[$key] ) ) {
			$this->exists[$key] = $property->isUserDefined() && $property->getDiWikiPage()->getTitle()->exists();
		}

		if ( $this->exists[$key] || $this->user->isAllowed( $this->createProtectionRight ) ) {
			return false;
		}

		// A user without the appropriate right cannot use a non-existing property
		return $this->error = Message::encode(
			[
				self::CREATE_RESTRICTION,
				$property->getLabel(),
				$this->createProtectionRight
			],
			Message::PARSE
		);
	}

}
