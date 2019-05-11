<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\EditInfo;
use SMW\Message;
use SMW\Property\Annotators\EditProtectedPropertyAnnotator;
use Title;

/**
 * Occurs after the protect article request has been processed
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ArticleProtectComplete extends HookHandler {

	/**
	 * Whether the update should be restricted or not. Which means that when
	 * no other change is required then categorize the update as restricted
	 * to avoid unnecessary cascading updates.
	 */
	const RESTRICTED_UPDATE = 'articleprotectcomplete.restricted.update';

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var EditInfo
	 */
	private $editInfo;

	/**
	 * @since  2.5
	 *
	 * @param Title $title
	 * @param EditInfo $editInfo
	 */
	public function __construct( Title $title, EditInfo $editInfo ) {
		$this->title = $title;
		$this->editInfo = $editInfo;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $protections
	 * @param string $reason
	 */
	public function process( $protections, $reason ) {

		if ( Message::get( 'smw-edit-protection-auto-update' ) === $reason ) {
			return $this->log( __METHOD__ . ' No changes required, invoked by own process!' );
		}

		$this->editInfo->fetchEditInfo();

		$output = $this->editInfo->getOutput();

		if ( $output === null ) {
			return $this->log( __METHOD__ . ' Missing ParserOutput!' );
		}

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$this->title,
			$output
		);

		$this->doPrepareData( $protections, $parserData );
		$parserData->setOrigin( 'ArticleProtectComplete' );

		$parserData->updateStore(
			true
		);
	}

	private function doPrepareData( $protections, $parserData ) {

		$isRestrictedUpdate = true;
		$isAnnotationBySystem = false;

		$dataItemFactory = ApplicationFactory::getInstance()->getDataItemFactory();
		$property = $dataItemFactory->newDIProperty( '_EDIP' );

		$dataItems = $parserData->getSemanticData()->getPropertyValues( $property );
		$dataItem = end( $dataItems );

		if ( $dataItem ) {
			$isAnnotationBySystem = $dataItem->getOption( EditProtectedPropertyAnnotator::SYSTEM_ANNOTATION );
		}

		$editProtectionRight = $this->getOption( 'smwgEditProtectionRight', false );

		// No _EDIP annotation but a selected protection matches the
		// `EditProtectionRight` setting
		if ( !$dataItem && isset( $protections['edit'] ) && $protections['edit'] === $editProtectionRight ) {
			$this->log( 'ArticleProtectComplete addProperty `Is edit protected`' );

			$isRestrictedUpdate = false;
			$parserData->getSemanticData()->addPropertyObjectValue(
				$property,
				$dataItemFactory->newDIBoolean( true )
			);
		}

		// _EDIP exists and was set by the EditProtectedPropertyAnnotator (which
		// means that is has been set by the system and is not a "human" added
		// annotation) but since the selected protection doesn't match the
		// `EditProtectionRight` setting, remove the annotation
		if ( $dataItem && $isAnnotationBySystem && isset( $protections['edit'] ) && $protections['edit'] !== $editProtectionRight ) {
			$this->log( 'ArticleProtectComplete removeProperty `Is edit protected`' );

			$isRestrictedUpdate = false;
			$parserData->getSemanticData()->removePropertyObjectValue(
				$property,
				$dataItemFactory->newDIBoolean( true )
			);
		}

		$parserData->getSemanticData()->setOption(
			self::RESTRICTED_UPDATE,
			$isRestrictedUpdate
		);
	}

}
