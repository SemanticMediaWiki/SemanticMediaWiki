<?php

namespace SMW\Tests\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\LinkFormatterOption;

/**
 * @covers \SMW\Query\Processor\LinkFormatterOption
 */
class LinkFormatterOptionTest extends TestCase {

	/**
	 * Test the getPrintRequestWithOutputMarker method
	 */
	public function testGetPrintRequestWithOutputMarkerWithHashInLabel() {
		$formatter = new LinkFormatterOption();

		// Test case 1: Previous printout exists, with '#' in the label
		$serialization = [
			'printouts' => [
				'Main Image' => [
					'label' => 'Main Image #40px'
				],
			],
		];
		$result = $formatter->getPrintRequestWithOutputMarker( '+link=', 'Main Image', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Main Image' => [
					'label' => 'Main Image #40px;link',
					'params' => [ 'link' => '' ]
				],
			],
		];
		$this->assertSame( $expectedSerialization, $result['serialization'] );
	}

	/**
	 * Test the getPrintRequestWithOutputMarker method
	 */
	public function testGetPrintRequestWithOutputMarkerWithoutHashInLabel() {
		$formatter = new LinkFormatterOption();

		// Test case 2: Previous printout exists, without '#' in the label
		$serialization = [
			'printouts' => [
				'Job Title' => [
					'label' => 'Job Title'
				],
			],
		];
		$result = $formatter->getPrintRequestWithOutputMarker( '+link=', 'Job Title', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Job Title' => [
					'label' => 'Job Title #link',
					'params' => [ 'link' => '' ]
				],
			],
		];
		$this->assertSame( $expectedSerialization, $result['serialization'] );
	}

	/**
	 * Test the getPrintRequestWithOutputMarker method
	 */
	public function testGetPrintRequestWithOutputMarkerWithMultipleParameters() {
		$formatter = new LinkFormatterOption();

		// Test case 3: Previous printout exists, without '#' in the label, more then 3 params in query
		$serialization = [
			'printouts' => [
				'Image' => [
					'label' => 'Image #40x50px;classunsortable'
				],
			],
		];
		$result = $formatter->getPrintRequestWithOutputMarker( '+link=', 'Image', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Image' => [
					'label' => 'Image #40x50px;classunsortable;link',
					'params' => [ 'link' => '' ]
				],
			],
		];
		$this->assertSame( $expectedSerialization, $result['serialization'] );
	}
}
