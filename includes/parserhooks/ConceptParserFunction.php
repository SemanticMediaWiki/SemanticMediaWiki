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
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup ParserFunction
 *
 * @licence GNU GPL v2+
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * Class that provides the {{#concept}} parser function
 *
 * @ingroup SMW
 * @ingroup ParserFunction
 */
class ConceptParserFunction {

	/**
	 * Represents IParserData object
	 * @var IParserData
	 */
	protected $parserData;

	/**
	 * @since 1.9
	 *
	 * @param IParserData $parserData
	 */
	public function __construct( IParserData $parserData ) {
		$this->parserData = $parserData;
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
			return smwfEncodeMessages( array( wfMessage( 'smw_no_concept_namespace' )->inContentLanguage()->text() ) );
		} elseif ( count( $this->parserData->getData()->getPropertyValues( $property ) ) > 0 ) {
			return smwfEncodeMessages( array( wfMessage( 'smw_multiple_concepts' )->inContentLanguage()->text() ) );
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

		// Handling errors from the query
		$this->parserData->addError( $this->query->getErrors() );

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->parserData->hasError() ? $this->parserData->getReport() : $this->getHtml( $title, $conceptQueryString, $conceptDocu );
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
		$instance = new self( new ParserData(
			$parser->getTitle(),
			$parser->getOutput() )
		);
		return $instance->parse( func_get_args() );
	}
}
