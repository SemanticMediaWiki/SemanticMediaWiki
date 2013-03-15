<?php

/**
 * @since 1.5.3
 * @file
 * @ingroup SMW
 * @ingroup ParserHooks
 */

/**
 * Class for the 'concept' parser functions.
 * @see http://semantic-mediawiki.org/wiki/Help:Concepts
 *
 * @since 1.5.3
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWConcept {

	/**
	 * Method for handling the ask concept function.
	 *
	 * @todo The possible use of this in an HTML or Special page context
	 * needs to be revisited. The code mentions it, but can this actually
	 * happen?
	 * @todo The escaping of symbols in concept queries needs to be
	 * revisited.
	 *
	 * @since 1.5.3
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		global $wgContLang, $wgTitle;

		$title = $parser->getTitle();
		$pconc = new SMWDIProperty( '_CONC' );

		if ( $title->getNamespace() != SMW_NS_CONCEPT ) {
			$result = smwfEncodeMessages( array( wfMessage( 'smw_no_concept_namespace' )->inContentLanguage()->text() ) );
			SMWOutputs::commitToParser( $parser );
			return $result;
		} elseif ( count( SMWParseData::getSMWdata( $parser )->getPropertyValues( $pconc ) ) > 0 ) {
			$result = smwfEncodeMessages( array( wfMessage( 'smw_multiple_concepts' )->inContentLanguage()->text() ) );
			SMWOutputs::commitToParser( $parser );
			return $result;
		}

		// process input:
		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		// Use first parameter as concept (query) string.
		$concept_input = str_replace( array( '&gt;', '&lt;' ), array( '>', '<' ), array_shift( $params ) );

		// second parameter, if any, might be a description
		$concept_docu = array_shift( $params );

		// NOTE: the str_replace above is required in MediaWiki 1.11, but not in MediaWiki 1.14
		$query = SMWQueryProcessor::createQuery(
			$concept_input,
			SMWQueryProcessor::getProcessedParams( array( 'limit' => 20, 'format' => 'list' ) ),
			SMWQueryProcessor::CONCEPT_DESC
		);

		$concept_text = $query->getDescription()->getQueryString();

		if ( !is_null( SMWParseData::getSMWData( $parser ) ) ) {
			$diConcept = new SMWDIConcept( $concept_text, $concept_docu, $query->getDescription()->getQueryFeatures(), $query->getDescription()->getSize(), $query->getDescription()->getDepth() );
			SMWParseData::getSMWData( $parser )->addPropertyObjectValue( $pconc, $diConcept );
		}

		// display concept box:
		$rdflink = SMWInfolink::newInternalLink(
			wfMessage( 'smw_viewasrdf' )->inContentLanguage()->text(),
			$wgContLang->getNsText( NS_SPECIAL ) . ':ExportRDF/' . $title->getPrefixedText(), 'rdflink'
		);
		SMWOutputs::requireResource( 'ext.smw.style' );

		// TODO: escape output, preferably via Html or Xml class.
		$result = '<div class="smwfact"><span class="smwfactboxhead">' .
			wfMessage( 'smw_concept_description', $title->getText() )->inContentLanguage()->text() .
			( count( $query->getErrors() ) > 0 ? ' ' . smwfEncodeMessages( $query->getErrors() ) : '' ) .
			'</span>' . '<span class="smwrdflink">' . $rdflink->getWikiText() . '</span>' . '<br />' .
			( $concept_docu ? "<p>$concept_docu</p>" : '' ) .
			'<pre>' . str_replace( '[', '&#x005B;', $concept_text ) . "</pre>\n</div>";

		if ( !is_null( $wgTitle ) && $wgTitle->isSpecialPage() ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		} else {
			SMWOutputs::commitToParser( $parser );
		}

		return $result;
	}

}
