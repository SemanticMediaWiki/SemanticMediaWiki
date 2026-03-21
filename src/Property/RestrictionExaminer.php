<?php

namespace SMW\Property;

use MediaWiki\User\User;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Localizer\Message;
use SMW\PropertyRegistry;

/**
 * @license GPL-2.0-or-later
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
	 * @var bool|string
	 */
	private $createProtectionRight = false;

	/**
	 * @var bool
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
	public function setUser( User $user ): void {
		$this->user = $user;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|bool $createProtectionRight
	 */
	public function setCreateProtectionRight( $createProtectionRight ): void {
		$this->createProtectionRight = $createProtectionRight;
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $isQueryContext
	 */
	public function isQueryContext( $isQueryContext ): void {
		$this->isQueryContext = (bool)$isQueryContext;
	}

	/**
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function hasRestriction(): bool {
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
	 * @param string $errorMsg
	 *
	 * @return Property|null
	 */
	public static function grepPropertyFromRestrictionErrorMsg( $errorMsg ): ?Property {
		if ( strpos( $errorMsg, self::CREATE_RESTRICTION ) === false ) {
			return null;
		}

		$error = json_decode( $errorMsg, true );

		return isset( $error[2] ) ? Property::newFromUserLabel( $error[2] ) : null;
	}

	/**
	 * @since 3.0
	 *
	 * @param Property $property
	 * @param WikiPage|null $contextPage
	 */
	public function checkRestriction( Property $property, ?WikiPage $contextPage = null ): void {
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

		$this->error = Message::encode(
			[
				'smw-datavalue-property-restricted-declarative-use',
				$property->getLabel()
			],
			Message::PARSE
		);
		return $this->error;
	}

	private function isAnnotationRestricted( $property ) {
		if ( $this->isQueryContext || $property->isUserDefined() ) {
			return false;
		}

		if ( $property->isUserAnnotable() ) {
			return false;
		}

		$this->error = [
			'smw-datavalue-property-restricted-annotation-use',
			$property->getLabel()
		];
		return $this->error;
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
		$this->error = Message::encode(
			[
				self::CREATE_RESTRICTION,
				$property->getLabel(),
				$this->createProtectionRight
			],
			Message::PARSE
		);
		return $this->error;
	}

}
