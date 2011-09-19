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

	protected $format = false;
	
	/**
	 * @see SMWResultPrinter::getResult
	 * 
	 * @param $results SMWQueryResult
	 * @param $params array
	 * @param $outputmode integer
	 * 
	 * @return string
	 */
	public function getResult( SMWQueryResult $results, array $params, $outputmode ) {
		$this->determineFormat( $results, $params );
		
		$printer = SMWQueryProcessor::getResultPrinter(
			$this->format,
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
	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		return ''; // acutally not needed in this implementation
	}

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_auto' );
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SMWResultPrinter::getParameters()
	 * 
	 * To work correctly as of 1.6.2, you need to call determineFormat first. 
	 */
	public function getParameters() {
		$printer = SMWQueryProcessor::getResultPrinter(
			$this->format,
			$this->mInline ? SMWQueryProcessor::INLINE_QUERY : SMWQueryProcessor::SPECIAL_PAGE
		);
		
		return $printer->getParameters();
	}
	
	/**
	 * Determine the format, based on the result and provided parameters.
	 * 
	 * @since 1.6.2
	 * 
	 * @param SMWQueryResult $results
	 * @param array $params
	 * 
	 * @return string
	 */
	public function determineFormat( SMWQueryResult $results = null, array $params = null ) {
		if ( $this->format === false ) {
			if ( is_null( $results ) || is_null( $params ) ) {
				$this->format = 'table';
			}
			else {

			}
		}
		
		return $this->format;
	}

}
