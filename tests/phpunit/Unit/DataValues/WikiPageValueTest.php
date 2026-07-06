<?php

namespace SMW\Tests\Unit\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValues\WikiPageValue;

/**
 * @covers \SMW\DataValues\WikiPageValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class WikiPageValueTest extends TestCase {

	private $dataItemFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();
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
	}

}
