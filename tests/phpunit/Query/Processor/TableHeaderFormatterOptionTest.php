<?php

namespace SMW\Tests\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\TableHeaderFormatterOption;

class TableHeaderFormatterOptionTest extends TestCase {

	/**
	 * Test the addPrintRequestHandleParams method
	 */
	public function testAddPrintRequestHandleParams() {
		$formatter = new TableHeaderFormatterOption();

		// Test case 1: Previous printout exists, with '#' in the label
		$serialization = [
			'printouts' => [
				'Main Image' => [
					'label' => 'Main Image #40px'
				],
			],
		];
		$result = $formatter->addPrintRequestHandleParams( 'Main Image', '+thclass=unsortable', 'Main Image', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Main Image' => [
					'label' => 'Main Image #40px;classunsortable',
					'params' => []
				],
			],
		];
		$this->assertEquals( $expectedSerialization, $result['serialization'] );

		// Test case 2: Previous printout exists, without '#' in the label
		$serialization = [
			'printouts' => [
				'Job Title' => [
					'label' => 'Job Title'
				],
			],
		];
		$result = $formatter->addPrintRequestHandleParams( 'Job Title', '+thclass=unsortable', 'Job Title', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Job Title' => [
					'label' => 'Job Title #classunsortable',
					'params' => []
				],
			],
		];
		$this->assertEquals( $expectedSerialization, $result['serialization'] );

		// Test case 3: Previous printout exists, without '#' in the label, more then 3 params in query
		$serialization = [
			'printouts' => [
				'Image' => [
					'label' => 'Image #40x50px;link'
				],
			],
		];
		$result = $formatter->addPrintRequestHandleParams( 'Image', '+thclass=unsortable', 'Image', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Image' => [
					'label' => 'Image #40x50px;link;classunsortable',
					'params' => []
				],
			],
		];
		$this->assertEquals( $expectedSerialization, $result['serialization'] );
	}
}
