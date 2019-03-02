<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use SMW\Services\ImporterServiceFactory;
use SMW\Settings;

/**
 * @covers \SMW\Services\ImporterServiceFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ImporterServiceFactoryTest extends \PHPUnit_Framework_TestCase {

	private $containerBuilder;

	protected function setUp() {
		parent::setUp();

		$callbackContainerFactory = new CallbackContainerFactory();

		$this->containerBuilder = $callbackContainerFactory->newCallbackContainerBuilder();

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->containerBuilder->registerObject( 'TitleFactory', $titleFactory );

		$importStringSource = $this->getMockBuilder( '\ImportStringSource' )
			->disableOriginalConstructor()
			->getMock();

		$this->containerBuilder->registerObject( 'ImportStringSource', $importStringSource );

		$wikiImporter = $this->getMockBuilder( '\WikiImporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->containerBuilder->registerObject( 'WikiImporter', $wikiImporter );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->containerBuilder->registerObject( 'ConnectionManager', $connectionManager );

		$this->containerBuilder->registerObject( 'Settings', new Settings( [
			'smwgImportReqVersion' => 1,
			'smwgImportFileDirs' => 'foo'
		] ) );

		$this->containerBuilder->registerFromFile( $GLOBALS['smwgServicesFileDir'] . '/' . 'ImporterServices.php' );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ImporterServiceFactory::class,
			new ImporterServiceFactory( $this->containerBuilder )
		);
	}

	public function testCanConstructImportStringSource() {

		$instance = new ImporterServiceFactory(
			$this->containerBuilder
		);

		$this->assertInstanceOf(
			'\ImportStringSource',
			$instance->newImportStringSource( 'Foo' )
		);
	}

	public function testCanConstructWikiImporter() {

		$importSource = $this->getMockBuilder( '\ImportSource' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ImporterServiceFactory(
			$this->containerBuilder
		);

		$this->assertInstanceOf(
			'\WikiImporter',
			$instance->newWikiImporter( $importSource )
		);
	}

	public function testCanConstructImporter() {

		$contentIterator = $this->getMockBuilder( '\SMW\Importer\ContentIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ImporterServiceFactory(
			$this->containerBuilder
		);

		$this->assertInstanceOf(
			'\SMW\Importer\Importer',
			$instance->newImporter( $contentIterator )
		);
	}

	public function testCanConstructJsonContentIterator() {

		$instance = new ImporterServiceFactory(
			$this->containerBuilder
		);

		$this->assertInstanceOf(
			'\SMW\Importer\JsonContentIterator',
			$instance->newJsonContentIterator( 'Foo' )
		);
	}

}
