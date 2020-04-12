<?php

namespace SMW\Protection;

use Onoi\Cache\Cache;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\Store;
use SMW\EntityCache;
use SMW\MediaWiki\PermissionManager;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\Listener\ChangeListener\ChangeRecord;
use Title;
use User;

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
	 * @var PermissionManager
	 */
	private $permissionManager;

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
	 * @var []
	 */
	private $importPerformers = [];

	/**
	 * @var []
	 */
	private $importPerformerProtectionLookupCache = [];

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param EntityCache $entityCache
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( Store $store, EntityCache $entityCache, PermissionManager $permissionManager ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @since 3.2
	 *
	 * @param PropertyChangeListener $propertyChangeListener
	 */
	public function registerPropertyChangeListener( PropertyChangeListener $propertyChangeListener ) {
		$propertyChangeListener->addListenerCallback( new DIProperty( '_CHGPRO' ), [ $this, 'invalidateCache' ] );
	}

	/**
	 * @since 3.2
	 *
	 * @param DIProperty $property
	 * @param ChangeRecord $changeRecord
	 */
	public function invalidateCache( DIProperty $property, ChangeRecord $changeRecord ) {

		if ( $property->getKey() !== '_CHGPRO' ) {
			return;
		}

		foreach ( $changeRecord as $record ) {

			if ( !$record->has( 'row.s_id' ) ) {
				continue;
			}

			$subject = $this->store->getObjectIds()->getDataItemById(
				$record->get( 'row.s_id' )
			);

			$key = $this->entityCache->makeCacheKey( 'protection', $subject->getHash() );

			// If the change is an insert type then the `Change propagation` property
			// was added hence use this as a short cut to store the state avoiding
			// an extra lookup query and allow the state to be available as soon
			// as possible.
			if ( $record->has( 'is_insert' ) && $record->get( 'is_insert' ) === true ) {
				$this->entityCache->save( $key, 'yes' );
			} else {
				$this->entityCache->delete( $key );
			}
		}
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
	 * @since 3.2
	 *
	 * @param array $importPerformers
	 */
	public function setImportPerformers( array $importPerformers ) {
		$this->importPerformers = $importPerformers;
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
	 * If a page was imported by a dedicated `import_performer` and the performer
	 * is the creator of the page, yet the current user that is trying to edit the
	 * page isn't matched to the creator/import performer then the page is classified
	 * as to be protected to make sure only an import performer can alter the
	 * content without having to fear that other users may have changed the
	 * content hereby may loose information when replacing the content during
	 * the next import.
	 *
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param User $user
	 *
	 * @return boolean
	 */
	public function isClassifiedAsImportPerformerProtected( Title $title, User $user ) : bool {

		if ( $this->importPerformers === [] ) {
			return false;
		}

		$key = md5( $title->getDBKey() . "#" . $user->getName() );

		// `WikiPage::getCreator` -> `Title::getFirstRevision` isn't cached therefore
		// avoid repeated requests for the `key` combination
		if ( isset( $this->importPerformerProtectionLookupCache[$key] ) ) {
			return $this->importPerformerProtectionLookupCache[$key];
		}

		$creator = \WikiPage::factory( $title )->getCreator();

		if ( !$creator instanceof User ) {
			return $this->importPerformerProtectionLookupCache[$key] = false;
		}

		$importPerformers = array_flip( $this->importPerformers );

		// Was the creator a dedicated import performer?, if yes, it means
		// only this user is allowed to alter the content
		if ( !isset( $importPerformers[$creator->getName()] ) ) {
			return $this->importPerformerProtectionLookupCache[$key] = false;
		}

		return $this->importPerformerProtectionLookupCache[$key] = !$creator->equals( $user );
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

		return $this->createProtectionRight && !$this->permissionManager->userCan( 'edit', null, $title );
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

		return !$this->permissionManager->userCan( 'edit', null, $title ) && $this->checkProtection( $subject->asBase() );
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
