<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use SMW\Settings;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ImporterServicesContainerBuildTest extends \PHPUnit_Framework_TestCase {

	private $callbackContainerFactory;
	private $connectionManager;
	private $servicesFileDir;
	private $titleFactory;

	protected function setUp() {
		parent::setUp();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

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

		$contentIterator = $this->getMockBuilder( '\SMW\Importer\ContentIterator' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			'Importer',
			[ $contentIterator ],
			'\SMW\Importer\Importer'
		];

		$provider[] = [
			'JsonContentIterator',
			[ 'SomeDirectory' ],
			'\SMW\Importer\JsonContentIterator'
		];

		$provider[] = [
			'ImporterServiceFactory',
			[],
			'\SMW\Services\ImporterServiceFactory'
		];

		$provider[] = [
			'XmlContentCreator',
			[],
			'\SMW\Importer\ContentCreators\XmlContentCreator'
		];

		$provider[] = [
			'TextContentCreator',
			[],
			'\SMW\Importer\ContentCreators\TextContentCreator'
		];

		return $provider;
	}

}
