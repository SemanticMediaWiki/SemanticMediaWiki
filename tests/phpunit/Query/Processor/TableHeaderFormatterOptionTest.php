<?php

namespace SMW\Tests\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\TableHeaderFormatterOption;

/**
 * @covers \SMW\Query\Processor\TableHeaderFormatterOption
 */
class TableHeaderFormatterOptionTest extends TestCase {

	/**
	 * Test the getPrintRequestWithOutputMarker method
	 */
	public function testGetPrintRequestWithOutputMarker() {
		$formatter = new TableHeaderFormatterOption();

		// Test case 1: Previous printout exists, with '#' in the label
		$serialization = [
			'printouts' => [
				'Main Image' => [
					'label' => 'Main Image #40px'
				],
			],
		];
		$result = $formatter->getPrintRequestWithOutputMarker( '+thclass=unsortable', 'Main Image', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Main Image' => [
					'label' => 'Main Image #40px;thclass',
					'params' => [ 'thclass' => 'unsortable' ]
				],
			],
		];
		$this->assertSame( $expectedSerialization, $result['serialization'] );

		// Test case 2: Previous printout exists, without '#' in the label
		$serialization = [
			'printouts' => [
				'Job Title' => [
					'label' => 'Job Title=Job Title='
				],
			],
		];
		$result = $formatter->getPrintRequestWithOutputMarker( '+thclass=unsortable', 'Job Title', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Job Title' => [
					'label' => 'Job Title #thclass=Job Title',
					'params' => [ 'thclass' => 'unsortable' ]
				],
			],
		];
		$this->assertSame( $expectedSerialization, $result['serialization'] );

		// Test case 3: Previous printout exists, without '#' in the label, more then 3 params in query
		$serialization = [
			'printouts' => [
				'Image' => [
					'label' => 'Image #40x50px;link'
				],
			],
		];
		$result = $formatter->getPrintRequestWithOutputMarker( '+thclass=unsortable', 'Image', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Image' => [
					'label' => 'Image #40x50px;link;thclass',
					'params' => [ 'thclass' => 'unsortable' ]
				],
			],
		];
		$this->assertSame( $expectedSerialization, $result['serialization'] );
	}
}
