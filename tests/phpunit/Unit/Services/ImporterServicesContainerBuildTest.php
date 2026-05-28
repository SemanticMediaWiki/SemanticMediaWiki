<?php

namespace SMW\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\Importer\ContentCreators\TextContentCreator;
use SMW\Importer\ContentCreators\XmlContentCreator;
use SMW\Importer\ContentIterator;
use SMW\Importer\Importer;
use SMW\Importer\JsonContentIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\Services\ImporterServiceFactory;
use SMW\Settings;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ImporterServicesContainerBuildTest extends TestCase {

	private TestEnvironment $testEnvironment;
	private $connectionManager;
	private $servicesFileDir;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->servicesFileDir = $GLOBALS['smwgServicesFileDir'];
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider servicesProvider
	 */
	public function testCanConstruct( $service, $parameters, $expected ) {
		$this->testEnvironment->registerObject( 'ConnectionManager', $this->connectionManager );

		$this->testEnvironment->registerObject( 'Settings', new Settings( [
			'smwgImportReqVersion' => 1,
			'smwgImportFileDirs' => [ 'foo' ]
		] ) );

		$servicesContainer = ImporterServiceFactory::newServicesContainer( $this->servicesFileDir );

		$this->assertInstanceOf(
			$expected,
			$servicesContainer->create( $service, $servicesContainer, ...$parameters )
		);
	}

	public function servicesProvider() {
		$contentIterator = $this->getMockBuilder( ContentIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			'Importer',
			[ $contentIterator ],
			Importer::class
		];

		$provider[] = [
			'JsonContentIterator',
			[ 'SomeDirectory' ],
			JsonContentIterator::class
		];

		$provider[] = [
			'XmlContentCreator',
			[],
			XmlContentCreator::class
		];

		$provider[] = [
			'TextContentCreator',
			[],
			TextContentCreator::class
		];

		return $provider;
	}

	public function testNewServicesContainerBuildsImporterServiceFactory() {
		$servicesContainer = ImporterServiceFactory::newServicesContainer( $this->servicesFileDir );

		$this->assertInstanceOf(
			ImporterServiceFactory::class,
			new ImporterServiceFactory( $servicesContainer )
		);
	}

}
