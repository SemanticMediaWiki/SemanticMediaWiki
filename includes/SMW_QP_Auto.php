<?php
/**
 * Print query results in tables or lists, depending on their shape.
 * This implements the automatic printer selection used in SMW if no
 * query format is specified.
 * 
 * @file
 * @ingroup SMWQuery
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */

/**
 * New implementation of SMW's printer for automatically selecting the
 * format for printing a result.
 *
 * @ingroup SMWQuery
 */
class SMWAutoResultPrinter extends SMWResultPrinter {

	/**
	 * @see SMWResultPrinter::getResult
	 * 
	 * @param SMWQueryResult $results
	 * @param array $params
	 * @param $outputmode
	 * 
	 * @return string
	 */
	public function getResult( /* SMWQueryResult */ $results, $params, $outputmode ) {
		global $smwgUseResultDefaults, $smwgResultDefaults, $smwgAddedResultDefaults;

		if ( !$smwgAddedResultDefaults ) {
			$smwgAddedResultDefaults = true;
			
			/**
			 * This hook allows extensions to specify default result formats for
			 * datavalues. For example, Semantic Maps can specify the result format
			 * for geographical coordinates is the map one. 
			 * 
			 * @since 1.5.2
			 */ 
			wfRunHooks( 'SMWResultDefaults', array( &$smwgResultDefaults ) );
		}
		
		$format = false;
		
		/**
		 * This hook allows extensions to override SMWs implementation of default result
		 * format handling. Extensions might choose do so even when $smwgUseResultDefaults is false.
		 * 
		 * @since 1.5.2
		 */
		wfRunHooks( 'SMWResultFormat', array( &$format, $results->getPrintRequests(), $params ) );		

		// If $smwgUseResultDefaults is true, and there are no multiple columns, see if there is a default format.
		if ( $smwgUseResultDefaults && $format === false && $results->getColumnCount() <= 2 ) {
			$printReqs = $results->getPrintRequests();
			$typeId = array_shift( $printReqs )->getTypeID();
			
			if ( $typeId == '_wpg' ) {
				$typeId = array_shift( $printReqs )->getTypeID();
			}

			if ( $typeId !== false && array_key_exists( $typeId, $smwgResultDefaults ) ) {
				$format = $smwgResultDefaults[$typeId];
			}
		}

		// If no default was found, use a table or list, depending on the column count.
		if ( $format === false ) {
			$format = $results->getColumnCount() > 1 ? 'table' : 'list';
		}
		
		$printer = SMWQueryProcessor::getResultPrinter(
			$format,
			$this->mInline ? SMWQueryProcessor::INLINE_QUERY : SMWQueryProcessor::SPECIAL_PAGE
		);
		
		return $printer->getResult( $results, $params, $outputmode );
	}

	/**
	 * @see SMWResultPrinter::getResultText
	 * 
	 * @param SMWQueryResult $res
	 * @param $outputmode
	 */
	protected function getResultText( /* SMWQueryResult */ $res, $outputmode ) {
		return ''; // acutally not needed in this implementation
	}

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_auto' );
	}

}