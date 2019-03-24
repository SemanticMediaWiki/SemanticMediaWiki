<?php

namespace SMW\Tests\DataValues\ValueValidators;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\DataValues\ValueValidators\ConstraintSchemaValueValidator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ValueValidators\ConstraintSchemaValueValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaValueValidatorTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $dataValueFactory;
	private $constraintCheckRunner;
	private $schemafinder;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->dataItemFactory = new DataItemFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();

		$this->constraintCheckRunner = $this->getMockBuilder( '\SMW\Property\Constraint\ConstraintCheckRunner' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemafinder = $this->getMockBuilder( '\SMW\Schema\Schemafinder' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}


	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintSchemaValueValidator::class,
			new ConstraintSchemaValueValidator( $this->constraintCheckRunner, $this->schemafinder )
		);
	}

	public function testHasNoConstraintViolationOnNonRelatedValue() {

		$instance = new ConstraintSchemaValueValidator(
			$this->constraintCheckRunner,
			$this->schemafinder
		);

		$instance->validate( 'Foo' );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function testFetchEmptyConstraintSchemaList() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->schemafinder->expects( $this->once() )
			->method( 'getConstraintSchema' )
			->with( $this->equalTo( $property ) );

		$dataValue = $this->dataValueFactory->newDataValueByProperty(
			$property
		);

		$instance = new ConstraintSchemaValueValidator(
			$this->constraintCheckRunner,
			$this->schemafinder
		);

		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function testRunConstraintCheck() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$dataValue = $this->dataValueFactory->newDataValueByProperty(
			$property
		);

		$dataValue->setContextPage(
			$this->dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN )
		);

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemafinder->expects( $this->once() )
			->method( 'getConstraintSchema' )
			->with( $this->equalTo( $property ) )
			->will( $this->returnValue( $schemaList ) );

		$this->constraintCheckRunner->expects( $this->once() )
			->method( 'load' );

		$this->constraintCheckRunner->expects( $this->once() )
			->method( 'check' )
			->with( $this->equalTo( $dataValue ) );

		$this->constraintCheckRunner->expects( $this->once() )
			->method( 'hasViolation' )
			->will( $this->returnValue( false ) );

		$instance = new ConstraintSchemaValueValidator(
			$this->constraintCheckRunner,
			$this->schemafinder
		);

		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function testRunConstraintCheckTriggerDeferredConstraintCheckUpdateJob() {

		$subject = $this->dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN );
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$dataValue = $this->dataValueFactory->newDataValueByProperty(
			$property
		);

		$dataValue->setContextPage(
			$subject
		);

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemafinder->expects( $this->once() )
			->method( 'getConstraintSchema' )
			->with( $this->equalTo( $property ) )
			->will( $this->returnValue( $schemaList ) );

		$this->constraintCheckRunner->expects( $this->once() )
			->method( 'load' );

		$this->constraintCheckRunner->expects( $this->once() )
			->method( 'check' )
			->with( $this->equalTo( $dataValue ) );

		$this->constraintCheckRunner->expects( $this->once() )
			->method( 'hasViolation' )
			->will( $this->returnValue( false ) );

		$this->constraintCheckRunner->expects( $this->once() )
			->method( 'hasDeferrableConstraint' )
			->will( $this->returnValue( true ) );

		$this->jobQueue->expects( $this->once() )
			->method( 'push' )
			->with( $this->callback( [ $this, 'checkPushedJobInstance' ] ) );

		$instance = new ConstraintSchemaValueValidator(
			$this->constraintCheckRunner,
			$this->schemafinder
		);

		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function checkPushedJobInstance( array $jobs ) {

		foreach ( $jobs as $job ) {
			if ( is_a( $job, '\SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob' ) ) {
				return true;
			}
		}

		return false;
	}

}
