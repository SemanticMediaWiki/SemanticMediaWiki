<?php

namespace SMW\Tests\Importer\ContentCreators;

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

	private $titleFactory;
	private $connection;
	private $messageReporter;

	protected function setUp() {
		parent::setUp();

		$this->titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
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
			new TextContentCreator( $this->titleFactory, $this->connection )
		);
	}

	public function testCanCreateContentsFor() {

		$instance = new TextContentCreator(
			$this->titleFactory,
			$this->connection
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_TEXT );

		$this->assertTrue(
			$instance->canCreateContentsFor( $importContents )
		);
	}

	public function testCreate() {

		$this->connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback ); }
			) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->will( $this->returnValue( CONTENT_MODEL_TEXT ) );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'doEditContent' );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'newFromText' )
			->will( $this->returnValue( $title ) );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $page ) );

		$instance = new TextContentCreator(
			$this->titleFactory,
			$this->connection
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_TEXT );
		$importContents->setName( 'Foo' );

		$instance->create( $importContents );
	}

	public function testCreate_NotReplaceable() {

		$this->connection->expects( $this->never() )
			->method( 'onTransactionIdle' );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->never() )
			->method( 'doEditContent' );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'newFromText' )
			->will( $this->returnValue( $title ) );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $page ) );

		$instance = new TextContentCreator(
			$this->titleFactory,
			$this->connection
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_TEXT );
		$importContents->setName( 'Foo' );
		$importContents->setOptions( [ 'replaceable' => false ] );

		$instance->create( $importContents );
	}

	public function testCreate_ReplaceableOnCreator() {

		$this->connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback ); }
			) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'equals' )
			->will( $this->returnValue( true ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->will( $this->returnValue( CONTENT_MODEL_TEXT ) );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'doEditContent' );

		$page->expects( $this->atLeastOnce() )
			->method( 'getCreator' )
			->will( $this->returnValue( $user ) );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'newFromText' )
			->will( $this->returnValue( $title ) );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $page ) );

		$instance = new TextContentCreator(
			$this->titleFactory,
			$this->connection
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_TEXT );
		$importContents->setName( 'Foo' );
		$importContents->setOptions( [ 'replaceable' => [ 'LAST_EDITOR' => 'IS_IMPORTER' ] ] );

		$instance->create( $importContents );
	}

	public function testCreate_ReplaceableOnCreator_WithNoAvailableUser() {

		$this->connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback ); }
			) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( false ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->will( $this->returnValue( CONTENT_MODEL_TEXT ) );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'doEditContent' );

		$page->expects( $this->atLeastOnce() )
			->method( 'getCreator' )
			->will( $this->returnValue( null ) );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'newFromText' )
			->will( $this->returnValue( $title ) );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $page ) );

		$instance = new TextContentCreator(
			$this->titleFactory,
			$this->connection
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_TEXT );
		$importContents->setName( 'Foo' );
		$importContents->setOptions( [ 'replaceable' => [ 'LAST_EDITOR' => 'IS_IMPORTER' ] ] );

		$instance->create( $importContents );
	}

}
