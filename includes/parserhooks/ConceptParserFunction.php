<?php

namespace SMW;

use SMWInfolink;
use SMWQueryProcessor;

use Parser;
use Html;
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

		$this->parserData->pushSemanticDataToParserOutput();

		if ( $this->messageFormatter->exists() ) {
			return $this->messageFormatter->getHtml();
		}

		return $this->buildConceptInfoBox( $title, $conceptQueryString, $conceptDocu );
	}

	private function buildConceptInfoBox( Title $title, $queryString, $documentation ) {
		return Html::rawElement( 'div', array( 'class' => 'smwfact' ),
			Html::rawElement( 'span', array( 'class' => 'smwfactboxhead' ),
				wfMessage( 'smw_concept_description', $title->getText() )->inContentLanguage()->text() ) .
			Html::rawElement( 'span', array( 'class' => 'smwrdflink' ), $this->getRdfLink( $title )->getWikiText() ) .
			Html::element( 'br', array() ) .
			Html::element( 'p', array( 'class' => 'concept-documenation' ), $documentation ? $documentation : '' ) .
			Html::rawElement( 'pre', array(), str_replace( '[', '&#x005B;', $queryString ) ) .
			Html::element( 'br', array() )
		);
	}

	private function getRdfLink( Title $title ) {
		return SMWInfolink::newInternalLink(
			wfMessage( 'smw_viewasrdf' )->inContentLanguage()->text(),
			$title->getPageLanguage()->getNsText( NS_SPECIAL ) . ':ExportRDF/' . $title->getPrefixedText(), 'rdflink'
		);
	}

	private function buildQuery( $conceptQueryString ) {
		$rawParams = array( $conceptQueryString );

		list( $query, ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::CONCEPT_DESC,
			false
		);

		return $query;
	}

}
