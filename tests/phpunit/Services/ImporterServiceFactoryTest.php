<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\Importer\ContentIterator;
use SMW\Importer\Importer;
use SMW\Importer\JsonContentIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\TitleFactory;
use SMW\Services\ImporterServiceFactory;
use SMW\Settings;

/**
 * @covers \SMW\Services\ImporterServiceFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ImporterServiceFactoryTest extends TestCase {

	private $callbackContainerBuilder;

	protected function setUp(): void {
		parent::setUp();

		$callbackContainerFactory = new CallbackContainerFactory();

		$this->callbackContainerBuilder = $callbackContainerFactory->newCallbackContainerBuilder();

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->callbackContainerBuilder->registerObject( 'TitleFactory', $titleFactory );

		$importStringSource = $this->getMockBuilder( '\ImportStringSource' )
			->disableOriginalConstructor()
			->getMock();

		$this->callbackContainerBuilder->registerObject( 'ImportStringSource', $importStringSource );

		$wikiImporter = $this->getMockBuilder( '\WikiImporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->callbackContainerBuilder->registerObject( 'WikiImporter', $wikiImporter );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->callbackContainerBuilder->registerObject( 'ConnectionManager', $connectionManager );

		$this->callbackContainerBuilder->registerObject( 'Settings', new Settings( [
			'smwgImportReqVersion' => 1,
			'smwgImportFileDirs' => 'foo'
		] ) );

		$this->callbackContainerBuilder->registerFromFile( $GLOBALS['smwgServicesFileDir'] . '/' . 'importer.php' );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ImporterServiceFactory::class,
			new ImporterServiceFactory( $this->callbackContainerBuilder )
		);
	}

	public function testCanConstructImportStringSource() {
		$instance = new ImporterServiceFactory(
			$this->callbackContainerBuilder
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
			$this->callbackContainerBuilder
		);

		$this->assertInstanceOf(
			'\WikiImporter',
			$instance->newWikiImporter( $importSource )
		);
	}

	public function testCanConstructImporter() {
		$contentIterator = $this->getMockBuilder( ContentIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ImporterServiceFactory(
			$this->callbackContainerBuilder
		);

		$this->assertInstanceOf(
			Importer::class,
			$instance->newImporter( $contentIterator )
		);
	}

	public function testCanConstructJsonContentIterator() {
		$instance = new ImporterServiceFactory(
			$this->callbackContainerBuilder
		);

		$this->assertInstanceOf(
			JsonContentIterator::class,
			$instance->newJsonContentIterator( 'Foo' )
		);
	}

}
