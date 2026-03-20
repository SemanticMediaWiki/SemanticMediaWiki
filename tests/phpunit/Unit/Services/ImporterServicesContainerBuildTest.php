<?php

namespace SMW\Tests\Unit\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\Importer\ContentCreators\TextContentCreator;
use SMW\Importer\ContentCreators\XmlContentCreator;
use SMW\Importer\ContentIterator;
use SMW\Importer\Importer;
use SMW\Importer\JsonContentIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\TitleFactory;
use SMW\Services\ImporterServiceFactory;
use SMW\Settings;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ImporterServicesContainerBuildTest extends TestCase {

	private $callbackContainerFactory;
	private $connectionManager;
	private $servicesFileDir;
	private $titleFactory;

	protected function setUp(): void {
		parent::setUp();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->callbackContainerFactory = new CallbackContainerFactory();
		$this->servicesFileDir = $GLOBALS['smwgServicesFileDir'];
	}

	/**
	 * @dataProvider servicesProvider
	 */
	public function testCanConstruct( $service, $parameters, $expected ) {
		array_unshift( $parameters, $service );

		$containerBuilder = $this->callbackContainerFactory->newCallbackContainerBuilder();

		$containerBuilder->registerObject( 'TitleFactory', $this->titleFactory );
		$containerBuilder->registerObject( 'ConnectionManager', $this->connectionManager );

		$containerBuilder->registerObject( 'Settings', new Settings( [
			'smwgImportReqVersion' => 1,
			'smwgImportFileDirs' => [ 'foo' ]
		] ) );

		$containerBuilder->registerFromFile( $this->servicesFileDir . '/' . 'importer.php' );

		$this->assertInstanceOf(
			$expected,
			call_user_func_array( [ $containerBuilder, 'create' ], $parameters )
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
			'ImporterServiceFactory',
			[],
			ImporterServiceFactory::class
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

}
