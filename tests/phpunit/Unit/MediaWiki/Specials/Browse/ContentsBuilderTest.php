<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\MediaWiki\Specials\Browse\ContentsBuilder;
use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;
use SMW\SemanticData;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\ContentsBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ContentsBuilderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;

	protected function setUp() {
		parent::setUp();

		// Disable a possible active hook execution
		$this->testEnvironment = new TestEnvironment( array(
			'smwgEnabledQueryDependencyLinksStore' => false
		) );

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

		$instance = new ContentsBuilder(
			$this->store,
			DIWikiPage::newFromText( 'Foo' )
		);

		$this->assertInstanceOf(
			ContentsBuilder::class,
			$instance
		);
	}

	public function testGetEmptyHtml() {

		$instance = new ContentsBuilder(
			$this->store,
			DIWikiPage::newFromText( 'Foo' )
		);

		$this->assertInternalType(
			'string',
			$instance->getEmptyHtml()
		);
	}

	public function testGetHtml() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( new SemanticData( $subject ) ) );

		$instance = new ContentsBuilder(
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

		$instance = new ContentsBuilder(
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

		$instance->importOptionsFromJson(
			json_encode( $options )
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testOptions() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$instance = new ContentsBuilder(
			$this->store,
			$subject
		);

		$options = array(
			'Foo' => 42
		);

		$instance->importOptionsFromJson(
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
