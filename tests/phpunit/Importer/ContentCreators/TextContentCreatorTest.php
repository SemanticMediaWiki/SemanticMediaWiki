<?php

namespace SMW\Tests\Importer\ContentCreators;

use SMW\Importer\ContentCreators\TextContentCreator;
use SMW\Importer\ImportContents;
use WikiPage;

/**
 * @covers \SMW\Importer\ContentCreators\TextContentCreator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextContentCreatorTest extends \PHPUnit\Framework\TestCase {

	private $titleFactory;
	private $connection;
	private $messageReporter;

	protected function setUp(): void {
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
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( function ( $callback ) {
				return call_user_func( $callback );
			}
			);

		if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
			$status = $this->getMockBuilder( '\Status' )
			->disableOriginalConstructor()
			->getMock();
		} else {
			$status = $this->getMockBuilder( '\MediaWiki\Storage\PageUpdateStatus' )
			->disableOriginalConstructor()
			->getMock();
		}

		$status->expects( $this->any() )
			->method( 'isOK' )
			->willReturn( true );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->willReturn( CONTENT_MODEL_TEXT );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( self::getDoEditContentMethod() )
			->willReturn( $status );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'newFromText' )
			->willReturn( $title );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $page );

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

	public function testCreate_WithError() {
		$this->connection->expects( $this->once() )
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( function ( $callback ) {
				return call_user_func( $callback );
			}
			);

		if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
			$status = $this->getMockBuilder( '\Status' )
			->disableOriginalConstructor()
			->getMock();
		} else {
			$status = $this->getMockBuilder( '\MediaWiki\Storage\PageUpdateStatus' )
			->disableOriginalConstructor()
			->getMock();
		}

		$status->expects( $this->any() )
			->method( 'isOK' )
			->willReturn( false );

		$status->expects( $this->any() )
			->method( 'getErrorsArray' )
			->willReturn( [ 'FooError', 'BarError' ] );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->willReturn( CONTENT_MODEL_TEXT );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( self::getDoEditContentMethod() )
			->willReturn( $status );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'newFromText' )
			->willReturn( $title );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $page );

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

		$this->assertEquals(
			[ 'FooError', 'BarError' ],
			$importContents->getErrors()
		);
	}

	public function testCreate_NotReplaceable() {
		$this->connection->expects( $this->never() )
			->method( 'onTransactionCommitOrIdle' );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->never() )
			->method( self::getDoEditContentMethod() );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'newFromText' )
			->willReturn( $title );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $page );

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
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( function ( $callback ) {
				return call_user_func( $callback );
			}
			);

		if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
			$status = $this->getMockBuilder( '\Status' )
			->disableOriginalConstructor()
			->getMock();
		} else {
			$status = $this->getMockBuilder( '\MediaWiki\Storage\PageUpdateStatus' )
			->disableOriginalConstructor()
			->getMock();
		}

		$status->expects( $this->any() )
			->method( 'isOK' )
			->willReturn( true );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'equals' )
			->willReturn( true );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->willReturn( CONTENT_MODEL_TEXT );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( self::getDoEditContentMethod() )
			->willReturn( $status );

		$page->expects( $this->atLeastOnce() )
			->method( 'getCreator' )
			->willReturn( $user );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'newFromText' )
			->willReturn( $title );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $page );

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
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( function ( $callback ) {
				return call_user_func( $callback );
			}
			);

		if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
			$status = $this->getMockBuilder( '\Status' )
			->disableOriginalConstructor()
			->getMock();
		} else {
			$status = $this->getMockBuilder( '\MediaWiki\Storage\PageUpdateStatus' )
			->disableOriginalConstructor()
			->getMock();
		}

		$status->expects( $this->any() )
			->method( 'isOK' )
			->willReturn( true );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( false );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->willReturn( CONTENT_MODEL_TEXT );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( self::getDoEditContentMethod() )
			->willReturn( $status );

		$page->expects( $this->atLeastOnce() )
			->method( 'getCreator' )
			->willReturn( null );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'newFromText' )
			->willReturn( $title );

		$this->titleFactory->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $page );

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

	/**
	 * Get the name of the appropriate edit method to mock.
	 * @return string
	 */
	private static function getDoEditContentMethod(): string {
		return method_exists( WikiPage::class, 'doUserEditContent' )
			? 'doUserEditContent' : 'doEditContent';
	}

}
