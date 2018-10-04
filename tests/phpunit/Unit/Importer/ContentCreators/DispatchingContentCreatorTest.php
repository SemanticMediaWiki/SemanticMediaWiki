<?php

namespace SMW\Tests\Importer\ContentCreators;

use SMW\Importer\ContentCreators\DispatchingContentCreator;
use SMW\Importer\ImportContents;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Importer\ContentCreators\DispatchingContentCreator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DispatchingContentCreatorTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $wikiImporter;
	private $messageReporter;

	protected function setUp() {
		parent::setUp();

		$importStreamSource = $this->getMockBuilder( '\ImportStreamSource' )
			->disableOriginalConstructor()
			->getMock();

		$this->wikiImporter = $this->getMockBuilder( '\WikiImporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Importer\ContentCreators\DispatchingContentCreator',
			new DispatchingContentCreator( [] )
		);
	}

	public function testCanCreateContentsFor() {

		$importContents = new ImportContents();
		$importContents->setContentType( 'Foo' );

		$contentCreator = $this->getMockBuilder( '\SMW\Importer\ContentCreator' )
			->disableOriginalConstructor()
			->getMock();

		$contentCreator->expects( $this->any() )
			->method( 'canCreateContentsFor' )
			->with( $this->equalTo( $importContents ) )
			->will( $this->returnValue( true ) );

		$instance = new DispatchingContentCreator(
			[
				$contentCreator
			]
		);

		$this->assertTrue(
			$instance->canCreateContentsFor( $importContents )
		);
	}

	public function testDoCreateFrom() {

		$importContents = new ImportContents();
		$importContents->setContentType( 'Foo' );

		$contentCreator = $this->getMockBuilder( '\SMW\Importer\ContentCreator' )
			->disableOriginalConstructor()
			->getMock();

		$contentCreator->expects( $this->any() )
			->method( 'canCreateContentsFor' )
			->will( $this->returnValue( true ) );

		$contentCreator->expects( $this->any() )
			->method( 'create' )
			->with( $this->equalTo( $importContents ) )
			->will( $this->returnValue( true ) );

		$instance = new DispatchingContentCreator(
			[
				$contentCreator
			]
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$this->assertTrue(
			$instance->create( $importContents )
		);
	}

	public function testDoCreateFromOnNonMatchableCreatorThrowsException() {

		$importContents = new ImportContents();
		$importContents->setContentType( 'Foo' );

		$contentCreator = $this->getMockBuilder( '\SMW\Importer\ContentCreator' )
			->disableOriginalConstructor()
			->getMock();

		$contentCreator->expects( $this->any() )
			->method( 'canCreateContentsFor' )
			->will( $this->returnValue( false ) );

		$contentCreator->expects( $this->never() )
			->method( 'create' );

		$instance = new DispatchingContentCreator(
			[
				$contentCreator
			]
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->create( $importContents );
	}

}
