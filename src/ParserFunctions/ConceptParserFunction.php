<?php

namespace SMW\ParserFunctions;

use Html;
use Parser;
use SMW\ApplicationFactory;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\MessageFormatter;
use SMW\ParserData;
use SMW\PostProcHandler;
use SMWInfolink;
use SMWQueryProcessor as QueryProcessor;
use Title;

/**
 * Class that provides the {{#concept}} parser function
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ConceptParserFunction {

	/**
	 * @var ParserData
	 */
	private $parserData;

	/**
	 * @var MessageFormatter
	 */
	private $messageFormatter;

	/**
	 * @var PostProcHandler
	 */
	private $postProcHandler;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param MessageFormatter $messageFormatter
	 */
	public function __construct( ParserData $parserData, MessageFormatter $messageFormatter ) {
		$this->parserData = $parserData;
		$this->messageFormatter = $messageFormatter;
	}

	/**
	 * @since 3.0
	 *
	 * @param PostProcHandler $postProcHandler
	 */
	public function setPostProcHandler( PostProcHandler $postProcHandler ) {
		$this->postProcHandler = $postProcHandler;
	}

	/**
	 * Parse parameters, return concept information box and update the
	 * ParserOutput with the concept object
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 *
	 * @return string|null
	 */
	public function parse( array $rawParams ) {
		$this->parserData->getOutput()->addModules( 'ext.smw.style' );

		$title = $this->parserData->getTitle();
		$property = new DIProperty( '_CONC' );

		if ( !( $title->getNamespace() === SMW_NS_CONCEPT ) ) {
			return $this->messageFormatter->addFromKey( 'smw_no_concept_namespace' )->getHtml();
		} elseif ( count( $this->parserData->getSemanticData()->getPropertyValues( $property ) ) > 0 ) {
			return $this->messageFormatter->addFromKey( 'smw_multiple_concepts' )->getHtml();
		}

		// Remove parser object from parameters array
		if( isset( $rawParams[0] ) && $rawParams[0] instanceof Parser ) {
			array_shift( $rawParams );
		}

		// Use first parameter as concept (query) string
		$conceptQuery = array_shift( $rawParams );

		// Use second parameter, if any as a description
		$conceptDocu = array_shift( $rawParams );

		$query = $this->buildQuery( $conceptQuery );

		$conceptQueryString = $query->getDescription()->getQueryString();

		$this->parserData->getSemanticData()->addPropertyObjectValue(
			$property,
			new DIConcept(
				$conceptQueryString,
				$conceptDocu,
				$query->getDescription()->getQueryFeatures(),
				$query->getDescription()->getSize(),
				$query->getDescription()->getDepth()
			)
		);

		$this->messageFormatter
			->addFromArray( $query->getErrors() )
			->addFromArray( $this->parserData->getErrors() );

		if ( $this->postProcHandler !== null ) {
			$this->postProcHandler->addCheck( $query );
		}

		$this->addQueryProfile( $query );

		$this->parserData->pushSemanticDataToParserOutput();

		if ( $this->messageFormatter->exists() ) {
			return $this->messageFormatter->getHtml();
		}

		return $this->createHtml( $title, $conceptQueryString, $conceptDocu );
	}

	private function createHtml( Title $title, $queryString, $documentation ) {

		$message = '';

		if ( wfMessage( 'smw-concept-introductory-message' )->exists() ) {
			$message = Html::rawElement(
				'div',
				[
					'class' => 'plainlinks smw-callout smw-callout-info'
				],
				wfMessage( 'smw-concept-introductory-message', $title->getText() )->text()
			);
		}

		return $message . Html::rawElement( 'div', [ 'class' => 'smwfact' ],
			Html::rawElement( 'span', [ 'class' => 'smwfactboxhead' ],
				wfMessage( 'smw_concept_description', $title->getText() )->text() ) .
			Html::rawElement( 'span', [ 'class' => 'smwrdflink' ], $this->getRdfLink( $title )->getWikiText() ) .
			Html::element( 'br', [] ) .
			Html::element( 'p', [ 'class' => 'concept-documenation' ], $documentation ? $documentation : '' ) .
			Html::rawElement( 'pre', [], str_replace( '[', '&#91;', $queryString ) ) .
			Html::element( 'br', [] )
		);
	}

	private function getRdfLink( Title $title ) {
		return SMWInfolink::newInternalLink(
			wfMessage( 'smw_viewasrdf' )->text(),
			$title->getPageLanguage()->getNsText( NS_SPECIAL ) . ':ExportRDF/' . $title->getPrefixedText(), 'rdflink'
		);
	}

	private function buildQuery( $conceptQueryString ) {
		$rawParams = [ $conceptQueryString ];

		list( $query, ) = QueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			QueryProcessor::CONCEPT_DESC,
			false
		);

		return $query;
	}

	private function addQueryProfile( $query ) {

		// If the smwgQueryProfiler is marked with FALSE then just don't create a profile.
		if ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgQueryProfiler' ) === false ) {
			return;
		}

		$query->setContextPage(
			$this->parserData->getSemanticData()->getSubject()
		);

		$profileAnnotatorFactory = ApplicationFactory::getInstance()->getQueryFactory()->newProfileAnnotatorFactory();

		$descriptionProfileAnnotator = $profileAnnotatorFactory->newDescriptionProfileAnnotator(
			$query
		);

		$descriptionProfileAnnotator->pushAnnotationsTo(
			$this->parserData->getSemanticData()
		);
	}

}
