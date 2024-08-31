<?php

namespace SMW\Tests;

use MediaWikiPHPUnitResultPrinter;
use PHPUnit\Framework\TestSuite;

class PHPUnitResultPrinter extends MediaWikiPHPUnitResultPrinter {

	/**
	 * @see TestSuite::endTestSuite
	 */
	public function endTestSuite( TestSuite $suite ): void {
		parent::endTestSuite( $suite );

		$slowTestsReport = ExecutionTimeTestListener::getSlowTestsReport( $suite );
		if ( $slowTestsReport === null ) {
			return;
		}

		$i = 0;

		$this->write( "\n\n" );
		$this->write( '--- Report (slow tests) ' . sprintf( "%'-56s", '' ) );
		$this->write( "\n\n" );

		$this->write( sprintf( "%-20s%s\n", 'Slow test(s) count:', count( $slowTestsReport['slowTests'] ) ) );
		$this->write( sprintf( "%-20s%s\n", 'Threshold (in s):', $slowTestsReport['slowThreshold'] ) );

		foreach ( $slowTestsReport['slowTests'] as $label => $time ) {
			$length = strlen( $label );
			$startOff = 32;
			$endOff = 32;

			if ( $length > 65 ) {
				$label = substr( $label, 0, $startOff ) . ' ... ' . substr( $label, $length - $endOff );
			}

			$i++;
			$this->write( sprintf( "\n%-73s%ss", "- $label", $time ) );
		}

		$this->write( "\n\n" );
		$this->write( sprintf( "%'-80s", '' ) );
	}
}
