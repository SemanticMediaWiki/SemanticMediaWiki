<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin\Supplement;

use MediaWiki\Request\WebRequest;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\Supplement\EntityLookupTaskHandler;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\EntityLookupTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class EntityLookupTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $store;
	private $htmlFormRenderer;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->htmlFormRenderer = $this->getMockBuilder( HtmlFormRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EntityLookupTaskHandler::class,
			new EntityLookupTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter )
		);
	}

	public function testGetHml() {
		$this->outputFormatter->expects( $this->any() )
			->method( 'getSpecialPageLinkWith' )
			->with(
				$this->anything(),
				[ 'action' => 'lookup' ] );

		$instance = new EntityLookupTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function testReadOnlyLookupRendersWithoutRequiringAToken() {
		$methods = [
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addInputField',
			'addSubmitButton',
			'addNonBreakingSpace',
			'addCheckbox'
		];

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new EntityLookupTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->never() )
			->method( 'matchEditToken' );

		$instance->setUser(
			$user
		);

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

	public function testDisposeWithoutValidTokenIsRefused() {
		$this->htmlFormRenderer->expects( $this->never() )
			->method( 'getForm' );

		$instance = new EntityLookupTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setFeatureSet( SMW_ADM_DISPOSAL );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->method( 'matchEditToken' )
			->willReturn( false );

		$instance->setUser(
			$user
		);

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->method( 'getText' )
			->willReturnCallback( static fn ( $key, $default = '' ) => [ 'id' => '42', 'dispose' => 'yes' ][$key] ?? $default );

		$instance->handleRequest( $webRequest );
	}

}
