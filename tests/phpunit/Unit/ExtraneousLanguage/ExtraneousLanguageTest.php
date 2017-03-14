<?php

namespace SMW\Tests\ExtraneousLanguage;

use SMW\ExtraneousLanguage\ExtraneousLanguage;
use SMW\ExtraneousLanguage\LanguageFileContentsReader;
use SMW\ExtraneousLanguage\LanguageContents;
use SMW\ExtraneousLanguage\LanguageFallbackFinder;

/**
 * @covers \SMW\ExtraneousLanguage\ExtraneousLanguage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ExtraneousLanguageTest extends \PHPUnit_Framework_TestCase {

	private $languageContents;

	public function setUp() {
		parent::setUp();

		$this->languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function tearDown() {
		ExtraneousLanguage::clear();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ExtraneousLanguage::class,
			new ExtraneousLanguage( $this->languageContents )
		);

		$this->assertInstanceOf(
			ExtraneousLanguage::class,
			ExtraneousLanguage::getInstance()
		);

		ExtraneousLanguage::clear();
	}

	public function testGetNamespaces() {

		$contents = [
			"SMW_NS_PROPERTY" => "Property"
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'namespaces' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
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
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'namespaceAliases' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
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
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'dateFormatsByPrecision' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
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
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'dateFormatsByPrecision' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
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
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'dataTypeLabels' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
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
			->method( 'getContentsByLanguageWithIndex' )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$this->languageContents
		);

		$this->assertEquals(
			'_foo',
			$instance->findDatatypeByLabel( 'Bar' )
		);
	}

	public function testGetPropertyIdByLabel() {

		$this->languageContents->expects( $this->at( 0 ) )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'propertyLabels' ) )
			->will( $this->returnValue( [ "_FOO" => "Foo" ] ) );

		$this->languageContents->expects( $this->at( 2 ) )
			->method( 'getContentsByLanguageWithIndex' )
			->will( $this->returnValue( [] ) );

		$instance = new ExtraneousLanguage(
			$this->languageContents
		);

		$this->assertEquals(
			'_FOO',
			$instance->getPropertyIdByLabel( 'Foo' )
		);
	}

	public function testGetDateFormats() {

		$contents = [
			[ 'SMW_Y' ],
			[ 'SMW_MY', 'SMW_YM' ]
		];

		$this->languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'dateFormats' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
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
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'months' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
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
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'months' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$this->languageContents
		);

		$this->assertEquals(
			'March',
			$instance->getMonthLabelByNumber( 3 )
		);
	}

}
