<?php

namespace SMW\Protection;

use SMW\Message;
use SMW\SemanticData;
use SMW\DIProperty;
use SMW\PropertyAnnotators\EditProtectedPropertyAnnotator;
use SMW\MediaWiki\Hooks\ArticleProtectComplete;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use WikiPage;
use User;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EditProtectionUpdater implements LoggerAwareInterface {

	/**
	 * @var WikiPage
	 */
	private $wikiPage;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var boolean
	 */
	private $isRestrictedUpdate = false;

	/**
	 * @var boolean|string
	 */
	private $editProtectionRights = false;

	/**
	 * @var boolean|string
	 */
	private $editProtectionEnforcedRight = false;

	/**
	 * LoggerInterface
	 */
	private $logger;

	/**
	 * @since 2.5
	 *
	 * @param WikiPage $wikiPage
	 * @param User|null $user
	 */
	public function __construct( WikiPage $wikiPage, User $user = null ) {
		$this->wikiPage = $wikiPage;
		$this->user = $user;

		if ( $this->user === null ) {
			$this->user = $GLOBALS['wgUser'];
		}
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|boolean $editProtectionRights
	 */
	public function setEditProtectionRights( $editProtectionRights ) {
		$this->editProtectionRights = is_bool( $editProtectionRights ) ? $editProtectionRights : (array)$editProtectionRights;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|boolean $editProtectionEnforcedRight
	 */
	public function setEditProtectionEnforcedRight( $editProtectionEnforcedRight ) {
		$this->editProtectionEnforcedRight = $editProtectionEnforcedRight;
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isRestrictedUpdate() {
		return $this->isRestrictedUpdate;
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData $semanticData
	 */
	public function doUpdateFrom( SemanticData $semanticData ) {

		// Do nothing
		if ( $this->editProtectionRights === false ) {
			return;
		}

		list( $isEditProtected, $isAnnotationBySystem ) = $this->fetchEditProtectedInfo( $semanticData );

		$title = $this->wikiPage->getTitle();

		if ( $title === null ) {
			return;
		}

		$hasEditRestrictions = $this->hasEditRestrictions( $title );

		// No `Is edit protected` was found and the restriction doesn't contain
		// a matchable `editProtectionRights`
		if ( $isEditProtected === null && !$hasEditRestrictions ) {
			return $this->log( __METHOD__ . ' no update required' );
		}

		if ( $isEditProtected && !$hasEditRestrictions && !$isAnnotationBySystem ) {
			return $this->doUpdateRestrictions( $isEditProtected );
		}

		if ( $isEditProtected && $title->isProtected( 'edit' ) || !$isEditProtected && !$title->isProtected( 'edit' ) ) {
			return $this->log( __METHOD__ . ' Status already set, no update required' );
		}

		$this->doUpdateRestrictions( $isEditProtected );
	}

	private function hasEditRestrictions( $title ) {

		$restrictions = array_flip( $title->getRestrictions( 'edit' ) );

		foreach ( $this->editProtectionRights as $editProtectionRight ) {
			if ( isset( $restrictions[$editProtectionRight]) ) {
				return true;
			}
		}

		return false;
	}

	private function fetchEditProtectedInfo( $semanticData ) {

		// Whether or not the update was invoked by the ArticleProtectComplete hook
		$this->isRestrictedUpdate = $semanticData->getOption( ArticleProtectComplete::RESTRICTED_UPDATE ) === true;
		$property = new DIProperty( '_EDIP' );

		$isEditProtected = null;
		$isAnnotationBySystem = false;

		$dataItems = $semanticData->getPropertyValues(
			$property
		);

		if ( $dataItems !== array() ) {
			$isEditProtected = false;

			// In case of two competing values, true always wins
			foreach ( $dataItems as $dataItem ) {

				$isEditProtected = $dataItem->getBoolean();

				if ( $isEditProtected ) {
					break;
				}
			}

			$isAnnotationBySystem = $dataItem->getOption( EditProtectedPropertyAnnotator::SYSTEM_ANNOTATION );
		}

		return array( $isEditProtected, $isAnnotationBySystem );
	}

	private function doUpdateRestrictions( $isEditProtected ) {

		$protections = array();
		$expiry = array();

		$editProtectionRight = array_shift( $this->editProtectionRights );

		if ( in_array( $this->editProtectionEnforcedRight, $this->editProtectionRights ) ) {
			$editProtectionRight = $this->editProtectionEnforcedRight;
		}

		if ( $isEditProtected ) {
			$this->log( __METHOD__ . " add `$editProtectionRight` protection on edit, move" );

			$protections = array(
				'edit' => $editProtectionRight,
				'move' => $editProtectionRight
			);

			$expiry = array(
				'edit' => 'infinity',
				'move' => 'infinity'
			);
		} else {
			$this->log( __METHOD__ . ' remove protection on edit, move' );
			$protections = array();
			$expiry = array();
		}

		$reason = Message::get( 'smw-edit-protection-auto-update' );
		$cascade = false;

		$status = $this->wikiPage->doUpdateRestrictions(
			$protections,
			$expiry,
			$cascade,
			$reason,
			$this->user
		);
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
