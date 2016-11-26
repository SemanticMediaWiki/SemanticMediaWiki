<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\TableSchemaUpdaterSection;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\TableSchemaUpdaterSection
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableSchemaUpdaterSectionTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\MediaWiki\Specials\Admin\TableSchemaUpdaterSection',
			new TableSchemaUpdaterSection( $this->store, $this->htmlFormRenderer, $this->outputFormatter )
		);
	}

	public function testGetForm() {

		$methods = array(
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addSubmitButton'
		);

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->will( $this->returnSelf() );
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new TableSchemaUpdaterSection(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->getForm();
	}

	public function testDoUpdate() {

		$this->store->expects( $this->once() )
			->method( 'setup' );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableSchemaUpdaterSection(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->enabledSetupStore( true );
		$instance->doUpdate( $webRequest );
	}

}
