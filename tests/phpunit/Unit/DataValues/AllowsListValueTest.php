<?php

namespace SMW\Tests\Unit\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValues\AllowsListValue;
use SMW\Services\DataValueServiceFactory;

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

	private $dataItemFactory;
	private $dataValueServiceFactory;

	protected function setUp(): void {
		$this->dataItemFactory = new DataItemFactory();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();
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
