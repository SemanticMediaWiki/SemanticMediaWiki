<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Supplement;

use MediaWiki\Request\WebRequest;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\MediaWiki\ManualEntryLogger;
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
	private $connection;
	private $htmlFormRenderer;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

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

	public function testPerformAction() {
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

		$user->expects( $this->atLeastOnce() )
			->method( 'matchEditToken' )
			->willReturn( true );

		$instance->setUser(
			$user
		);

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

	public function testPerformActionWithId() {
		$this->connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [] );

		$manualEntryLogger = $this->getMockBuilder( ManualEntryLogger::class )
			->disableOriginalConstructor()
			->getMock();

		$manualEntryLogger->expects( $this->once() )
			->method( 'log' );

		$this->testEnvironment->registerObject( 'ManualEntryLogger', $manualEntryLogger );

		$entityIdDisposerJob = $this->getMockBuilder( EntityIdDisposerJob::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newEntityIdDisposerJob' )
			->willReturn( $entityIdDisposerJob );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

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

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->atLeastOnce() )
			->method( 'matchEditToken' )
			->willReturn( true );

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->atLeastOnce() )
			->method( 'getText' )
			->willReturnCallback( static function ( $key ) {
				$map = [
					'id'      => 42,
					'dispose' => 'yes',
					'action'  => 'lookup',
				];
				return $map[$key] ?? '';
			} );

		$instance = new EntityLookupTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setEnabledFeatures( SMW_ADM_DISPOSAL );
		$instance->setUser( $user );

		$instance->handleRequest( $webRequest );
	}

}
