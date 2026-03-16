<?php

namespace SMW\Tests;

use PHPUnit\Framework\TestCase;
use SMW\Exception\ConfigPreloadFileNotReadableException;
use SMW\SetupCheck;
use SMW\UncaughtExceptionHandler;

/**
 * @covers \SMW\UncaughtExceptionHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class UncaughtExceptionHandlerTest extends TestCase {

	private $setupCheck;

	protected function setUp(): void {
		parent::setUp();

		$this->setupCheck = $this->getMockBuilder( SetupCheck::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UncaughtExceptionHandler::class,
			new UncaughtExceptionHandler( $this->setupCheck )
		);
	}

	public function testConfigPreloadError() {
		$this->setupCheck->expects( $this->once() )
			->method( 'showErrorAndAbort' );

		$this->setupCheck->expects( $this->once() )
			->method( 'setErrorType' )
			->with( SetupCheck::ERROR_CONFIG_PROFILE_UNKNOWN );

		$instance = new UncaughtExceptionHandler(
			$this->setupCheck
		);

		$exception = new ConfigPreloadFileNotReadableException(
			'Foo'
		);

		$instance->registerHandler( $exception );
	}

	public function testExtensionRegistryError() {
		$this->setupCheck->expects( $this->once() )
			->method( 'showErrorAndAbort' );

		$instance = new UncaughtExceptionHandler(
			$this->setupCheck
		);

		$exception = new \Exception(
			'SemanticMediaWiki, extension.json'
		);

		$instance->registerHandler( $exception );
	}

	/**
	 * @dataProvider errorTypeProvider
	 */
	public function testExtensionDependencyError( $args, $expected ) {
		$exception = $this->getMockBuilder( '\ExtensionDependencyError' )
			->setConstructorArgs( [ [ $args ] ] )
			->getMock();

		$this->setupCheck->expects( $this->once() )
			->method( 'showErrorAndAbort' );

		$this->setupCheck->expects( $this->once() )
			->method( 'setErrorType' )
			->with( $expected );

		$instance = new UncaughtExceptionHandler(
			$this->setupCheck
		);

		$instance->registerHandler( $exception );
	}

	public function errorTypeProvider() {
		yield [
			[ 'msg' => 'SemanticFoo', 'type' => 'Foo' ],
			SetupCheck::ERROR_EXTENSION_DEPENDENCY
		];

		yield [
			[ 'msg' => 'SemanticBar', 'type' => 'incompatible-core' ],
			SetupCheck::ERROR_EXTENSION_INCOMPATIBLE
		];

		yield [
			[ 'msg' => 'SemanticFoobar', 'type' => 'incompatible-php' ],
			SetupCheck::ERROR_EXTENSION_INCOMPATIBLE
		];

		yield [
			[ 'msg' => 'SemanticFoOBaR', 'type' => 'incompatible-extensions', 'incompatible' => [] ],
			SetupCheck::ERROR_EXTENSION_INCOMPATIBLE
		];
	}

}
