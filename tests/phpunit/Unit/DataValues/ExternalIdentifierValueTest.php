<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ExternalIdentifierValue;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;
use SMW\PropertySpecificationLookup;

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

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( PropertySpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

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
			->method( 'getExternalFormatterUriBy' )
			->will( $this->returnValue( $this->dataItemFactory->newDIUri( 'http', 'example.org/$1' ) ) );

		$instance = new ExternalIdentifierValue();

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

	public function testGetShortHTMLText() {

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getExternalFormatterUriBy' )
			->will( $this->returnValue( $this->dataItemFactory->newDIUri( 'http', 'example.org/$1' ) ) );

		$instance = new ExternalIdentifierValue();

		$instance->setUserValue( 'foo' );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			'foo',
			$instance->getShortHTMLText()
		);

		$this->assertEquals(
			'<a href="http://example.org/foo" target="_blank">foo</a>',
			$instance->getShortHTMLText( 'linker' )
		);

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testTryToGetShortHTMLTextWithLinkerOnMissingFormatterUri() {

		$instance = new ExternalIdentifierValue();

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

}
