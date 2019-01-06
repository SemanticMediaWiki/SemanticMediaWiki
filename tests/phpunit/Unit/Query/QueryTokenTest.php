<?php

namespace SMW\Tests\Query;

use SMW\DataItemFactory;
use SMW\Query\QueryToken;

/**
 * @covers \SMW\Query\QueryToken
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QueryTokenTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			QueryToken::class,
			new QueryToken()
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testAddFromDesciption( $description, $expected ) {

		$instance = new QueryToken();

		$instance->addFromDesciption( $description );

		$this->assertEquals(
			$expected,
			$instance->getTokens()
		);
	}

	public function testMulitpleAddFromDesciption() {

		$instance = new QueryToken();

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getComparator' )
			->will( $this->returnValue( SMW_CMP_LIKE ) );

		$description->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'abc Foo 123' ) ) );

		$instance->addFromDesciption( $description );

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( '~*123 bar 456' ) ) );

		$instance->addFromDesciption( $description );

		$this->assertEquals(
			[
				'abc' => 0,
				'Foo' => 1,
				123 => 2,
				'bar' => 1,
				456 => 2
			],
			$instance->getTokens()
		);
	}

	/**
	 * @dataProvider highlightProvider
	 */
	public function testHighlight( $description, $text, $type, $expected ) {

		$instance = new QueryToken();

		$instance->addFromDesciption( $description );
		$instance->setOutputFormat( '-hL' );

		$this->assertEquals(
			$expected,
			$instance->highlight( $text, $type )
		);
	}

	public function descriptionProvider() {

		$dataItemFactory = new DataItemFactory();

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getComparator' )
			->will( $this->returnValue( SMW_CMP_LIKE ) );

		$description->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItemFactory->newDIBlob( 'abc Foo 123' ) ) );

		$provider[] = [
			$description,
			[
				'abc' => 0,
				'Foo' => 1,
				123 => 2
			]
		];

		return $provider;
	}

	public function highlightProvider() {

		$dataItemFactory = new DataItemFactory();

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->any() )
			->method( 'getComparator' )
			->will( $this->returnValue( SMW_CMP_LIKE ) );

		$description->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItemFactory->newDIBlob( 'abc Foo 123 foobar' ) ) );

		$provider[] = [
			$description,
			'Lorem abc foobar',
			QueryToken::HL_BOLD,
			"Lorem <b>abc</b> <b>foo</b>bar"
		];

		$provider[] = [
			$description,
			'Lorem abc foobar',
			QueryToken::HL_WIKI,
			"Lorem '''abc''' '''foo'''bar"
		];

		$provider[] = [
			$description,
			'Lorem abc foobar',
			QueryToken::HL_UNDERLINE,
			"Lorem <u>abc</u> <u>foo</u>bar"
		];

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->any() )
			->method( 'getComparator' )
			->will( $this->returnValue( SMW_CMP_LIKE ) );

		$description->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItemFactory->newDIBlob( 'integer porttitor portt' ) ) );

		$provider[] = [
			$description,
			'Integer porttitor mi id ante consequat consequat <b>porttitor</b>',
			QueryToken::HL_BOLD,
			"<b>Integer</b> <b>porttitor</b> mi id ante consequat consequat <b><b>porttitor</b></b>"
		];

		$provider[] = [
			$description,
			'Integer porttitor mi id ante consequat consequat <b>porttitor</b>',
			QueryToken::HL_SPAN,
			"<span class='smw-query-token'>Integer</span> <span class='smw-query-token'>porttitor</span> mi id ante consequat consequat <b><span class='smw-query-token'>porttitor</span></b>"
		];

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->any() )
			->method( 'getComparator' )
			->will( $this->returnValue( SMW_CMP_PRIM_LIKE ) );

		$description->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItemFactory->newDIBlob( 'abc Foo 123 foobar' ) ) );

		$provider[] = [
			$description,
			'Lorem abc foobar',
			QueryToken::HL_BOLD,
			"Lorem <b>abc</b> <b>foo</b>bar"
		];

		return $provider;
	}

}
