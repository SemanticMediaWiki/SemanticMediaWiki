<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\SupportListTaskHandler;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\SupportListTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SupportListTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $htmlFormRenderer;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
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

		$methods = array(
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addSubmitButton',
			'setActionUrl'
		);

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->will( $this->returnSelf() );
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new SupportListTaskHandler(
			$this->htmlFormRenderer
		);

		$instance->getHtml();
	}


}
