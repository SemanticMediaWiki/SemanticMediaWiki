<?php

namespace SMW\Query\ResultPrinters;

use SMW\Query\QueryResult;

/**
 * @license GPL-2.0-or-later
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
