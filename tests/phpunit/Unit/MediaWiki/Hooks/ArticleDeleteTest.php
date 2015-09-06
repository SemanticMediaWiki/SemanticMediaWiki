<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\Utils\Mock\MockSuperUser;

use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\ApplicationFactory;
use SMW\Settings;

use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleDelete
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDeleteTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->getSettings()->set( 'smwgEnableUpdateJobs', false );
		$this->applicationFactory->getSettings()->set( 'smwgDeleteSubjectWithAssociatesRefresh', false );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

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

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new ArticleDelete(
			$wikiPage,
			$user,
			$reason,
			$error
		);

		$this->assertTrue( $instance->process() );
	}

}
