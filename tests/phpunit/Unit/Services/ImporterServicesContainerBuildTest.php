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

		$this->pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

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

		$containerBuilder->registerObject( 'Settings', new Settings( [
			'smwgImportReqVersion' => 1,
			'smwgImportFileDir' => 'foo'
		] ) );

		$containerBuilder->registerFromFile( $this->servicesFileDir . '/' . 'ImporterServices.php' );

		$this->assertInstanceOf(
			$expected,
			call_user_func_array( [ $containerBuilder, 'create' ], $parameters )
		);
	}

	public function servicesProvider() {

		$importContentsIterator = $this->getMockBuilder( '\SMW\Importer\ImportContentsIterator' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			'ContentsImporter',
			[ $importContentsIterator ],
			'\SMW\Importer\ContentsImporter'
		];

		$provider[] = [
			'JsonImportContentsIterator',
			[],
			'\SMW\Importer\JsonImportContentsIterator'
		];

		$provider[] = [
			'JsonContentsImporter',
			[],
			'\SMW\Importer\ContentsImporter'
		];

		return $provider;
	}

}
