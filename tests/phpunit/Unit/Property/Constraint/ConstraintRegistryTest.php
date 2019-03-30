<?php

namespace SMW\Tests\Property\Constraint;

use SMW\Property\Constraint\ConstraintRegistry;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Constraint\ConstraintRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintRegistryTest extends \PHPUnit_Framework_TestCase {

	private $constraintFactory;

	protected function setUp() {

		$this->constraintFactory = $this->getMockBuilder( '\SMW\Property\ConstraintFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintRegistry::class,
			new ConstraintRegistry( $this->constraintFactory )
		);
	}

	public function testGetConstraintByUnkownKey() {

		$constraint = $this->getMockBuilder( '\SMW\Property\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$this->constraintFactory->expects( $this->atLeastOnce() )
			->method( 'newConstraintByClass' )
			->with( $this->equalTo( 'SMW\Property\Constraint\Constraints\NullConstraint' ) )
			->will( $this->returnValue( $constraint ) );

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->getConstraintByKey( '__unknown__' );
	}

	public function testRegisterConstraintWithInstance() {

		$constraint = $this->getMockBuilder( '\SMW\Property\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->registerConstraint( 'foo', $constraint );

		$this->assertInstanceOf(
			'\SMW\Property\Constraint\Constraint',
			$instance->getConstraintByKey( 'foo' )
		);
	}

	public function testRegisterConstraintWithCallable() {

		$constraint = $this->getMockBuilder( '\SMW\Property\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->registerConstraint( 'foo', function() use( $constraint ) {
			return $constraint; }
		);

		$this->assertInstanceOf(
			'\SMW\Property\Constraint\Constraint',
			$instance->getConstraintByKey( 'foo' )
		);
	}

	public function testRegisterConstraintWithClassReference() {

		$constraint = $this->getMockBuilder( '\SMW\Property\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$this->constraintFactory->expects( $this->atLeastOnce() )
			->method( 'newConstraintByClass' )
			->with( $this->equalTo( '__class__' ) )
			->will( $this->returnValue( $constraint ) );

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->registerConstraint( 'foo', '__class__' );

		$this->assertInstanceOf(
			'\SMW\Property\Constraint\Constraint',
			$instance->getConstraintByKey( 'foo' )
		);
	}

	/**
	 * @dataProvider constraintKeyProvider
	 */
	public function testGetConstraintByKey( $key, $expected ) {

		$this->constraintFactory->expects( $this->atLeastOnce() )
			->method( 'newConstraintByClass' )
			->with( $this->equalTo( $expected ) );

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->getConstraintByKey( $key );
	}

	public function constraintKeyProvider() {

		yield[
			'allowed_namespaces',
			'SMW\Property\Constraint\Constraints\CommonConstraint'
		];
	}

}
