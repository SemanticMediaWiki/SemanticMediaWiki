<?php
/**
 * @file
 * @since 1.5.3
 * @ingroup SMW
 * @ingroup ParserHooks
 */

/**
 * Class for the 'ask' parser functions.
 * @see http://semantic-mediawiki.org/wiki/Help:Inline_queries#Introduction_to_.23ask
 *
 * @since 1.5.3
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWAsk {

	/**
	 * Method for handling the ask parser function.
	 *
	 * @since 1.5.3
	 *
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		global $smwgQEnabled, $smwgIQRunningNumber, $wgTitle;

		if ( $smwgQEnabled ) {
			$smwgIQRunningNumber++;

			$rawParams = func_get_args();
			array_shift( $rawParams ); // We already know the $parser ...

			list( $query, $params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams( $rawParams, SMW_OUTPUT_WIKI, SMWQueryProcessor::INLINE_QUERY, false );

			$result = SMWQueryProcessor::getResultFromQuery( $query, $params, SMW_OUTPUT_WIKI, SMWQueryProcessor::INLINE_QUERY );

			$queryKey = hash( 'md4', implode( '|', $rawParams ) , false );
			self::addQueryData( $queryKey, $query, $params, $parser );
		} else {
			$result = smwfEncodeMessages( array( wfMessage( 'smw_iq_disabled' )->inContentLanguage()->text() ) );
		}

		if ( !is_null( $wgTitle ) && $wgTitle->isSpecialPage() ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		} else {
			SMWOutputs::commitToParser( $parser );
		}

		return $result;
	}

	/**
	 * Add data about the query and its parameters to the semantic data of
	 * the given parser. The $queryKey is a string key that uniquely
	 * identifies the query; this is difficult to create in a stable way
	 * from the processed query object and parameters, but easy to get from
	 * the raw user input.
	 *
	 * @param string $queryKey
	 * @param SMWQuery $query
	 * @param array $params
	 * @param Parser $parser
	 *
	 * @since 1.8
	 */
	public static function addQueryData( $queryKey, SMWQuery $query, array $params, Parser $parser ) {
		$mainSemanticData = SMWParseData::getSMWData( $parser );
		$subject = $mainSemanticData->getSubject();

		$diSubWikiPage = new SMWDIWikiPage( $subject->getDBkey(),
				$subject->getNamespace(), $subject->getInterwiki(),
				"_QUERY" . $queryKey );

		$semanticData = new SMWContainerSemanticData( $diSubWikiPage );

		$description = $query->getDescription();

		// Add query string
		$propertyDi = new SMWDIProperty( '_ASKST' );
		$valueDi = new SMWDIBlob( $description->getQueryString() );
		$semanticData->addPropertyObjectValue( $propertyDi, $valueDi );
		// Add query size
		$propertyDi = new SMWDIProperty( '_ASKSI' );
		$valueDi = new SMWDINumber( $description->getSize() );
		$semanticData->addPropertyObjectValue( $propertyDi, $valueDi );
		// Add query depth
		$propertyDi = new SMWDIProperty( '_ASKDE' );
		$valueDi = new SMWDINumber( $description->getDepth() );
		$semanticData->addPropertyObjectValue( $propertyDi, $valueDi );
		// Add query format
		$propertyDi = new SMWDIProperty( '_ASKFO' );
		$valueDi = new SMWDIString( $params['format']->getValue() );
		$semanticData->addPropertyObjectValue( $propertyDi, $valueDi );

		$propertyDi = new SMWDIProperty( '_ASK' );
		$subObjectDi = new SMWDIContainer( $semanticData );
		SMWParseData::getSMWData( $parser )->addPropertyObjectValue( $propertyDi, $subObjectDi );
	}

}
