<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Maintenance;

use SMW\MediaWiki\Specials\Admin\Maintenance\TableSchemaTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Maintenance\TableSchemaTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableSchemaTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;
	private $htmlFormRenderer;
	private $outputFormatter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TableSchemaTaskHandler::class,
			new TableSchemaTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter )
		);
	}

	public function testGetHtml() {

		$methods = [
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addSubmitButton'
		];

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->will( $this->returnSelf() );
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new TableSchemaTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->getHtml();
	}

	public function testHandleRequest() {

		$this->store->expects( $this->once() )
			->method( 'setup' );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->once() )
			->method( 'getVal' )
			->will( $this->returnValue( 'done' ) );

		$instance = new TableSchemaTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setFeatureSet( SMW_ADM_SETUP );
		$instance->handleRequest( $webRequest );
	}

}
