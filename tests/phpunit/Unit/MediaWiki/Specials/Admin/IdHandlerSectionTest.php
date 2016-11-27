<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\IdHandlerSection;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\IdHandlerSection
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class IdHandlerSectionTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $connection;
	private $htmlFormRenderer;
	private $outputFormatter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\Admin\IdHandlerSection',
			new IdHandlerSection( $this->connection, $this->htmlFormRenderer, $this->outputFormatter )
		);
	}

	public function testOutputActionForm() {

		$methods = array(
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addInputField',
			'addSubmitButton',
			'addNonBreakingSpace',
			'addCheckbox'
		);

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->will( $this->returnSelf() );
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new IdHandlerSection(
			$this->connection,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance->outputActionForm( $webRequest );
	}

	public function testOutputActionFormWithId() {

		$manualEntryLogger = $this->getMockBuilder( '\SMW\MediaWiki\ManualEntryLogger' )
			->disableOriginalConstructor()
			->getMock();

		$manualEntryLogger->expects( $this->once() )
			->method( 'log' );

		$this->testEnvironment->registerObject( 'ManualEntryLogger', $manualEntryLogger );

		$entityIdDisposerJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\EntityIdDisposerJob' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newEntityIdDisposerJob' )
			->will( $this->returnValue( $entityIdDisposerJob ) );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$methods = array(
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addInputField',
			'addSubmitButton',
			'addNonBreakingSpace',
			'addCheckbox'
		);

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->will( $this->returnSelf() );
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->at( 0 ) )
			->method( 'getText' )
			->with( $this->equalTo( 'id' ) )
			->will( $this->returnValue( 42 ) );

		$webRequest->expects( $this->at( 1 ) )
			->method( 'getText' )
			->with( $this->equalTo( 'dispose' ) )
			->will( $this->returnValue( 'yes' ) );

		$webRequest->expects( $this->at( 2 ) )
			->method( 'getText' )
			->with( $this->equalTo( 'action' ) )
			->will( $this->returnValue( 'idlookup' ) );

		$instance = new IdHandlerSection(
			$this->connection,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->enabledIdDisposal( true );
		$instance->outputActionForm( $webRequest, $user );
	}

}
