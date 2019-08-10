<?php

namespace SMW\Tests\Schema;

use SMW\Schema\SchemaFactory;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Schema\SchemaFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $jobQueue;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	protected function tearDown() {
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
			'\SMW\Schema\SchemaValidator',
			$instance->newSchemaValidator()
		);
	}

	public function testCanConstructSchemaFinder() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new SchemaFactory();

		$this->assertInstanceof(
			'\SMW\Schema\SchemaFinder',
			$instance->newSchemaFinder( $store )
		);

		$this->assertInstanceof(
			'\SMW\Schema\SchemaFinder',
			$instance->newSchemaFinder()
		);
	}

	public function testIsRegisteredType() {

		$instance = new SchemaFactory(
			[
				'foo' => []
			]
		);

		$this->assertTrue(
			$instance->isRegisteredType( 'foo' )
		);
	}

	public function testGetRegisteredTypes() {

		$instance = new SchemaFactory(
			[
				'foo' => [],
				'bar' => []
			]
		);

		$this->assertEquals(
			[ 'foo', 'bar' ],
			$instance->getRegisteredTypes()
		);
	}

	public function testGetRegisteredTypesByGroup() {

		$instance = new SchemaFactory(
			[
				'foo' => [ 'group' => 'f_group' ],
				'bar' => [ 'group' => 'b_group' ]
			]
		);

		$this->assertEquals(
			[ 'foo' ],
			$instance->getRegisteredTypesByGroup( 'f_group' )
		);
	}

	public function testNewSchemaDefinition() {

		$instance = new SchemaFactory(
			[
				'foo' => [ 'group' => 'f_group' ]
			]
		);

		$this->assertInstanceof(
			'\SMW\Schema\SchemaDefinition',
			$instance->newSchema( 'foo_bar', [ 'type' => 'foo' ] )
		);
	}

	public function testNewSchemaDefinitionOnUnknownTypeThrowsException() {

		$instance = new SchemaFactory();

		$this->setExpectedException( '\SMW\Schema\Exception\SchemaTypeNotFoundException' );
		$instance->newSchema( 'foo_bar', [ 'type' => 'foo' ] );
	}

	public function testNewSchemaDefinitionOnNoTypeThrowsException() {

		$instance = new SchemaFactory(
			[
				'foo' => [ 'group' => 'f_group' ]
			]
		);

		$this->setExpectedException( '\SMW\Schema\Exception\SchemaTypeNotFoundException' );
		$instance->newSchema( 'foo_bar', [] );
	}

	public function testPushChangePropagationDispatchJob() {

		$checkJobParameterCallback = function( $job ) {
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

		$checkJobParameterCallback = function( $job ) {
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
