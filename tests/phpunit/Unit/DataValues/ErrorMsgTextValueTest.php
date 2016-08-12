<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ErrorMsgTextValue;
use SMW\DataItemFactory;

/**
 * @covers \SMW\DataValues\ErrorMsgTextValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorMsgTextValueTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;

	protected function setUp() {
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ErrorMsgTextValue',
			new ErrorMsgTextValue()
		);
	}

	public function testErrorOnEmptyUserValue() {

		$instance = new ErrorMsgTextValue();
		$instance->setUserValue( '' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider textProvider
	 */
	public function testValueOutput( $text, $expected ) {

		$dataItem = $this->dataItemFactory->newDIBlob( $text );

		$instance = new ErrorMsgTextValue();
		$instance->setDataItem( $dataItem );

		$this->assertEquals(
			$expected,
			$instance->getWikiValue()
		);

		$this->assertEquals(
			$expected,
			$instance->getShortWikiText()
		);

		$this->assertEquals(
			$expected,
			$instance->getShortHTMLText()
		);

		$this->assertEquals(
			$expected,
			$instance->getLongWikiText()
		);

		$this->assertEquals(
			$expected,
			$instance->getLongHTMLText()
		);
	}

	public function textProvider() {

		$provider[] = array(
			'Foo',
			'Foo'
		);

		$provider[] = array(
			'[2,"Foo"]',
			'Foo'
		);

		return $provider;
	}

}
