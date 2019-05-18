<?php

namespace SMW\Protection;

use Onoi\Cache\Cache;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\Store;
use SMW\EntityCache;
use Title;

/**
 * Handles protection validation.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ProtectionValidator {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var boolean|string
	 */
	private $editProtectionRight = false;

	/**
	 * @var boolean|string
	 */
	private $createProtectionRight = false;

	/**
	 * @var boolean|string
	 */
	private $changePropagationProtection = true;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param EntityCache $entityCache
	 */
	public function __construct( Store $store, EntityCache $entityCache ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|boolean $editProtectionRight
	 */
	public function setEditProtectionRight( $editProtectionRight ) {
		$this->editProtectionRight = $editProtectionRight;
	}

	/**
	 * @since 3.0
	 *
	 * @return string|false
	 */
	public function getEditProtectionRight() {
		return $this->editProtectionRight;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|boolean $createProtectionRight
	 */
	public function setCreateProtectionRight( $createProtectionRight ) {
		$this->createProtectionRight = $createProtectionRight;
	}

	/**
	 * @since 3.0
	 *
	 * @return string|false
	 */
	public function getCreateProtectionRight() {
		return $this->createProtectionRight;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $changePropagationProtection
	 */
	public function setChangePropagationProtection( $changePropagationProtection ) {
		$this->changePropagationProtection = (bool)$changePropagationProtection;
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasEditProtectionOnNamespace( Title $title ) {

		$subject = DIWikiPage::newFromTitle(
			$title
		);

		return $this->editProtectionRight && $this->checkProtection( $subject->asBase() );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasChangePropagationProtection( Title $title ) {

		$subject = DIWikiPage::newFromTitle( $title )->asBase();
		$namespace = $subject->getNamespace();

		if ( $namespace !== SMW_NS_PROPERTY && $namespace !== NS_CATEGORY ) {
			return false;
		}

		if ( $this->changePropagationProtection === false ) {
			return false;
		}

		return $this->checkProtection( $subject, new DIProperty( '_CHGPRO' ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasProtection( Title $title ) {

		$subject = DIWikiPage::newFromTitle(
			$title
		);

		return $this->checkProtection( $subject->asBase() );
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasCreateProtection( Title $title = null ) {

		if ( $title === null ) {
			return false;
		}

		return $this->createProtectionRight && !$title->userCan( 'edit' );
	}

	/**
	 * @note There is not direct validation of the permission within this method,
	 * it is done by the Title::userCan when probing against the User and hooks
	 * that carry out the permission check including the validation provided by
	 * SMW's `PermissionManager`.
	 *
	 * @since 2.5
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasEditProtection( Title $title = null ) {

		if ( $title === null ) {
			return false;
		}

		$subject = DIWikiPage::newFromTitle(
			$title
		);

		return !$title->userCan( 'edit' ) && $this->checkProtection( $subject->asBase() );
	}

	private function checkProtection( $subject, $property = null ) {

		if ( $property === null ) {
			$property = new DIProperty( '_EDIP' );
		}

		$key = $this->entityCache->makeCacheKey( 'protection', $subject->getHash() );
		$hasProtection = false;

		if ( $this->entityCache->contains( $key ) ) {
			return $this->entityCache->fetch( $key ) === 'yes';
		}

		$dataItems = $this->store->getPropertyValues(
			$subject,
			$property
		);

		if ( $dataItems !== null && $dataItems !== [] ) {
			$hasProtection = $property->getKey() === '_EDIP' ? end( $dataItems )->getBoolean() : true;
		}

		// Store as literal so that the check avoids a `false` and is not
		// attempting to read from the store on every check where it hasn't
		// found a positive confirmation
		$this->entityCache->save( $key, ( $hasProtection ? 'yes' : 'no' ) );
		$this->entityCache->associate( $subject, $key );

		return $hasProtection;
	}

}
