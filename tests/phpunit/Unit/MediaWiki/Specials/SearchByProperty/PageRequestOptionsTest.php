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
		$requestOptions = [];

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
		$provider[] = [
			'',
			[],
			[
				'limit'  => 20,
				'offset' => 0,
				'propertyString' => '',
				'valueString' => '',
				'value' => null
			]
		];

		#1
		$provider[] = [
			'Foo',
			[],
			[
				'limit'  => 20,
				'offset' => 0,
				'propertyString' => 'Foo',
				'valueString'    => '',
			]
		];

		#2
		$provider[] = [
			'Foo_nu/Bar',
			[],
			[
				'limit'  => 20,
				'offset' => 0,
				'propertyString' => 'Foo nu',
				'valueString'    => 'Bar',
				'nearbySearch'   => false
			]
		];

		#3 @see 516
		$provider[] = [
			':Foo("#^$&--2F)/("#^$&-)Bar',
			[],
			[
				'limit'  => 20,
				'offset' => 0,
				'propertyString' => 'Foo("#^$&-/)',
				'valueString'    => '("#^$&-)Bar',
				'nearbySearch'   => false
			]
		];

		#4
		$provider[] = [
			'Foo("#^$&--2F)/("#^$&-)Bar',
			[
				'property' => '("#^$&-/)李秀英',
				'value'    => '田中("#^$&-)',
				'nearbySearchForType' => true
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => '("#^$&-/)李秀英',
				'valueString'    => '田中("#^$&-)',
			]
		];

		#5
		$provider[] = [
			'',
			[
				'property' => ' Foo ',
				'value'    => '',
				'nearbySearchForType' => [ '_txt' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => 'Foo',
				'valueString'    => '',
			]
		];

		#6
		$provider[] = [
			'',
			[
				'property' => 'Foo',
				'value'    => '',
				'nearbySearchForType' => [ '_wpg' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => true,
				'propertyString' => 'Foo',
				'valueString'    => '',
			]
		];

		#7
		$provider[] = [
			'',
			[
				'property' => '',
				'value'    => 'Foo',
				'nearbySearchForType' => [ '_wpg' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => '',
				'valueString'    => 'Foo',
			]
		];

		#9
		$provider[] = [
			'',
			[
				'property' => 'Number',
				'value'    => '2',
				'nearbySearchForType' => [ '_wpg' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => 'Number',
				'valueString'    => '2.0',
			]
		];

		#10
		$provider[] = [
			'',
			[
				'property' => 'Temperature',
				'value'    => '373,15 K',
				'nearbySearchForType' => [ '_wpg' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => 'Temperature',
				'valueString'    => '373,15 K',
			]
		];

		#10
		$provider[] = [
			':Temperature/373,15-20K',
			[
				'nearbySearchForType' => [ '_wpg' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => false,
				'propertyString' => 'Temperature',
				'valueString'    => '373,15 K',
			]
		];

		#11
		$provider[] = [
			'',
			[
				'property' => 'Telephone number',
				'value'    => '%2B1-201-555-0123',
				'nearbySearchForType' => [ '_tel' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => true,
				'propertyString' => 'Telephone number',
				'valueString'    => '%2B1-201-555-0123',
			]
		];

		#11
		$provider[] = [
			':Telephone number/%2B1-2D201-2D555-2D0123',
			[
				'nearbySearchForType' => [ '_tel' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => true,
				'propertyString' => 'Telephone number',
				'valueString'    => '%2B1-201-555-0123',
			]
		];

		#12
		$provider[] = [
			'',
			[
				'property' => 'Text',
				'value'    => 'abc-123',
				'nearbySearchForType' => [ '_txt' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => true,
				'propertyString' => 'Text',
				'valueString'    => 'abc-123',
			]
		];

		#13
		$provider[] = [
			'',
			[
				'property' => 'Text',
				'value'    => 'foo-123#&^*%<1?=/->"\'',
				'nearbySearchForType' => [ '_txt' ]
			],
			[
				'limit'  => 20,
				'offset' => 0,
				'nearbySearch' => true,
				'propertyString' => 'Text',
				'valueString'    => 'foo-123#&^*%<1?=/->"\'',
			]
		];

		return $provider;
	}

}
