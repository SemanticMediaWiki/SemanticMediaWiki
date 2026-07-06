<?php

namespace SMW\Tests\Unit\Query\ResultPrinters;

use ParamProcessor\ParamDefinition;
use PHPUnit\Framework\TestCase;
use SMW\Query\QueryProcessor;
use SMW\Query\ResultPrinters\ResultPrinter;
use SMW\Query\ResultPrinters\TableResultPrinter;
use SMW\Services\ServicesFactory;
use SMW\Settings;

/**
 * @covers \SMW\Query\ResultPrinters\ResultPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ResultPrintersTest extends TestCase {

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $format, $class, $isInline ) {
		$instance = new $class( $format, $isInline );

		$this->assertInstanceOf(
			\SMW\Query\ResultPrinter::class,
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

	/**
	 * `ResultPrinter::isEnabledFeature` must read $smwgResultFormatsFeatures via
	 * Settings, not directly via $GLOBALS. A direct read sees the unnormalized
	 * user value, which for the new string form `'template-outsep'` would coerce
	 * to int 0 and silently disable the feature for everyone who migrates to
	 * the documented string form (#6586).
	 *
	 * This test sets $GLOBALS to the string form (bug scenario) and stubs
	 * ApplicationFactory's Settings to the post-normalization integer. With a
	 * correct fix (read via Settings) the printer reports the feature enabled.
	 * With the regression (read via $GLOBALS direct) it would report disabled.
	 */
	public function testIsEnabledFeature_readsViaSettingsNotGlobals() {
		$saved = $GLOBALS['smwgResultFormatsFeatures'] ?? null;
		$servicesFactory = ServicesFactory::getInstance();
		$savedSettings = $servicesFactory->getSettings();

		try {
			$GLOBALS['smwgResultFormatsFeatures'] = 'template-outsep';
			$servicesFactory->registerObject(
				'Settings',
				Settings::newFromArray( [ 'smwgResultFormatsFeatures' => SMW_RF_TEMPLATE_OUTSEP ] )
			);

			$printer = new TableResultPrinter( 'table', true );
			$this->assertTrue(
				$printer->isEnabledFeature( SMW_RF_TEMPLATE_OUTSEP ),
				'Expected isEnabledFeature to honour the post-normalization Settings value, '
				. 'not the unnormalized $GLOBALS string. A regression here means every admin '
				. 'who adopts the new string form gets the feature silently disabled.'
			);
		} finally {
			$servicesFactory->registerObject( 'Settings', $savedSettings );
			if ( $saved !== null ) {
				$GLOBALS['smwgResultFormatsFeatures'] = $saved;
			}
		}
	}

}
