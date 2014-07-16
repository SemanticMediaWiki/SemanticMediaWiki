<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Util\Mock\MockTitle;
use SMW\Tests\Util\Mock\MockSuperUser;

use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\Application;
use SMW\Settings;

use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleDelete
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDeleteTest extends \PHPUnit_Framework_TestCase {

	private $application;

	protected function setUp() {
		parent::setUp();

		$this->application = Application::getInstance();
		$this->application->getSettings()->set( 'smwgEnableUpdateJobs', false );
		$this->application->getSettings()->set( 'smwgDeleteSubjectWithAssociatesRefresh', false );
	}

	protected function tearDown() {
		$this->application->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$reason = '';
		$error = '';

		$instance = new ArticleDelete(
			$wikiPage,
			$user,
			$reason,
			$error
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\ArticleDelete',
			$instance
		);
	}

	public function testProcess() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'deleteSubject' );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( __METHOD__ ) ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$reason = '';
		$error = '';

		$this->application->registerObject( 'Store', $store );

		$instance = new ArticleDelete(
			$wikiPage,
			$user,
			$reason,
			$error
		);

		$this->assertTrue( $instance->process() );
	}

}
