<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\SchemaUpdateJob;
use SMW\ApplicationFactory;
use SMW\SchemaManager;
use SMW\DIWikiPage;

/**
 * @covers \SMW\MediaWiki\Jobs\SchemaUpdateJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SchemaUpdateJobTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\SchemaUpdateJob',
			new SchemaUpdateJob( $title )
		);
	}

	public function testRun() {

		SchemaManager::getInstance()->registerSchema( 'Foo' );
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $subject->getTitle() ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->applicationFactory->registerObject( 'PageCreator', $pageCreator );

		$instance = new SchemaUpdateJob(
			$subject->getTitle()
		);

		$this->assertTrue(
			$instance->run()
		);

		SchemaManager::getInstance()->clear();
	}

	public function testMapPropertySchema() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $subject->getTitle() ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->once() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->applicationFactory->registerObject( 'PageCreator', $pageCreator );

		$instance = new SchemaUpdateJob(
			$subject->getTitle()
		);

		$properties[] = array(
			'property' => 'Foo',
			'type'     => 'Text'
		);

		$instance->mapPropertySchemaFor( $properties );
	}

	public function testMapCategorySchema() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $subject->getTitle() ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->once() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->applicationFactory->registerObject( 'PageCreator', $pageCreator );

		$instance = new SchemaUpdateJob(
			$subject->getTitle()
		);

		$properties[] = array(
			'category' => 'Foo',
			'type'     => 'Text'
		);

		$instance->mapCategorySchemaFor( $properties );
	}

}
