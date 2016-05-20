<?php

namespace SMW\Tests\MediaWiki\Specials\SearchByProperty;

use SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions;

/**
 * @covers \SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PageRequestOptionsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$queryString = '';
		$requestOptions = array();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions',
			new PageRequestOptions( $queryString, $requestOptions )
		);
	}

	/**
	 * @dataProvider pageRequestOptionsProvider
	 */
	public function testProcess( $queryString, $requestOptions, $expected ) {

		$instance = new PageRequestOptions( $queryString, $requestOptions );
		$instance->initialize();

		foreach ( $expected as $key => $value ) {
			$this->assertEquals( $expected[$key], $instance->$key, "$key" );
		}

		$this->assertInstanceOf(
			'SMWPropertyValue',
			$instance->property
		);
	}

	public function pageRequestOptionsProvider() {

		#0
		$provider[] = array(
			'',
			array(),
			array(
				'limit'  => 20,
				'offset' => 0,
				'propertyString' => '',
				'valueString' => '',
				'value' => null
			)
		);

		#1
		$provider[] = array(
			'Foo',
			array(),
			array(
				'limit'  => 20,
				'offset' => 0,
				'propertyString' => 'Foo',
				'valueString'    => '',
			)
		);

		#2
		$provider[] = array(
			'Foo_nu/Bar',
			array(),
			array(
				'limit'  => 20,
				'offset' => 0,
				'propertyString' => 'Foo nu',
				'valueString'    => 'Bar',
				'nearbySearch'   => false
			)
		);

		#3 @see 516
		$provider[] = array(
			'Foo("#^$&--2F)/("#^$&-)Bar',
			array(),
			array(
				'limit'  => 20,
				'offset' => 0,
				'propertyString' => 'Foo("#^$&-/)',
				'valueString'    => '("#^$&-)Bar',
				'nearbySearch'   => false
			)
		);

		#4
		$provider[] = array(
			'Foo("#^$&--2F)/("#^$&-)Bar',
			array(
				'property' => '("#^$&--2F)李秀英',
				'value'    => '田中("#^$&-)',
				'nearbySearchForType' => true
			),
			array(
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => '("#^$&-/)李秀英',
				'valueString'    => '田中("#^$&-)',
			)
		);

		#5
		$provider[] = array(
			'',
			array(
				'property' => ' Foo ',
				'value'    => '',
				'nearbySearchForType' => array( '_txt' )
			),
			array(
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => 'Foo',
				'valueString'    => '',
			)
		);

		#6
		$provider[] = array(
			'',
			array(
				'property' => 'Foo',
				'value'    => '',
				'nearbySearchForType' => array( '_wpg' )
			),
			array(
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => true,
				'propertyString' => 'Foo',
				'valueString'    => '',
			)
		);

		#7
		$provider[] = array(
			'',
			array(
				'property' => '',
				'value'    => 'Foo',
				'nearbySearchForType' => array( '_wpg' )
			),
			array(
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => '',
				'valueString'    => 'Foo',
			)
		);

		#9
		$provider[] = array(
			'',
			array(
				'property' => 'Number',
				'value'    => '2',
				'nearbySearchForType' => array( '_wpg' )
			),
			array(
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => 'Number',
				'valueString'    => '2.0',
			)
		);

		#10
		$provider[] = array(
			'',
			array(
				'property' => 'Temperature',
				'value'    => '373,15-20K',
				'nearbySearchForType' => array( '_wpg' )
			),
			array(
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => 'Temperature',
				'valueString'    => '373,15 K',
			)
		);

		#11
		$provider[] = array(
			'',
			array(
				'property' => 'Telephone number',
				'value'    => '%2B1-2D201-2D555-2D0123',
				'nearbySearchForType' => array( '_tel' )
			),
			array(
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => true,
				'propertyString' => 'Telephone number',
				'valueString'    => '%2B1-201-555-0123',
			)
		);

		return $provider;
	}

}
