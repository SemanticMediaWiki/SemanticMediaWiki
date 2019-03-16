<?php

namespace SMW\Protection;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use SMW\DIProperty;
use SMW\MediaWiki\Hooks\ArticleProtectComplete;
use SMW\Message;
use SMW\Property\Annotators\EditProtectedPropertyAnnotator;
use SMW\SemanticData;
use User;
use WikiPage;

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
	private $editProtectionRight = false;

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
	 * @param string|boolean $editProtectionRight
	 */
	public function setEditProtectionRight( $editProtectionRight ) {
		$this->editProtectionRight = $editProtectionRight;
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
		if ( $this->editProtectionRight === false ) {
			return;
		}

		list( $isEditProtected, $isAnnotationBySystem ) = $this->fetchEditProtectedInfo( $semanticData );

		$title = $this->wikiPage->getTitle();

		if ( $title === null ) {
			return;
		}

		$restrictions = array_flip( $title->getRestrictions( 'edit' ) );

		// No `Is edit protected` was found and the restriction doesn't contain
		// a matchable `editProtectionRight`
		if ( $isEditProtected === null && !isset( $restrictions[$this->editProtectionRight] ) ) {
			return $this->log( __METHOD__ . ' no update required' );
		}

		if ( $isEditProtected && !isset( $restrictions[$this->editProtectionRight] ) && !$isAnnotationBySystem ) {
			return $this->doUpdateRestrictions( $isEditProtected );
		}

		if ( $isEditProtected && $title->isProtected( 'edit' ) || !$isEditProtected && !$title->isProtected( 'edit' ) ) {
			return $this->log( __METHOD__ . ' Status already set, no update required' );
		}

		$this->doUpdateRestrictions( $isEditProtected );
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

		if ( $dataItems !== [] ) {
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

		return [ $isEditProtected, $isAnnotationBySystem ];
	}

	private function doUpdateRestrictions( $isEditProtected ) {

		$protections = [];
		$expiry = [];

		if ( $isEditProtected ) {
			$this->log( __METHOD__ . ' add protection on edit, move' );

			$protections = [
				'edit' => $this->editProtectionRight,
				'move' => $this->editProtectionRight
			];

			$expiry = [
				'edit' => 'infinity',
				'move' => 'infinity'
			];
		} else {
			$this->log( __METHOD__ . ' remove protection on edit, move' );
			$protections = [];
			$expiry = [];
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

	private function log( $message, $context = [] ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
