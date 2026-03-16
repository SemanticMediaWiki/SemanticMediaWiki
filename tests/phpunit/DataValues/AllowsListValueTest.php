<?php

namespace SMW\Tests\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValues\AllowsListValue;
use SMW\Property\SpecificationLookup;
use SMW\Services\DataValueServiceFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\AllowsListValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsListValueTest extends TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $dataValueServiceFactory;
	private $propertySpecificationLookup;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown(): void {
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

		$this->assertStringContainsString(
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

		$this->assertStringContainsString(
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

		$this->assertStringContainsString(
			'MediaWiki:Smw_allows_list_Foo',
			$instance->getShortHtmlText()
		);
	}

}
