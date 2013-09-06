<?php

namespace SMW;

use Parser;
use Html;
use Title;

use SMWDIProperty;
use SMWInfolink;
use SMWQueryProcessor;

/**
 * Class that provides the {{#concept}} parser function
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * Class that provides the {{#concept}} parser function
 *
 * @ingroup ParserFunction
 */
class ConceptParserFunction {

	/** @var ParserData */
	protected $parserData;

	/** @var MessageFormatter */
	protected $msgFormatter;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param MessageFormatter $msgFormatter
	 */
	public function __construct( ParserData $parserData, MessageFormatter $msgFormatter ) {
		$this->parserData = $parserData;
		$this->msgFormatter = $msgFormatter;
	}

	/**
	 * Returns RDF link
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 *
	 * @return string
	 */
	protected function getRDFLink( Title $title ) {
		return SMWInfolink::newInternalLink(
			wfMessage( 'smw_viewasrdf' )->inContentLanguage()->text(),
			$title->getPageLanguage()->getNsText( NS_SPECIAL ) . ':ExportRDF/' . $title->getPrefixedText(), 'rdflink'
		);
	}

	/**
	 * Returns a concept information box as html
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param $queryString
	 * @param $documentation
	 *
	 * @return string
	 */
	protected function getHtml( Title $title, $queryString, $documentation ) {
		return Html::rawElement( 'div', array( 'class' => 'smwfact' ),
			Html::rawElement( 'span', array( 'class' => 'smwfactboxhead' ),
				wfMessage( 'smw_concept_description', $title->getText() )->inContentLanguage()->text() ) .
			Html::rawElement( 'span', array( 'class' => 'smwrdflink' ), $this->getRDFLink( $title )->getWikiText() ) .
			Html::element( 'br', array() ) .
			Html::element( 'p', array(), $documentation ? $documentation : '' ) .
			Html::rawElement( 'pre', array(), str_replace( '[', '&#x005B;', $queryString ) ) .
			Html::element( 'br', array() )
		);
	}

	/**
	 * After some discussion IQueryProcessor/QueryProcessor is not being
	 * used in 1.9 and instead rely on SMWQueryProcessor
	 *
	 * @todo Static class SMWQueryProcessor, please fixme
	 */
	private function initQueryProcessor( array $rawParams, $showMode = false ) {
		list( $this->query, $this->params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			$showMode
		);

		$this->result = SMWQueryProcessor::getResultFromQuery(
			$this->query,
			$this->params,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY
		);
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
		$property = new SMWDIProperty( '_CONC' );

		if ( !( $title->getNamespace() === SMW_NS_CONCEPT ) ) {
			return $this->msgFormatter->addFromKey( 'smw_no_concept_namespace' )->getHtml();
		} elseif ( count( $this->parserData->getData()->getPropertyValues( $property ) ) > 0 ) {
			return $this->msgFormatter->addFromKey( 'smw_multiple_concepts' )->getHtml();
		}

		// Remove parser object from parameters array
		if( isset( $rawParams[0] ) && $rawParams[0] instanceof Parser ) {
			array_shift( $rawParams );
		}

		// Use first parameter as concept (query) string
		$conceptQuery = array_shift( $rawParams );

		// Use second parameter, if any as a description
		$conceptDocu = array_shift( $rawParams );

		// Query processor
		$this->initQueryProcessor( array( $conceptQuery ) );

		$conceptQueryString = $this->query->getDescription()->getQueryString();

		// Store query data to the semantic data instance
		$this->parserData->getData()->addPropertyObjectValue(
			$property,
			new DIConcept(
				$conceptQueryString,
				$conceptDocu,
				$this->query->getDescription()->getQueryFeatures(),
				$this->query->getDescription()->getSize(),
				$this->query->getDescription()->getDepth()
			)
		);

		// Collect possible errors
		$this->msgFormatter->addFromArray( $this->query->getErrors() )->addFromArray( $this->parserData->getErrors() );

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->msgFormatter->exists() ? $this->msgFormatter->getHtml() : $this->getHtml( $title, $conceptQueryString, $conceptDocu );
	}

	/**
	 * Parser::setFunctionHook {{#concept}} handler method
	 *
	 * @since 1.9
	 *
	 * @param Parser $parser
	 *
	 * @return string
	 */
	public static function render( Parser &$parser ) {
		$concept = new self(
			new ParserData( $parser->getTitle(), $parser->getOutput() ),
			new MessageFormatter( $parser->getTargetLanguage() )
		);
		return $concept->parse( func_get_args() );
	}
}
