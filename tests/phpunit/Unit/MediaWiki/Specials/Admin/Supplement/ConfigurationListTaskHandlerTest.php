<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin\Supplement;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\Supplement\ConfigurationListTaskHandler;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\ConfigurationListTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ConfigurationListTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter->expects( $this->any() )
			->method( 'encodeAsJson' )
			->willReturn( '' );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConfigurationListTaskHandler::class,
			new ConfigurationListTaskHandler( $this->outputFormatter )
		);
	}

	public function testGetHtml() {
		$instance = new ConfigurationListTaskHandler(
			$this->outputFormatter
		);

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function testHandleRequest() {
		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new ConfigurationListTaskHandler(
			$this->outputFormatter
		);

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

	public function testHandleRequestKeepsElasticsearchCredentialsOutOfTheOutput() {
		$settings = ApplicationFactory::getInstance()->getSettings()->toArray();
		$settings['smwgElasticsearchCredentials'] = [ 'user' => 'elastic', 'pass' => 'wLcJ4jPjKk6MsQ2r' ];

		$this->testEnvironment->registerObject( 'Settings', Settings::newFromArray( $settings ) );

		$rendered = '';

		$outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$outputFormatter->expects( $this->any() )
			->method( 'encodeAsJson' )
			->willReturnCallback( static function ( $value ) use ( &$rendered ) {
				$rendered .= json_encode( $value );
				return '';
			} );

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConfigurationListTaskHandler(
			$outputFormatter
		);

		$instance->handleRequest( $webRequest );

		$this->assertStringNotContainsString(
			'wLcJ4jPjKk6MsQ2r',
			$rendered
		);
	}

}
