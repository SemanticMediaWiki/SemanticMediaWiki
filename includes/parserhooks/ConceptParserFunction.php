<?php

namespace SMW;

use Parser;
use Html;
use Title;

use SMWDIProperty;
use SMWInfolink;

/**
 * {{#concept}} parser function
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
 * @ingroup ParserHooks
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * Class that provides the {{#concept}} parser hook function
 *
 * @ingroup SMW
 * @ingroup ParserHooks
 */
class ConceptParserFunction {

	/**
	 * Represents IParserData
	 */
	protected $parserData;

	/**
	 * Represents IQueryProcessor
	 */
	protected $queryProcessor;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param IParserData $parserData
	 * @param IQueryProcessor $queryProcessor
	 */
	public function __construct( IParserData $parserData, IQueryProcessor $queryProcessor ) {
		$this->parserData = $parserData;
		$this->queryProcessor = $queryProcessor;
	}

	/**
	 * Returns RDF link
	 *
	 * @since 1.9
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
	 * Parse parameters and return results to the ParserOutput object
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

		// Extinct parser object from parameters array
		array_shift( $rawParams );

		// Use first parameter as concept (query) string.
		$conceptQuery = array_shift( $rawParams );

		// second parameter, if any, might be a description
		$conceptDocu = array_shift( $rawParams );

		// Query processor
		$this->queryProcessor->map( array( $conceptQuery ) );
		$query = $this->queryProcessor->getQuery();

		$conceptQueryString = $query->getDescription()->getQueryString();

		// Store query data to the semantic data instance
		$this->parserData->getData()->addPropertyObjectValue(
			$property,
			new DIConcept(
				$conceptQueryString,
				$conceptDocu,
				$query->getDescription()->getQueryFeatures(),
				$query->getDescription()->getSize(),
				$query->getDescription()->getDepth()
			)
		);

		// Store query data to the ParserOutput
		$this->parserData->setError( $query->getErrors() );

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->parserData->hasError() ? $this->parserData->getReport() : $this->getHtml( $title, $conceptQueryString, $conceptDocu );
	}

	/**
	 * Method for handling the ask parser function
	 *
	 * @since 1.9
	 *
	 * @param Parser $parser
	 *
	 * @return string
	 */
	public static function render( Parser &$parser ) {
		$instance = new self(
			new ParserData( $parser->getTitle(), $parser->getOutput() ),
			new QueryProcessor( SMW_OUTPUT_WIKI, QueryProcessor::CONCEPT_DESC )
		);
		return $instance->parse( func_get_args() );
	}
}
