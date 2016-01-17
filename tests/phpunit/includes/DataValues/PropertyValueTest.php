<?php

namespace SMW\Tests\DataValues;

use SMW\DIProperty;
use SMWPropertyValue as PropertyValue;
use SMW\Options;

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
	public function testNeedsToFindPropertyRedirectOrNotByOption( $options, $expected ) {

		$instance = new PropertyValue( '__pro' );

		$instance->setOptions(
			new Options( array( 'smwgDVFeatures' => $options ) )
		);

		$this->assertEquals(
			$expected,
			$instance->isToFindPropertyRedirect()
		);
	}

	public function optionsProvider() {

		$provider[] = array(
			SMW_DV_PROV_REDI,
			true
		);

		$provider[] = array(
			SMW_DV_NONE | SMW_DV_PROV_REDI,
			true
		);

		$provider[] = array(
			SMW_DV_NONE,
			false
		);

		$provider[] = array(
			false,
			false
		);

		return $provider;
	}

}
