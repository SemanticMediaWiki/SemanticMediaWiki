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
	public function getResult( $results, $params, $outputmode ) {
		global $smwgUseResultDefaults, $smwgResultDefaults, $smwgAddedResultDefaults;

		if ( !$smwgAddedResultDefaults ) {
			$smwgAddedResultDefaults = true;
			wfRunHooks( 'SMWResultDefaults', array( &$smwgResultDefaults ) );
		}
		
		$format = false;
		
		wfRunHooks( 'SMWResultFormat', array( &$format, $results->getPrintRequests(), $params ) );		

		if ( $smwgUseResultDefaults && !$format && $results->getColumnCount() <= 2 ) {
			$printReqs = $results->getPrintRequests();
			$typeId = array_shift( $printReqs )->getTypeID();
			
			if ( $typeId == '_wpg' ) {
				$typeId = array_shift( $printReqs )->getTypeID();
			}

			if ( $typeId !== false && array_key_exists( $typeId, $smwgResultDefaults ) ) {
				$format = $smwgResultDefaults[$typeId];
			}
		}

		if ( $format === false ) {
			$format = $results->getColumnCount() > 1 ? 'table' : 'list';
		}
		
		$printer = SMWQueryProcessor::getResultPrinter(
			$format,
			$this->mInline ? SMWQueryProcessor::INLINE_QUERY : SMWQueryProcessor::SPECIAL_PAGE
		);
		
		return $printer->getResult( $results, $params, $outputmode );
	}

	protected function getResultText( $res, $outputmode ) {
		return ''; // acutally not needed in this implementation
	}

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_auto' );
	}

}