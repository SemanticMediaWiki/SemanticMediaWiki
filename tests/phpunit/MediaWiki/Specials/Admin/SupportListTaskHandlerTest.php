<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\SupportListTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\SupportListTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SupportListTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $htmlFormRenderer;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
		// ->setMEthods( [ 'getInfo' ] )
			->getMockForAbstractClass();

		$this->testEnvironment = new TestEnvironment();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\Admin\SupportListTaskHandler',
			new SupportListTaskHandler( $this->htmlFormRenderer )
		);
	}

	public function testGetHtml() {
		$methods = [
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addSubmitButton',
			'setActionUrl'
		];

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new SupportListTaskHandler(
			$this->htmlFormRenderer
		);

		$instance->setStore( $this->store );

		$instance->getHtml();
	}

}
