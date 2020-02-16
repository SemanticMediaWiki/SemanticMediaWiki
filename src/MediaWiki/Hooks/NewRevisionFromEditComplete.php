<?php

namespace SMW\MediaWiki\Hooks;

use Onoi\EventDispatcher\EventDispatcherAwareTrait;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\MediaWiki\EditInfo;
use SMW\MediaWiki\PageInfoProvider;
use SMW\ParserData;
use SMW\Schema\Schema;
use Title;
use SMW\MediaWiki\HookListener;
use SMW\OptionsAwareTrait;
use SMW\Property\AnnotatorFactory as PropertyAnnotatorFactory;
use SMW\Schema\SchemaFactory;

/**
 * Hook: NewRevisionFromEditComplete called when a revision was inserted
 * due to an edit
 *
 * Fetch additional information that is related to the saving that has just happened,
 * e.g. regarding the last edit date. In runs where this hook is not triggered, the
 * last DB entry (of MW) will be used to fill such properties.
 *
 * Called from LocalFile.php, SpecialImport.php, Article.php, Title.php
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NewRevisionFromEditComplete implements HookListener {

	use OptionsAwareTrait;
	use EventDispatcherAwareTrait;

	/**
	 * @var EditInfo
	 */
	private $editInfo;

	/**
	 * @var PageInfoProvider
	 */
	private $pageInfoProvider;

	/**
	 * @var PropertyAnnotatorFactory
	 */
	private $propertyAnnotatorFactory;

	/**
	 * @var SchemaFactory
	 */
	private $schemaFactory;

	/**
	 * @since 1.9
	 *
	 * @param EditInfo $editInfo
	 * @param PageInfoProvider $pageInfoProvider
	 * @param PropertyAnnotatorFactory $propertyAnnotatorFactory
	 * @param SchemaFactory $schemaFactory
	 */
	public function __construct( EditInfo $editInfo, PageInfoProvider $pageInfoProvider, PropertyAnnotatorFactory $propertyAnnotatorFactory, SchemaFactory $schemaFactory ) {
		$this->editInfo = $editInfo;
		$this->pageInfoProvider = $pageInfoProvider;
		$this->propertyAnnotatorFactory = $propertyAnnotatorFactory;
		$this->schemaFactory = $schemaFactory;
	}

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function process( Title $title ) {

		$this->editInfo->fetchEditInfo();

		$parserOutput = $this->editInfo->getOutput();

		if ( !$parserOutput instanceof ParserOutput ) {
			return true;
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory->newParserData(
			$title,
			$parserOutput
		);

		$this->addPredefinedPropertyAnnotation(
			$parserData,
			$this->tryCreateSchema( $title )
		);

		$context = [
			'context' => NewRevisionFromEditComplete::class,
			'title' => $title
		];

		$this->eventDispatcher->dispatch( 'InvalidateResultCache', $context );

		// If the concept was altered make sure to delete the cache
		if ( $title->getNamespace() === SMW_NS_CONCEPT ) {
			$applicationFactory->getStore()->deleteConceptCache( $title );
		}

		$parserData->copyToParserOutput();

		$this->eventDispatcher->dispatch( 'InvalidateEntityCache', $context );

		return true;
	}

	private function tryCreateSchema( $title ) {

		if ( $title->getNamespace() !== SMW_NS_SCHEMA ) {
			return null;
		}

		try {
			$schema = $this->schemaFactory->newSchema(
				$title->getDBKey(),
				$this->pageInfoProvider->getNativeData()
			);
		} catch( \Exception $e ) {
			return null;
		}

		return $schema;
	}

	private function addPredefinedPropertyAnnotation( ParserData $parserData, Schema $schema = null ) {

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
