<?php

namespace SMW\Tests\Query\ResultPrinters;

use ParamProcessor\ParamDefinition;
use SMW\Query\ResultPrinters\ResultPrinter;
use SMW\Tests\PHPUnitCompat;
use SMWQueryProcessor as QueryProcessor;

/**
 * @covers \SMW\Query\ResultPrinters\ResultPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ResultPrintersTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $format, $class, $isInline ) {
		$instance = new $class( $format, $isInline );

		$this->assertInstanceOf(
			'\SMWIResultPrinter',
			$instance
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetParamDefinitions( ResultPrinter $printer ) {
		$params = $printer->getParamDefinitions(
			QueryProcessor::getParameters( null, $printer )
		);

		$params = ParamDefinition::getCleanDefinitions( $params );

		$this->assertIsArray(

			$params
		);
	}

	public function constructorProvider() {
		global $smwgResultFormats;

		$formats = [];

		foreach ( $smwgResultFormats as $format => $class ) {
			$formats[] = [ $format, $class, true ];
			$formats[] = [ $format, $class, false ];
		}

		return $formats;
	}

	public function instanceProvider() {
		global $smwgResultFormats;

		$instances = [];

		foreach ( $smwgResultFormats as $format => $class ) {
			$instances[] = new $class( $format, true );
		}

		yield $instances;
	}

}
