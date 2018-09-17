<?php

namespace SMW\Tests\Lang;

use SMW\Lang\Lang;
use SMW\Lang\LanguageContents;

/**
 * @covers \SMW\Lang\Lang
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class LangTest extends \PHPUnit_Framework_TestCase {

	private $languageContents;

	public function setUp() {
		parent::setUp();

		$this->languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function tearDown() {
		Lang::clear();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Lang::class,
			new Lang( $this->languageContents )
		);

		$this->assertInstanceOf(
			Lang::class,
			Lang::getInstance()
		);

		Lang::clear();
	}

	public function testGetNamespaces() {

		$contents = [
			"SMW_NS_PROPERTY" => "Property"
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with(
				$this->equalTo( 'namespace.labels' ),
				$this->anything() )
			->will( $this->returnValue( $contents ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			[ SMW_NS_PROPERTY => "Property" ],
			$instance->getNamespaces()
		);
	}

	public function testGetNamespaceAliases() {

		$contents = [
			"Property" => "SMW_NS_PROPERTY"
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with(
				$this->equalTo( 'namespace.aliases' ),
				$this->anything() )
			->will( $this->returnValue( $contents ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			[ "Property" => SMW_NS_PROPERTY ],
			$instance->getNamespaceAliases()
		);
	}

	public function testGetPreferredDateFormatByPrecisionOnMatchedPrecision() {

		$contents = [
			"SMW_PREC_YMDT" => "d m Y"
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with(
				$this->equalTo( 'date.precision' ),
				$this->anything() )
			->will( $this->returnValue( $contents ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			'd m Y',
			$instance->getPreferredDateFormatByPrecision( SMW_PREC_YMDT )
		);
	}

	public function testGetPreferredDateFormatOnNotMatchablePrecision() {

		$contents = [
			"Foo" => "d m Y"
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with(
				$this->equalTo( 'date.precision' ),
				$this->anything() )
			->will( $this->returnValue( $contents ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			'd F Y H:i:s',
			$instance->getPreferredDateFormatByPrecision( SMW_PREC_YMDT )
		);
	}

	public function testGetDatatypeLabels() {

		$contents = [
			"Foo" => "Bar"
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with(
				$this->equalTo( 'datatype.labels' ),
				$this->anything() )
			->will( $this->returnValue( $contents ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			[ "Foo" => 'Bar' ],
			$instance->getDatatypeLabels()
		);
	}

	public function testFindDatatypeByLabel() {

		$contents = [
			"Bar" => "_foo"
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->will( $this->returnValue( $contents ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			'_foo',
			$instance->findDatatypeByLabel( 'Bar' )
		);
	}

	public function testGetPropertyIdByLabel() {

		$this->languageContents->expects( $this->at( 0 ) )
			->method( 'get' )
			->with(
				$this->equalTo( 'property.labels' ),
				$this->anything() )
			->will( $this->returnValue( [ "_FOO" => "Foo" ] ) );

		$this->languageContents->expects( $this->at( 1 )  )
			->method( 'get' )
			->with(
				$this->equalTo( 'datatype.labels' ),
				$this->anything() )
			->will( $this->returnValue( [] ) );

		$this->languageContents->expects( $this->at( 2 ) )
			->method( 'get' )
			->will( $this->returnValue( [] ) );

		$this->languageContents->expects( $this->at( 3 ) )
			->method( 'get' )
			->will( $this->returnValue( [] ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			'_FOO',
			$instance->getPropertyIdByLabel( 'Foo' )
		);
	}

	public function testGetPropertyLabelList() {

		$propertyLabels = [
			'_Foo'  => 'Bar',
			'_Foo2' => 'Baar',
			'_Foo3' => 'Abc'
		];

		$this->languageContents->expects( $this->any() )
			->method( 'get' )
			->will( $this->onConsecutiveCalls( $propertyLabels, [], [], [] ) );

		$instance = new Lang(
			$this->languageContents
		);

		$instance->fetch( 'foo' );

		$this->assertEquals(
			[ 'label' => [
				'Bar' => '_Foo',
				'Baar' => '_Foo2',
				'Abc' => '_Foo3'
			] ],
			$instance->getPropertyLabelList()
		);
	}

	public function testGetDateFormats() {

		$contents = [
			[ 'SMW_Y' ],
			[ 'SMW_MY', 'SMW_YM' ]
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with(
				$this->equalTo( 'date.format' ),
				$this->anything() )
			->will( $this->returnValue( $contents ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			[ [ 9 ], [ 97, 76 ] ],
			$instance->getDateFormats()
		);
	}

	public function testFindMonthNumberByLabelWithCaseInsensitiveSearch() {

		$contents = [
			[ 'January', 'Jan' ],
			[ 'February', 'Feb' ],
			[ 'March', 'Mar' ]
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with(
				$this->equalTo( 'date.months' ),
				$this->anything() )
			->will( $this->returnValue( $contents ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			3,
			$instance->findMonthNumberByLabel( 'mar' )
		);
	}

	public function testGetMonthLabelByNumber() {

		$contents = [
			[ 'January', 'Jan' ],
			[ 'February', 'Feb' ],
			[ 'March', 'Mar' ]
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with(
				$this->equalTo( 'date.months' ),
				$this->anything() )
			->will( $this->returnValue( $contents ) );

		$instance = new Lang(
			$this->languageContents
		);

		$this->assertEquals(
			'March',
			$instance->getMonthLabelByNumber( 3 )
		);
	}

}
