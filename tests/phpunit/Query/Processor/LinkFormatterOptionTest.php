<?php

namespace SMW\Tests\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\LinkFormatterOption;

/**
 * @covers \SMW\Query\Processor\LinkFormatterOption
 */
class LinkFormatterOptionTest extends TestCase {

	private $formatter;

	protected function setUp(): void {
		$this->formatter = new LinkFormatterOption();
	}

	/**
	 * Test the getPrintRequestWithOutputMarker method
	 */
	public function testGetPrintRequestWithOutputMarkerWithHashInLabel() {
		// Test case 1: Previous printout exists, with '#' in the label
		$serialization = [
			'printouts' => [
				'Main Image' => [
					'label' => 'Main Image #40px=',
					'params' => ['width' => '40px'],
					'mainLabel' => 'Main Image='
				],
			],
		];
		$result = $this->formatter->getPrintRequestWithOutputMarker( '+link=', 'Main Image', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Main Image' => [
					'label' => 'Main Image #40px;link=',
					'params' => [ 
						'width' => '40px',
						'link' => '' 
					],
					'mainLabel' => 'Main Image='
				],
			],
		];
		$this->assertSame( $expectedSerialization, $result['serialization'] );
	}

	/**
	 * Test the getPrintRequestWithOutputMarker method
	 */
	public function testGetPrintRequestWithOutputMarkerWithoutHashInLabel() {
		// Test case 2: Previous printout exists, without '#' in the label
		$serialization = [
			'printouts' => [
				'Job Title' => [
					'label' => 'Job Title'
				],
			],
		];
		$result = $this->formatter->getPrintRequestWithOutputMarker( '+link=', 'Job Title', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Job Title' => [
					'label' => 'Job Title #link',
					'params' => [ 'link' => '' ],
					'mainLabel' => ''
				],
			],
		];
		$this->assertSame( $expectedSerialization, $result['serialization'] );
	}

	/**
	 * Test the getPrintRequestWithOutputMarker method
	 */
	public function testGetPrintRequestWithOutputMarkerWithMultipleParameters() {
		// Test case 3: Previous printout exists, without '#' in the label, more then 3 params in query
		$serialization = [
			'printouts' => [
				'Image' => [
					'label' => 'Main Image #120x120px;thclass=',
					'params' => [
						'width' => '120px',
						'height' => '120px',
						'thclass' => 'unsortable'
					],
					'mainLabel' => 'Main Image='
				],
			],
		];
		$result = $this->formatter->getPrintRequestWithOutputMarker( '+link=', 'Image', $serialization );

		$expectedSerialization = [
			'printouts' => [
				'Image' => [
					'label' => 'Main Image #120x120px;thclass;link=',
					'params' => [
						'width' => '120px',
						'height' => '120px',
						'thclass' => 'unsortable',
						'link' => ''
					],
					'mainLabel' => 'Main Image='
				],
			],
		];
		$this->assertSame( $expectedSerialization, $result['serialization'] );
	}
}
