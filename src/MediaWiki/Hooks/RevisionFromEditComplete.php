<?php

namespace SMW\MediaWiki\Hooks;

use Exception;
use MediaWiki\Page\Hook\RevisionFromEditCompleteHook;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use SMW\EventDispatcher\EventDispatcher;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\ParserData;
use SMW\Property\AnnotatorFactory as PropertyAnnotatorFactory;
use SMW\Schema\Schema;
use SMW\Schema\SchemaFactory;
use SMW\Store;

/**
 * Hook: RevisionFromEditComplete called when a revision was inserted
 * due to an edit
 *
 * Fetch additional information that is related to the saving that has just happened,
 * e.g. regarding the last edit date. In runs where this hook is not triggered, the
 * last DB entry (of MW) will be used to fill such properties.
 *
 * Called from LocalFile.php, SpecialImport.php, Article.php, Title.php
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RevisionFromEditComplete
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class RevisionFromEditComplete implements RevisionFromEditCompleteHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly PropertyAnnotatorFactory $propertyAnnotatorFactory,
		private readonly SchemaFactory $schemaFactory,
		private readonly Store $store,
		private readonly EventDispatcher $eventDispatcher,
		private readonly MwCollaboratorFactory $mwCollaboratorFactory,
		private readonly UserFactory $userFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onRevisionFromEditComplete( $wikiPage, $rev, $originalRevId, $user, &$tags ) {
		$userObject = $this->userFactory->newFromUserIdentity( $user );

		$editInfo = $this->mwCollaboratorFactory->newEditInfo(
			$wikiPage,
			$rev,
			$userObject
		);

		$pageInfoProvider = $this->mwCollaboratorFactory->newPageInfoProvider(
			$wikiPage,
			$rev,
			$userObject
		);

		$this->doProcess( $wikiPage->getTitle(), $editInfo, $pageInfoProvider );

		return true;
	}

	private function doProcess( Title $title, $editInfo, $pageInfoProvider ): void {
		$editInfo->fetchEditInfo();

		$parserOutput = $editInfo->getOutput();

		if ( !$parserOutput instanceof ParserOutput ) {
			return;
		}

		$parserData = new ParserData( $title, $parserOutput );

		$this->addPredefinedPropertyAnnotation(
			$parserData,
			$pageInfoProvider,
			$this->tryCreateSchema( $title, $pageInfoProvider )
		);

		$context = [
			'context' => self::class,
			'title' => $title
		];

		$this->eventDispatcher->dispatch( 'InvalidateResultCache', $context );

		// If the concept was altered make sure to delete the cache
		if ( $title->getNamespace() === SMW_NS_CONCEPT ) {
			$this->store->deleteConceptCache( $title );
		}

		$parserData->copyToParserOutput();

		$this->eventDispatcher->dispatch( 'InvalidateEntityCache', $context );
	}

	private function tryCreateSchema( Title $title, $pageInfoProvider ) {
		if ( $title->getNamespace() !== SMW_NS_SCHEMA ) {
			return null;
		}

		try {
			$schema = $this->schemaFactory->newSchema(
				$title->getDBKey(),
				$pageInfoProvider->getNativeData()
			);
		} catch ( Exception ) {
			return null;
		}

		return $schema;
	}

	private function addPredefinedPropertyAnnotation( ParserData $parserData, $pageInfoProvider, ?Schema $schema = null ): void {
		$propertyAnnotator = $this->propertyAnnotatorFactory->newNullPropertyAnnotator(
			$parserData->getSemanticData()
		);

		$propertyAnnotator = $this->propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$pageInfoProvider
		);

		$propertyAnnotator = $this->propertyAnnotatorFactory->newSchemaPropertyAnnotator(
			$propertyAnnotator,
			$schema
		);

		$propertyAnnotator->addAnnotation();
	}

}
