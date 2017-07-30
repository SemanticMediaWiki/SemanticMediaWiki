<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValues\AllowsListValue;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\AllowsListValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsListValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $dataValueServiceFactory;
	private $propertySpecificationLookup;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			AllowsListValue::class,
			new AllowsListValue()
		);
	}

	public function testGetShortWikiText() {

		$instance = new AllowsListValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setDataItem(
			$this->dataItemFactory->newDIBlob( 'Foo' )
		);

		$this->assertContains(
			'[[MediaWiki:Smw_allows_list_Foo|Foo]]',
			$instance->getShortWikiText()
		);
	}

	public function testGetLongHtmlText() {

		$instance = new AllowsListValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setDataItem(
			$this->dataItemFactory->newDIBlob( 'Foo' )
		);

		$this->assertContains(
			'MediaWiki:Smw_allows_list_Foo',
			$instance->getLongHtmlText()
		);
	}

	public function testGetShortHtmlText() {

		$instance = new AllowsListValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setDataItem(
			$this->dataItemFactory->newDIBlob( 'Foo' )
		);

		$this->assertContains(
			'MediaWiki:Smw_allows_list_Foo',
			$instance->getShortHtmlText()
		);
	}

}
