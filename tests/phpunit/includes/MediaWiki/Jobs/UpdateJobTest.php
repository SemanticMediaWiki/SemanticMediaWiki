<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\ExtensionContext;
use SMW\Settings;

use Title;

/**
 * @covers \SMW\MediaWiki\Jobs\UpdateJob
 * @covers \SMW\MediaWiki\Jobs\JobBase
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateJobTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\UpdateJob',
			new UpdateJob( $title )
		);

		// FIXME Delete SMWUpdateJob assertion after all
		// references to SMWUpdateJob have been removed
		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\UpdateJob',
			new \SMWUpdateJob( $title )
		);
	}

	public function testDefaultContext() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UpdateJob( $title );

		$this->assertInstanceOf(
			'\SMW\ContextResource',
			$instance->withContext()
		);
	}

	public function testJobWithMissingParserOutput() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->assertFalse(
			$this->acquireInstance( $title )->run(),
			'Asserts to return false due to a missing ParserOutput object'
		);
	}

	public function testJobWithInvalidTitle() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( false ) );

		$instance  = $this->acquireInstance( $title );

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'ContentParser', null );

		$this->assertTrue( $instance->run() );
	}

	public function testJobWithNoRevisionAvailable() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->once() )
			->method( 'getOutput' )
			->will( $this->returnValue( null ) );

		$instance  = $this->acquireInstance( $title );

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'ContentParser', $contentParser );

		$this->assertFalse( $instance->run() );
	}

	public function testJobWithValidRevision() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getDBkey' )
			->will( $this->returnValue( __METHOD__ ) );

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->will( $this->returnValue( new \ParserOutput ) );

		$instance  = $this->acquireInstance( $title );

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'ContentParser', $contentParser );

		$this->assertTrue( $instance->run() );
	}

	/**
	 * @return UpdateJob
	 */
	private function acquireInstance( Title $title = null ) {

		if ( $title === null ) {
			$title = Title::newFromText( __METHOD__ );
		}

		$settings = Settings::newFromArray( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false // false in order to avoid having jobs being inserted
		) );

		$mockStore = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$context   = new ExtensionContext();

		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $mockStore );
		$container->registerObject( 'Settings', $settings );

		$instance = new UpdateJob( $title );
		$instance->invokeContext( $context );
		$instance->setJobQueueEnabledState( false );

		return $instance;
	}

}
