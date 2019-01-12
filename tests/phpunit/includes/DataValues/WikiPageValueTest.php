<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;
use SMWWikiPageValue as WikiPageValue;

/**
 * @covers \SMWWikiPageValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class WikiPageValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			WikiPageValue::class,
			new WikiPageValue( '' )
		);
	}

	public function testDisableInfolinksOnSpecialUsernamePrefix() {

		$instance = new WikiPageValue( '_wpg' );

		$this->assertFalse(
			$instance->getOption( WikiPageValue::OPT_DISABLE_INFOLINKS )
		);

		$instance->setDataItem(
			$this->dataItemFactory->newDIWikiPage( '>Foo', NS_USER )
		);

		$instance->getTitle();

		$this->assertTrue(
			$instance->getOption( WikiPageValue::OPT_DISABLE_INFOLINKS )
		);
	}

}
