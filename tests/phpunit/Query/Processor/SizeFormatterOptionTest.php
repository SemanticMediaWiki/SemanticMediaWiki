<?php

namespace SMW\Tests\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\SizeFormatterOption;

class SizeFormatterOptionTest extends TestCase {
	private const MAIN_IMAGE = 'Main Image';

	/**
	 * @dataProvider sizeParameterProvider
	 */
	public function testAddPrintRequestHandleParams( string $parameter, string $value, string $expectedLabel, array $expectedParams ) {
		$formatter = new SizeFormatterOption();

		$serialization = [
			'printouts' => [
				self::MAIN_IMAGE => [
					'label' => self::MAIN_IMAGE
				],
			],
		];

		$result = $formatter->addPrintRequestHandleParams(
			self::MAIN_IMAGE,
			"+{$parameter}={$value}",
			self::MAIN_IMAGE,
			$serialization
		);

		$this->assertArrayHasKey( self::MAIN_IMAGE, $result['serialization']['printouts'] );
		$this->assertEquals( $expectedLabel, $result['serialization']['printouts'][self::MAIN_IMAGE]['label'] );
		$this->assertEquals( $expectedParams, $result['serialization']['printouts'][self::MAIN_IMAGE]['params'] );
	}

	public function sizeParameterProvider(): array
	{
		return [
			'width parameter' => [ 'width', '50px', self::MAIN_IMAGE . ' #50px', [ 'width' => '50px' ] ],
			'height parameter' => [ 'height', '90px', self::MAIN_IMAGE . ' #x90px', [ 'height' => '90px' ] ],
		];
	}
}
