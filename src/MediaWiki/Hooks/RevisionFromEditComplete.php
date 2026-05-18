<?php

namespace SMW\MediaWiki\Hooks;

use Exception;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use SMW\EventDispatcher\EventDispatcherAwareTrait;
use SMW\MediaWiki\EditInfo;
use SMW\MediaWiki\HookListener;
use SMW\MediaWiki\PageInfoProvider;
use SMW\OptionsAwareTrait;
use SMW\ParserData;
use SMW\Property\AnnotatorFactory as PropertyAnnotatorFactory;
use SMW\Schema\Schema;
use SMW\Schema\SchemaFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
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
class RevisionFromEditComplete implements HookListener {

	use OptionsAwareTrait;
	use EventDispatcherAwareTrait;

	/**
	 * @since 1.9
	 */
	public function __construct(
		private readonly EditInfo $editInfo,
		private readonly PageInfoProvider $pageInfoProvider,
		private readonly PropertyAnnotatorFactory $propertyAnnotatorFactory,
		private readonly SchemaFactory $schemaFactory,
		private readonly Store $store,
	) {
	}

	/**
	 * @since 1.9
	 */
	public function process( Title $title ): bool {
		$this->editInfo->fetchEditInfo();

		$parserOutput = $this->editInfo->getOutput();

		if ( !$parserOutput instanceof ParserOutput ) {
			return true;
		}

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$title,
			$parserOutput
		);

		$this->addPredefinedPropertyAnnotation(
			$parserData,
			$this->tryCreateSchema( $title )
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

		return true;
	}

	private function tryCreateSchema( Title $title ) {
		if ( $title->getNamespace() !== SMW_NS_SCHEMA ) {
			return null;
		}

		try {
			$schema = $this->schemaFactory->newSchema(
				$title->getDBKey(),
				$this->pageInfoProvider->getNativeData()
			);
		} catch ( Exception ) {
			return null;
		}

		return $schema;
	}

	private function addPredefinedPropertyAnnotation( ParserData $parserData, ?Schema $schema = null ): void {
		$propertyAnnotator = $this->propertyAnnotatorFactory->newNullPropertyAnnotator(
			$parserData->getSemanticData()
		);

		$propertyAnnotator = $this->propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$this->pageInfoProvider
		);

		$propertyAnnotator = $this->propertyAnnotatorFactory->newSchemaPropertyAnnotator(
			$propertyAnnotator,
			$schema
		);

		$propertyAnnotator->addAnnotation();
	}

}
