<?php

namespace SMW\Tests\Query\Language;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ValueDescription;
use SMWDINumber as DINumber;

/**
 * @covers \SMW\Query\Language\ValueDescription
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ValueDescriptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			new ValueDescription( $dataItem )
		);

		// Legacy
		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			new \SMWValueDescription( $dataItem )
		);
	}

	/**
	 * @dataProvider valueDescriptionProvider
	 */
	public function testCommonMethods( $dataItem, $property, $comparator, $expected ) {

		$instance = new ValueDescription( $dataItem, $property, $comparator );

		$this->assertEquals(
			$expected['comparator'],
			$instance->getComparator()
		);

		$this->assertEquals(
			$expected['dataItem'],
			$instance->getDataItem()
		);

		$this->assertEquals(
			$expected['property'],
			$instance->getProperty()
		);

		$this->assertEquals(
			$expected['queryString'],
			$instance->getQueryString()
		);

		$this->assertEquals(
			$expected['queryStringAsValue'],
			$instance->getQueryString( true )
		);

		$this->assertEquals(
			$expected['isSingleton'],
			$instance->isSingleton()
		);

		$this->assertEquals(
			[],
			$instance->getPrintRequests()
		);

		$this->assertEquals(
			1,
			$instance->getSize()
		);

		$this->assertEquals(
			0,
			$instance->getDepth()
		);

		$this->assertEquals(
			0,
			$instance->getQueryFeatures()
		);
	}

	/**
	 * @dataProvider comparativeHashProvider
	 */
	public function testGetFingerprint( $description, $compareTo, $expected ) {

		$this->assertEquals(
			$expected,
			$description->getFingerprint() === $compareTo->getFingerprint()
		);
	}

	public function valueDescriptionProvider() {

		$dataItem = new DIWikiPage( 'Foo', NS_MAIN );

		$provider[] = [
			$dataItem,
			null,
			SMW_CMP_EQ,
			[
				'comparator'  => SMW_CMP_EQ,
				'dataItem'    => $dataItem,
				'property'    => null,
				'queryString' => '[[:Foo]]',
				'queryStringAsValue' => 'Foo',
				'isSingleton' => true
			]
		];

		$provider['page.1'] = [
			$dataItem,
			null,
			SMW_CMP_LEQ,
			[
				'comparator'  => SMW_CMP_LEQ,
				'dataItem'    => $dataItem,
				'property'    => null,
				'queryString' => '[[≤Foo]]',
				'queryStringAsValue' => '≤Foo',
				'isSingleton' => false
			]
		];

		$property = DIProperty::newFromUserLabel( 'Foo' )->setPropertyTypeId( '_num' );
		$dataItem = new DINumber( 9001 );

		$provider['num.1'] = [
			$dataItem,
			$property,
			SMW_CMP_LEQ,
			[
				'comparator'  => SMW_CMP_LEQ,
				'dataItem'    => $dataItem,
				'property'    => $property,
				'queryString' => '[[≤9001]]',
				'queryStringAsValue' => '≤9001',
				'isSingleton' => false
			]
		];

		$property = DIProperty::newFromUserLabel( 'Foo' )->setPropertyTypeId( '_num' );
		$dataItem = new DINumber( 9001.356 );

		$provider['num.2'] = [
			$dataItem,
			$property,
			SMW_CMP_GEQ,
			[
				'comparator'  => SMW_CMP_GEQ,
				'dataItem'    => $dataItem,
				'property'    => $property,
				'queryString' => '[[≥9001.356]]',
				'queryStringAsValue' => '≥9001.356',
				'isSingleton' => false
			]
		];

		return $provider;
	}

	public function comparativeHashProvider() {

		$provider[] = [
			new ValueDescription(
				new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_EQ
			),
			new ValueDescription(
				new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_EQ
			),
			true
		];

		$provider[] = [
			new ValueDescription(
				new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_EQ
			),
			new ValueDescription(
				new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_LEQ
			),
			false
		];

		$provider[] = [
			new ValueDescription(
				new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_EQ
			),
			new ValueDescription(
				new DIWikiPage( 'Foo', NS_MAIN ), new DIProperty( 'Bar' ), SMW_CMP_EQ
			),
			false
		];

		// Inverse case
		$provider[] = [
			new ValueDescription(
				new DIWikiPage( 'Foo', NS_MAIN ), new DIProperty( 'Bar', true ), SMW_CMP_EQ
			),
			new ValueDescription(
				new DIWikiPage( 'Foo', NS_MAIN ), new DIProperty( 'Bar' ), SMW_CMP_EQ
			),
			false
		];

		return $provider;
	}

}
