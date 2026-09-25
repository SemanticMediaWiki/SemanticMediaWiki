<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin\Supplement;

use MediaWiki\Request\WebRequest;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\Supplement\EntityLookupTaskHandler;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

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

	use MockSelectQueryBuilderTrait;

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

	public function testScriptBearingIdIsEscapedInNoReferencesLookupMessage() {
		$output = $this->renderLookup( '<img src=x onerror=alert(1)>' );

		$this->assertStringNotContainsString( '<img src=x', $output );
		$this->assertStringContainsString( '&lt;img src=x', $output );
	}

	public function testBenignIdIsPreservedInNoReferencesLookupMessage() {
		$output = $this->renderLookup( 'Example_page' );

		$this->assertStringContainsString( 'Example_page', $output );
	}

	/**
	 * Drives the id lookup for an id that matches no entity, so the no-references
	 * branch renders, and returns the text handed to the form for display.
	 */
	private function renderLookup( string $id ): string {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [] ) );

		$connection->method( 'addQuotes' )
			->willReturnCallback( static fn ( $value ) => "'" . $value . "'" );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->method( 'getConnection' )
			->willReturn( $connection );

		$paragraphs = [];

		$selfReturning = [ 'setName', 'setMethod', 'addHiddenField', 'addHeader',
			'addInputField', 'addSubmitButton', 'addNonBreakingSpace', 'addCheckbox' ];

		foreach ( $selfReturning as $method ) {
			$this->htmlFormRenderer->method( $method )->willReturnSelf();
		}

		$this->htmlFormRenderer->method( 'addParagraph' )
			->willReturnCallback( function ( $text ) use ( &$paragraphs ) {
				$paragraphs[] = $text;
				return $this->htmlFormRenderer;
			} );

		$this->htmlFormRenderer->method( 'getForm' )
			->willReturn( '' );

		$instance = new EntityLookupTaskHandler(
			$store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->method( 'getText' )
			->willReturnCallback( static fn ( $key, $default = '' ) => [ 'id' => $id, 'action' => 'lookup' ][$key] ?? $default );

		$instance->handleRequest( $webRequest );

		return implode( "\n", $paragraphs );
	}

}
