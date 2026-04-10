<?php

namespace SMW\Tests\Unit\Schema;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\HookDispatcher;
use SMW\Schema\Exception\SchemaTypeNotFoundException;
use SMW\Schema\SchemaDefinition;
use SMW\Schema\SchemaFactory;
use SMW\Schema\SchemaFilterFactory;
use SMW\Schema\SchemaFinder;
use SMW\Schema\SchemaValidator;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Schema\SchemaFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaFactoryTest extends TestCase {

	private $testEnvironment;
	private $jobQueue;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
		$this->testEnvironment->registerObject( 'HookDispatcher', $hookDispatcher );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$instance = new SchemaFactory();

		$this->assertInstanceof(
			SchemaFactory::class,
			$instance
		);
	}

	public function testCanConstructSchemaValidator() {
		$instance = new SchemaFactory();

		$this->assertInstanceof(
			SchemaValidator::class,
			$instance->newSchemaValidator()
		);
	}

	public function testCanConstructSchemaFinder() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new SchemaFactory();

		$this->assertInstanceof(
			SchemaFinder::class,
			$instance->newSchemaFinder( $store )
		);

		$this->assertInstanceof(
			SchemaFinder::class,
			$instance->newSchemaFinder()
		);
	}

	public function testCanConstructSchemaFilterFactory() {
		$instance = new SchemaFactory();

		$this->assertInstanceof(
			SchemaFilterFactory::class,
			$instance->newSchemaFilterFactory()
		);
	}

	public function testNewSchemaDefinition() {
		$instance = new SchemaFactory(
			[
				'foo' => [ 'group' => 'f_group' ]
			]
		);

		$this->assertInstanceof(
			SchemaDefinition::class,
			$instance->newSchema( 'foo_bar', [ 'type' => 'foo' ] )
		);
	}

	public function testNewSchemaDefinitionOnUnknownTypeThrowsException() {
		$instance = new SchemaFactory();

		$this->expectException( SchemaTypeNotFoundException::class );
		$instance->newSchema( 'foo_bar', [ 'type' => 'foo' ] );
	}

	public function testNewSchemaDefinitionOnNoTypeThrowsException() {
		$instance = new SchemaFactory(
			[
				'foo' => [ 'group' => 'f_group' ]
			]
		);

		$this->expectException( SchemaTypeNotFoundException::class );
		$instance->newSchema( 'foo_bar', [] );
	}

	public function testPushChangePropagationDispatchJob() {
		$checkJobParameterCallback = static function ( $job ) {
			return $job->getParameter( 'property_key' ) === 'FOO' && $job->hasParameter( 'schema_change_propagation' );
		};

		$this->jobQueue->expects( $this->once() )
			->method( 'lazyPush' )
			->with( $this->callback( $checkJobParameterCallback ) );

		$instance = new SchemaFactory(
			[
				'foo' => [
					'group' => 'f_group',
					'change_propagation' => [ 'FOO' ]
				]
			]
		);

		$schema = $instance->newSchema( 'foo_bar', [ 'type' => 'foo' ] );

		$instance->pushChangePropagationDispatchJob( $schema );
	}

	public function testPushChangePropagationDispatchJob_CastAsArray() {
		$checkJobParameterCallback = static function ( $job ) {
			return $job->getParameter( 'property_key' ) === 'FOO' && $job->hasParameter( 'schema_change_propagation' );
		};

		$this->jobQueue->expects( $this->once() )
			->method( 'lazyPush' )
			->with( $this->callback( $checkJobParameterCallback ) );

		$instance = new SchemaFactory(
			[
				'foo' => [
					'group' => 'f_group',
					'change_propagation' => 'FOO' // needs to an array
				]
			]
		);

		$schema = $instance->newSchema( 'foo_bar', [ 'type' => 'foo' ] );

		$instance->pushChangePropagationDispatchJob( $schema );
	}

}
