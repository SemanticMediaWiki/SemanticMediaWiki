<?php

namespace SMW\Tests\Property\Constraint;

use SMW\Property\Constraint\ConstraintCheckRunner;
use SMW\Property\Constraint\Constraint;
use SMW\Schema\SchemaDefinition;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Constraint\ConstraintCheckRunner
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintCheckRunnerTest extends \PHPUnit_Framework_TestCase {

	private $constraintRegistry;

	protected function setUp() {

		$this->constraintRegistry = $this->getMockBuilder( '\SMW\Property\Constraint\ConstraintRegistry' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintCheckRunner::class,
			new ConstraintCheckRunner( $this->constraintRegistry )
		);
	}

	public function testCheck_Instance() {

		$constraint = $this->getMockBuilder( '\SMW\Property\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$constraint->expects( $this->atLeastOnce() )
			->method( 'isType' )
			->with( $this->equalTo( Constraint::TYPE_DEFERRED ) )
			->will( $this->returnValue( false ) );

		$constraint->expects( $this->atLeastOnce() )
			->method( 'checkConstraint' )
			->with(
				$this->equalTo( [ 'foo_bar' => [] ] ),
			 	$this->equalTo( '__value__' ) )
			->will( $this->returnValue( false ) );

		$constraint->expects( $this->atLeastOnce() )
			->method( 'hasViolation' )
			->will( $this->returnValue( true ) );

		$this->constraintRegistry->expects( $this->atLeastOnce() )
			->method( 'getConstraintByKey' )
			->with( $this->equalTo( 'foo_bar' ) )
			->will( $this->returnValue( $constraint ) );

		$instance = new ConstraintCheckRunner(
			$this->constraintRegistry
		);

		$data = [
			'constraints' => [
				'foo_bar' => []
			]
		];

		$this->assertFalse(
			$instance->hasViolation()
		);

		$instance->load( '_FOO', new SchemaDefinition( 'test', $data ) );
		$instance->check( '__value__' );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function testCheck_Deferred() {

		$constraint = $this->getMockBuilder( '\SMW\Property\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$constraint->expects( $this->atLeastOnce() )
			->method( 'isType' )
			->with( $this->equalTo( Constraint::TYPE_DEFERRED ) )
			->will( $this->returnValue( true ) );

		$constraint->expects( $this->never() )
			->method( 'checkConstraint' );

		$this->constraintRegistry->expects( $this->atLeastOnce() )
			->method( 'getConstraintByKey' )
			->with( $this->equalTo( 'foo_bar' ) )
			->will( $this->returnValue( $constraint ) );

		$instance = new ConstraintCheckRunner(
			$this->constraintRegistry
		);

		$data = [
			'constraints' => [
				'foo_bar' => []
			]
		];

		$this->assertFalse(
			$instance->hasDeferrableConstraint()
		);

		$instance->load( '_FOO', new SchemaDefinition( 'test', $data ) );
		$instance->check( '__value__' );

		$this->assertTrue(
			$instance->hasDeferrableConstraint()
		);
	}

}
