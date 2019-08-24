<?php

namespace SMW\Tests;

use PHPUnit_TextUI_ResultPrinter;
use PHPUnit_Framework_TestSuite;

class PHPUnitResultPrinter extends PHPUnit_TextUI_ResultPrinter {

	/**
	 * @see PHPUnit_TextUI_ResultPrinter::endTestSuite
	 */
	public function endTestSuite( PHPUnit_Framework_TestSuite $suite ) {
		parent::endTestSuite( $suite );

		if ( !isset( $suite->_slowTestsReport ) ) {
			return;
		}

		$slowTestsReport = $suite->_slowTestsReport;
		$i = 0;

		$this->write( "\n\n" );
		$this->writeWithColor( 'fg-black, bg-yellow', "Slow test report" );
		$this->write( "\n" );

		$this->write( sprintf( "%-20s%s\n", 'Slow test(s) count:', count( $slowTestsReport['slowTests'] ) ) );
		$this->write( sprintf( "%-20s%s\n", 'Threshold (in s):', $slowTestsReport['slowThreshold'] ) );

		$this->write( "\nList of slow test(s) ..." );

		foreach ( $slowTestsReport['slowTests'] as $label => $time ) {
			$length = strlen( $label );
			$startOff = 25;
			$endOff = 32;

			if ( $length > 60 ) {
				$label = substr( $label, 0, $startOff ) . ' ... ' . substr( $label, $length - $endOff );
			}

			$i++;
			$this->write( sprintf("\n%-73s%ss", "   ... $label", $time ) );
		}
	}

}