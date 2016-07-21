<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\MediaWiki\Specials\Browse\HtmlContentBuilder;
use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;
use SMW\SemanticData;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\HtmlContentBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class HtmlContentBuilderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new HtmlContentBuilder(
			$this->store,
			DIWikiPage::newFromText( 'Foo' )
		);

		$this->assertInstanceOf(
			HtmlContentBuilder::class,
			$instance
		);
	}

	public function testGetEmptyHtml() {

		$instance = new HtmlContentBuilder(
			$this->store,
			DIWikiPage::newFromText( 'Foo' )
		);

		$this->assertInternalType(
			'string',
			$instance->getEmptyHtml()
		);
	}

	public function testGetPageSearchQuickForm() {

		$this->assertInternalType(
			'string',
			HtmlContentBuilder::getPageSearchQuickForm()
		);
	}

	public function testGetHtml() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( new SemanticData( $subject ) ) );

		$instance = new HtmlContentBuilder(
			$this->store,
			$subject
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testGetHtmlWithOptions() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( new SemanticData( $subject ) ) );

		$this->store->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( array() ) );

		$instance = new HtmlContentBuilder(
			$this->store,
			$subject
		);

		$options = array(
			'offset' => 0,
			'showAll' => true,
			'showInverse' => false,
			'dir' => 'both',
			'printable' => ''
		);

		$instance->setOptionsFromJsonFormat(
			json_encode( $options )
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testOptions() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$instance = new HtmlContentBuilder(
			$this->store,
			$subject
		);

		$options = array(
			'Foo' => 42
		);

		$instance->setOptionsFromJsonFormat(
			json_encode( $options )
		);

		$instance->setOption(
			'Bar',
			1001
		);

		$this->assertEquals(
			42,
			$instance->getOption( 'Foo' )
		);

		$this->assertEquals(
			1001,
			$instance->getOption( 'Bar' )
		);
	}

}
