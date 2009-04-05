<?php
/**
 * Print query results in tables or lists, depending on their shape.
 * This implements the automatic printer selection used in SMW if no
 * query format is specified.
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

/**
 * New implementation of SMW's printer for automatically selecting the
 * format for printing a result.
 *
 * @ingroup SMWQuery
 */
class SMWAutoResultPrinter extends SMWResultPrinter {

	public function getResult($results, $params, $outputmode) {
		if ( ($results->getColumnCount()>1) && ($results->getColumnCount()>0) ) {
			$format = 'table';
		} else {
			$format = 'list';
		}
		$printer = SMWQueryProcessor::getResultPrinter($format, ($this->mInline?SMWQueryProcessor::INLINE_QUERY:SMWQueryProcessor::SPECIAL_PAGE));
		return $printer->getResult($results, $params, $outputmode);
	}

	protected function getResultText($res, $outputmode) {
		return ''; // acutally not needed in this implementation
	}

	public function getName() {
		wfLoadExtensionMessages('SemanticMediaWiki');
		return wfMsg('smw_printername_auto');
	}

}