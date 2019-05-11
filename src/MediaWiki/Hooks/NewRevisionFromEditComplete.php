<?php

namespace SMW\MediaWiki\Hooks;

use Onoi\EventDispatcher\EventDispatcherAwareTrait;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\EventHandler;
use SMW\MediaWiki\EditInfo;
use SMW\MediaWiki\PageInfoProvider;
use Title;

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
class NewRevisionFromEditComplete extends HookHandler {

	use EventDispatcherAwareTrait;

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var EditInfo
	 */
	private $editInfo;

	/**
	 * @var PageInfoProvider
	 */
	private $pageInfoProvider;

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param EditInfo $editInfo
	 * @param PageInfoProvider $pageInfoProvider
	 */
	public function __construct( Title $title, EditInfo $editInfo, PageInfoProvider $pageInfoProvider ) {
		parent::__construct();
		$this->title = $title;
		$this->editInfo = $editInfo;
		$this->pageInfoProvider = $pageInfoProvider;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function process() {

		$this->editInfo->fetchEditInfo();

		$parserOutput = $this->editInfo->getOutput();
		$schema = null;

		if ( !$parserOutput instanceof ParserOutput ) {
			return true;
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory->newParserData(
			$this->title,
			$parserOutput
		);

		if ( $this->title->getNamespace() === SMW_NS_SCHEMA ) {
			$schemaFactory = $applicationFactory->singleton( 'SchemaFactory' );

			try {
				$schema = $schemaFactory->newSchema(
					$this->title->getDBKey(),
					$this->pageInfoProvider->getNativeData()
				);
			} catch( \Exception $e ) {
				// Do nothing!
			}
		}

		$this->addPredefinedPropertyAnnotation(
			$applicationFactory,
			$parserData,
			$schema
		);

		$context = [
			'context' => 'NewRevisionFromEditComplete',
			'title' => $this->title
		];

		$this->eventDispatcher->dispatch( 'InvalidateResultCache', $context );

		// If the concept was altered make sure to delete the cache
		if ( $this->title->getNamespace() === SMW_NS_CONCEPT ) {
			$applicationFactory->getStore()->deleteConceptCache( $this->title );
		}

		$parserData->copyToParserOutput();

		$this->eventDispatcher->dispatch( 'InvalidateEntityCache', $context );

		return true;
	}

	private function addPredefinedPropertyAnnotation( $applicationFactory, $parserData, $schema = null ) {

		$propertyAnnotatorFactory = $applicationFactory->singleton( 'PropertyAnnotatorFactory' );

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$parserData->getSemanticData()
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$this->pageInfoProvider
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newSchemaPropertyAnnotator(
			$propertyAnnotator,
			$schema
		);

		$propertyAnnotator->addAnnotation();
	}

}
