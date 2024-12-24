<?php

namespace SMW\Tests\Localizer\LocalLanguage;

use SMW\Localizer\LocalLanguage\LocalLanguage;
use SMW\Localizer\LocalLanguage\LanguageContents;

/**
 * @covers \SMW\Localizer\LocalLanguage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class LocalLanguageTest extends \PHPUnit\Framework\TestCase {

	private $languageContents;

	public function setUp(): void {
		parent::setUp();

		$this->languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function tearDown(): void {
		LocalLanguage::clear();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			LocalLanguage::class,
			new LocalLanguage( $this->languageContents )
		);

		$this->assertInstanceOf(
			LocalLanguage::class,
			LocalLanguage::getInstance()
		);

		LocalLanguage::clear();
	}

	public function testGetNamespaces() {
		$contents = [
			"SMW_NS_PROPERTY" => "Property"
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with(
				'namespace.labels',
				$this->anything() )
			->willReturn( $contents );

		$instance = new LocalLanguage(
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
				'namespace.aliases',
				$this->anything() )
			->willReturn( $contents );

		$instance = new LocalLanguage(
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
				'date.precision',
				$this->anything() )
			->willReturn( $contents );

		$instance = new LocalLanguage(
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
				'date.precision',
				$this->anything() )
			->willReturn( $contents );

		$instance = new LocalLanguage(
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
				'datatype.labels',
				$this->anything() )
			->willReturn( $contents );

		$instance = new LocalLanguage(
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
			->willReturn( $contents );

		$instance = new LocalLanguage(
			$this->languageContents
		);

		$this->assertEquals(
			'_foo',
			$instance->findDatatypeByLabel( 'Bar' )
		);
	}

	public function testGetPropertyIdByLabel() {
		$this->languageContents->expects( $this->exactly( 4 ) )
			->method( 'get' )
			->withConsecutive(
				[ $this->equalTo( 'property.labels' ), $this->anything() ],
				[ $this->equalTo( 'datatype.labels' ), $this->anything() ],
				[ $this->equalTo( 'property.aliases' ), $this->anything() ],
				[ $this->equalTo( 'property.aliases' ), $this->anything() ]
			)
			->willReturnOnConsecutiveCalls(
				[ "_FOO" => "Foo" ],
				[],
				[],
				[]
			);

		$instance = new LocalLanguage(
			$this->languageContents
		);

		$this->assertEquals(
			'_FOO',
			$instance->getPropertyIdByLabel( 'Foo' )
		);
	}

	public function testGetPropertyIdByLabel_NoMatch() {
		// inverse testing - Mocking the data to ensure that the label does not match any property ID.
		$this->languageContents->expects( $this->exactly( 4 ) )
			->method( 'get' )
			->withConsecutive(
				[ $this->equalTo( 'property.labels' ), $this->anything() ],
				[ $this->equalTo( 'datatype.labels' ), $this->anything() ],
				[ $this->equalTo( 'property.aliases' ), $this->anything() ],
				[ $this->equalTo( 'property.aliases' ), $this->anything() ]
			)
			->willReturnOnConsecutiveCalls(
				[ '_FOO' => 'Bar' ],
				[],
				[],
				[]
		);

		$instance = new LocalLanguage( $this->languageContents );

		// Check that the label 'Foo' does not match any property ID
		$this->assertNull( $instance->getPropertyIdByLabel( 'Foo' ) );
	}

	public function testGetPropertyIdByLabel_AllSourcesEmpty() {
		// inverse testing - Mocking the data to simulate empty arrays from all sources
		$this->languageContents->expects( $this->exactly( 4 ) )
			->method( 'get' )
			->withConsecutive(
				[ $this->equalTo( 'property.labels' ), $this->anything() ],
				[ $this->equalTo( 'datatype.labels' ), $this->anything() ],
				[ $this->equalTo( 'property.aliases' ), $this->anything() ],
				[ $this->equalTo( 'property.aliases' ), $this->anything() ]
			)
			->willReturnOnConsecutiveCalls(
				[],
				[],
				[],
				[]
		);

		$instance = new LocalLanguage( $this->languageContents );

		// Check that when all data sources are empty, no property ID is found
		$this->assertNull( $instance->getPropertyIdByLabel( 'Foo' ) );
	}

	public function testGetPropertyLabelList() {
		$propertyLabels = [
			'_Foo'  => 'Bar',
			'_Foo2' => 'Baar',
			'_Foo3' => 'Abc'
		];

		$this->languageContents->expects( $this->any() )
			->method( 'get' )
			->willReturnOnConsecutiveCalls( $propertyLabels, [], [], [] );

		$instance = new LocalLanguage(
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
				'date.format',
				$this->anything() )
			->willReturn( $contents );

		$instance = new LocalLanguage(
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
				'date.months',
				$this->anything() )
			->willReturn( $contents );

		$instance = new LocalLanguage(
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
				'date.months',
				$this->anything() )
			->willReturn( $contents );

		$instance = new LocalLanguage(
			$this->languageContents
		);

		$this->assertEquals(
			'March',
			$instance->getMonthLabelByNumber( 3 )
		);
	}

}
