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

	public function testCanConstruct() {

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ExtraneousLanguage::class,
			new ExtraneousLanguage( $languageContents )
		);

		$this->assertInstanceOf(
			ExtraneousLanguage::class,
			ExtraneousLanguage::getInstance()
		);

		ExtraneousLanguage::clear();
	}

	public function testGetNamespaces() {

		$contents = array(
			"SMW_NS_PROPERTY" => "Property"
		);

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'namespaces' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$languageContents
		);

		$this->assertEquals(
			array( SMW_NS_PROPERTY => "Property" ),
			$instance->getNamespaces()
		);
	}

	public function testGetNamespaceAliases() {

		$contents = array(
			"Property" => "SMW_NS_PROPERTY"
		);

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'namespaceAliases' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$languageContents
		);

		$this->assertEquals(
			array( "Property" => SMW_NS_PROPERTY ),
			$instance->getNamespaceAliases()
		);
	}

	public function testGetPreferredDateFormatByPrecisionOnMatchedPrecision() {

		$contents = array(
			"SMW_PREC_YMDT" => "d m Y"
		);

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'dateFormatsByPrecision' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$languageContents
		);

		$this->assertEquals(
			'd m Y',
			$instance->getPreferredDateFormatByPrecision( SMW_PREC_YMDT )
		);
	}

	public function testGetPreferredDateFormatOnNotMatchablePrecision() {

		$contents = array(
			"Foo" => "d m Y"
		);

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'dateFormatsByPrecision' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$languageContents
		);

		$this->assertEquals(
			'd F Y H:i:s',
			$instance->getPreferredDateFormatByPrecision( SMW_PREC_YMDT )
		);
	}

	public function testGetDatatypeLabels() {

		$contents = array(
			"Foo" => "Bar"
		);

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'dataTypeLabels' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$languageContents
		);

		$this->assertEquals(
			array( "Foo" => 'Bar' ),
			$instance->getDatatypeLabels()
		);
	}

	public function testGetPropertyIdByLabel() {

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$languageContents->expects( $this->at( 0 ) )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'propertyLabels' ) )
			->will( $this->returnValue( array( "_FOO" => "Foo" ) ) );

		$languageContents->expects( $this->at( 2 ) )
			->method( 'getContentsByLanguageWithIndex' )
			->will( $this->returnValue( array() ) );

//		$languageContents->expects( $this->at( 5 ) )
//			->method( 'getContentsByLanguageWithIndex' )
//			->with(
//				$this->anything(),
//				$this->equalTo( 'propertyAliases' ) )
//			->will( $this->returnValue( array( "Foo" => "_FOO" ) ) );

		$instance = new ExtraneousLanguage(
			$languageContents
		);

		$this->assertEquals(
			'_FOO',
			$instance->getPropertyIdByLabel( 'Foo' )
		);
	}

	public function testGetDateFormats() {

		$contents = array(
			array( 'SMW_Y' ),
			array( 'SMW_MY', 'SMW_YM' )
		);

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'dateFormats' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$languageContents
		);

		$this->assertEquals(
			array( array( 9 ), array( 97, 76 ) ),
			$instance->getDateFormats()
		);
	}

	public function testFindMonthNumberByLabelWithCaseInsensitiveSearch() {

		$contents = array(
			array( 'January', 'Jan' ),
			array( 'February', 'Feb' ),
			array( 'March', 'Mar' )
		);

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'months' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$languageContents
		);

		$this->assertEquals(
			3,
			$instance->findMonthNumberByLabel( 'mar' )
		);
	}

	public function testGetMonthLabelByNumber() {

		$contents = array(
			array( 'January', 'Jan' ),
			array( 'February', 'Feb' ),
			array( 'March', 'Mar' )
		);

		$languageContents = $this->getMockBuilder( LanguageContents::class )
			->disableOriginalConstructor()
			->getMock();

		$languageContents->expects( $this->atLeastOnce() )
			->method( 'getContentsByLanguageWithIndex' )
			->with(
				$this->anything(),
				$this->equalTo( 'months' ) )
			->will( $this->returnValue( $contents ) );

		$instance = new ExtraneousLanguage(
			$languageContents
		);

		$this->assertEquals(
			'March',
			$instance->getMonthLabelByNumber( 3 )
		);
	}

}
