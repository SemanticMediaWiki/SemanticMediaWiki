<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\DataValues\ExternalIdentifierValue;
use SMW\PropertySpecificationLookup;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ExternalIdentifierValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ExternalIdentifierValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $dataValueServiceFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( PropertySpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$externalFormatterUriValue = $this->getMockBuilder( '\SMW\DataValues\ExternalFormatterUriValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getPropertySpecificationLookup' )
			->will( $this->returnValue( $this->propertySpecificationLookup ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $constraintValueValidator ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getDataValueFactory' )
			->will( $this->returnValue( DataValueFactory::getInstance() ) );

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ExternalIdentifierValue::class,
			new ExternalIdentifierValue()
		);
	}

	public function testGetShortWikiText() {

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getExternalFormatterUri' )
			->will( $this->returnValue( $this->dataItemFactory->newDIUri( 'http', 'example.org/$1' ) ) );

		$instance = new ExternalIdentifierValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );

		$instance->setUserValue( 'foo' );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			'foo',
			$instance->getShortWikiText()
		);

		$this->assertEquals(
			'<span class="plainlinks smw-eid">[http://example.org/foo foo]</span>',
			$instance->getShortWikiText( 'linker' )
		);
	}

	public function testGetShortWikiText_Nowiki() {

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getExternalFormatterUri' )
			->will( $this->returnValue( $this->dataItemFactory->newDIUri( 'http', 'example.org/$1' ) ) );

		$instance = new ExternalIdentifierValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );

		$instance->setUserValue( 'foo' );
		$instance->setOutputFormat( 'nowiki' );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			'foo',
			$instance->getShortWikiText()
		);

		$this->assertEquals(
			'<span class="plainlinks smw-eid">http&#58;//example.org/foo</span>',
			$instance->getShortWikiText( 'linker' )
		);
	}

	/**
	 * @dataProvider identifierProvider
	 */
	public function testGetShortHTMLText( $value, $uri, $expected_text, $expected_html ) {

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getExternalFormatterUri' )
			->will( $this->returnValue( $uri ) );

		$instance = new ExternalIdentifierValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );

		$instance->setUserValue( $value );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			$expected_text,
			$instance->getShortHTMLText()
		);

		$this->assertEquals(
			$expected_html,
			$instance->getShortHTMLText( 'linker' )
		);

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testTryToGetShortHTMLTextWithLinkerOnMissingFormatterUri() {

		$instance = new ExternalIdentifierValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );

		$instance->setUserValue( 'foo' );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			'foo',
			$instance->getShortHTMLText()
		);

		$instance->getShortHTMLText( 'linker' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function identifierProvider() {

		$dataItemFactory = new DataItemFactory();

		yield [
			'foo',
			$dataItemFactory->newDIUri( 'http', 'example.org/$1' ),
			'foo',
			'<a href="http://example.org/foo" target="_blank">foo</a>'
		];

		// foo + 1001 [main,extra]
		yield [
			'foo{1001}',
			$dataItemFactory->newDIUri( 'http', 'example.org/$1&id=$2' ),
			'foo{1001}',
			'<a href="http://example.org/foo&amp;id=1001" target="_blank">foo</a>'
		];

		// trim space / main part and extra parameters
		yield [
			'foo {1001}',
			$dataItemFactory->newDIUri( 'http', 'example.org/$1&id=$2' ),
			'foo {1001}',
			'<a href="http://example.org/foo&amp;id=1001" target="_blank">foo</a>'
		];

		// encoded comma
		yield [
			'foo {1\,2\,and}',
			$dataItemFactory->newDIUri( 'http', 'example.org/$1&id=$2' ),
			'foo {1,2,and}',
			'<a href="http://example.org/foo&amp;id=1,2,and" target="_blank">foo</a>'
		];

		// no multi substitute
		yield [
			'foo {1001}',
			$dataItemFactory->newDIUri( 'http', 'example.org/$1' ),
			'foo {1001}',
			'<a href="http://example.org/foo_%7B1001%7D" target="_blank">foo {1001}</a>'
		];
	}

}
