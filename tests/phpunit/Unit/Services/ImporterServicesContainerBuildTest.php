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
	private $servicesFileDir;
	private $pageCreator;

	protected function setUp() {
		parent::setUp();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$this->databaseConnectionProvider = $this->getMockBuilder( '\SMW\MediaWiki\DatabaseConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$this->databaseConnectionProvider->expects( $this->any() )
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

		$containerBuilder->registerObject( 'PageCreator', $this->pageCreator );
		$containerBuilder->registerObject( 'DatabaseConnectionProvider', $this->databaseConnectionProvider );

		$containerBuilder->registerObject( 'Settings', new Settings( array(
			'smwgImportReqVersion' => 1,
			'smwgImportFileDir' => 'foo'
		) ) );

		$containerBuilder->registerFromFile( $this->servicesFileDir . '/' . 'ImporterServices.php' );

		$this->assertInstanceOf(
			$expected,
			call_user_func_array( array( $containerBuilder, 'create' ), $parameters )
		);
	}

	public function servicesProvider() {

		$contentIterator = $this->getMockBuilder( '\SMW\Importer\ContentIterator' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = array(
			'Importer',
			array( $contentIterator ),
			'\SMW\Importer\Importer'
		);

		$provider[] = array(
			'JsonContentIterator',
			array( 'SomeDirectory' ),
			'\SMW\Importer\JsonContentIterator'
		);

		$provider[] = array(
			'ImporterServiceFactory',
			array(),
			'\SMW\Services\ImporterServiceFactory'
		);

		$provider[] = array(
			'XmlContentCreator',
			array(),
			'\SMW\Importer\ContentCreators\XmlContentCreator'
		);

		$provider[] = array(
			'TextContentCreator',
			array(),
			'\SMW\Importer\ContentCreators\TextContentCreator'
		);

		return $provider;
	}

}
