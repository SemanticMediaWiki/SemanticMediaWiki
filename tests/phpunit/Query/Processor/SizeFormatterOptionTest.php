<?php

namespace SMW\Tests\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\SizeFormatterOption;

/**
 * @covers \SMW\Query\Processor\SizeFormatterOption
 */
class SizeFormatterOptionTest extends TestCase {
	private const MAIN_IMAGE = 'Main Image';
	private $formatter;
	private $serialization;

	protected function setUp(): void {
		$this->formatter = new SizeFormatterOption();

		$this->serialization = [
			'printouts' => [
				self::MAIN_IMAGE => [
					'label' => self::MAIN_IMAGE
				],
			],
		];
	}

	public function testGetPrintRequestWithOutputMarkerWidthHeightCombined() {
		$result = $this->formatter->getPrintRequestWithOutputMarker(
			"+width=30px",
			self::MAIN_IMAGE,
			$this->serialization
		);

		$this->assertArrayHasKey(self::MAIN_IMAGE, $result['serialization']['printouts']);
		$this->assertSame('Main Image #30px', $result['serialization']['printouts'][self::MAIN_IMAGE]['label']);
		$this->assertSame(['width' => '30px'], $result['serialization']['printouts'][self::MAIN_IMAGE]['params']);

		$result = $this->formatter->getPrintRequestWithOutputMarker(
			"+height=60px",
			self::MAIN_IMAGE,
			$result['serialization']
		);

		$this->assertArrayHasKey(self::MAIN_IMAGE, $result['serialization']['printouts']);
		$this->assertSame('Main Image #30x60px', $result['serialization']['printouts'][self::MAIN_IMAGE]['label']);
		$this->assertSame(['width' => '30px', 'height' => '60px'], $result['serialization']['printouts'][self::MAIN_IMAGE]['params']);
	}

	/**
	 * @dataProvider sizeParameterProvider
	 */
	public function testGetPrintRequestWithOutputMarker( string $parameter, string $value, string $expectedLabel, array $expectedParams ) {
		$result = $this->formatter->getPrintRequestWithOutputMarker(
			"+{$parameter}={$value}",
			self::MAIN_IMAGE,
			$this->serialization
		);

		$this->assertArrayHasKey( self::MAIN_IMAGE, $result['serialization']['printouts'] );
		$this->assertSame( $expectedLabel, $result['serialization']['printouts'][self::MAIN_IMAGE]['label'] );
		$this->assertSame( $expectedParams, $result['serialization']['printouts'][self::MAIN_IMAGE]['params'] );
	}

	public function sizeParameterProvider(): array {
		return [
			'width parameter' => [ 'width', '50px', 'Main Image #50px', [ 'width' => '50px' ] ],
			'height parameter' => [ 'height', '90px', 'Main Image #x90px', [ 'height' => '90px' ] ]
		];
	}
}
