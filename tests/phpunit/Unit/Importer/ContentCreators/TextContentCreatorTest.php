<?php

namespace SMW\TestsImporter\ContentCreators;

use SMW\Importer\ContentCreators\TextContentCreator;
use SMW\Importer\ImportContents;

/**
 * @covers \SMW\Importer\ContentCreators\TextContentCreator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextContentCreatorTest extends \PHPUnit_Framework_TestCase {

	private $pageCreator;
	private $connection;
	private $messageReporter;

	protected function setUp() {
		parent::setUp();

		$this->pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Importer\ContentCreators\TextContentCreator',
			new TextContentCreator( $this->pageCreator, $this->connection )
		);
	}

	public function testCanCreateContentsFor() {

		$instance = new TextContentCreator(
			$this->pageCreator,
			$this->connection
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_TEXT );

		$this->assertTrue(
			$instance->canCreateContentsFor( $importContents )
		);
	}

	public function testDoCreateFrom() {

		$this->connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback ); }
			) );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'doEditContent' );

		$this->pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $page ) );

		$instance = new TextContentCreator(
			$this->pageCreator,
			$this->connection
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_TEXT );
		$importContents->setName( 'Foo' );

		$instance->doCreateFrom( $importContents );
	}

}
