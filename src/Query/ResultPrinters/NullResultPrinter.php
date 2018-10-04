<?php

namespace SMW\Query\ResultPrinters;

use SMWQueryResult as QueryResult;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NullResultPrinter extends ResultPrinter {

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return 'null';
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $queryResult, $outputMode ) {
		return '';
	}

}
