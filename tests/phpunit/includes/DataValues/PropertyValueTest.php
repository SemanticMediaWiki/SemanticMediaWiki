<?php

namespace SMW\Tests\DataValues;

use SMW\Options;
use SMWPropertyValue as PropertyValue;

/**
 * @covers \SMWPropertyValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWPropertyValue',
			new PropertyValue( '__pro' )
		);
	}

	/**
	 * @dataProvider optionsProvider
	 */
	public function testOptions( $options, $expected ) {

		$instance = new PropertyValue( '__pro' );

		$instance->setOptions(
			new Options( [ 'smwgDVFeatures' => $options ] )
		);

		$this->assertEquals(
			$expected,
			$instance->getOption( 'smwgDVFeatures' )
		);
	}

	public function optionsProvider() {

		$provider[] = [
			SMW_DV_PROV_REDI,
			true
		];

		$provider[] = [
			SMW_DV_NONE | SMW_DV_PROV_REDI,
			true
		];

		$provider[] = [
			SMW_DV_NONE,
			false
		];

		$provider[] = [
			false,
			false
		];

		return $provider;
	}

}
