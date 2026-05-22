<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Page\Hook\ArticleProtectCompleteHook;
use Psr\Log\LoggerInterface;
use SMW\Localizer\Message;
use SMW\Property\Annotators\EditProtectedPropertyAnnotator;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;

/**
 * Occurs after the protect article request has been processed
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ArticleProtectComplete implements ArticleProtectCompleteHook {

	/**
	 * Whether the update should be restricted or not. Which means that when
	 * no other change is required then categorize the update as restricted
	 * to avoid unnecessary cascading updates.
	 */
	const RESTRICTED_UPDATE = 'articleprotectcomplete.restricted.update';

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Settings $settings,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onArticleProtectComplete( $wikiPage, $user, $protect, $reason ) {
		if ( Message::get( 'smw-edit-protection-auto-update' ) === $reason ) {
			$this->logger->info( __METHOD__ . ' No changes required, invoked by own process!' );
			return true;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$revisionGuard = $applicationFactory->singleton( 'RevisionGuard' );

		$editInfo = $applicationFactory->newMwCollaboratorFactory()->newEditInfo(
			$wikiPage,
			$revisionGuard->newRevisionFromPage( $wikiPage ),
			$user
		);

		$editInfo->fetchEditInfo();

		$output = $editInfo->getOutput();

		if ( $output === null ) {
			$this->logger->info( __METHOD__ . ' Missing ParserOutput!' );
			return true;
		}

		$parserData = $applicationFactory->newParserData(
			$wikiPage->getTitle(),
			$output
		);

		$this->doPrepareData( $protect, $parserData );
		$parserData->setOrigin( 'ArticleProtectComplete' );

		$parserData->updateStore(
			true
		);

		return true;
	}

	private function doPrepareData( array $protections, $parserData ): void {
		$isRestrictedUpdate = true;
		$isAnnotationBySystem = false;

		$dataItemFactory = ApplicationFactory::getInstance()->getDataItemFactory();
		$property = $dataItemFactory->newDIProperty( '_EDIP' );

		$dataItems = $parserData->getSemanticData()->getPropertyValues( $property );
		$dataItem = end( $dataItems );

		if ( $dataItem ) {
			$isAnnotationBySystem = $dataItem->getOption( EditProtectedPropertyAnnotator::SYSTEM_ANNOTATION );
		}

		$editProtectionRight = $this->settings->get( 'smwgEditProtectionRight' );

		// No _EDIP annotation but a selected protection matches the
		// `EditProtectionRight` setting
		if ( !$dataItem && isset( $protections['edit'] ) && $protections['edit'] === $editProtectionRight ) {
			$this->logger->info( 'ArticleProtectComplete addProperty `Is edit protected`' );

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
			$this->logger->info( 'ArticleProtectComplete removeProperty `Is edit protected`' );

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
