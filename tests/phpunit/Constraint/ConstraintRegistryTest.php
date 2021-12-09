<?php

namespace SMW\Tests\Constraint;

use SMW\Constraint\ConstraintRegistry;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Constraint\ConstraintRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintRegistryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $constraintFactory;
	private $hookDispatcher;

	protected function setUp() : void {

		$this->constraintFactory = $this->getMockBuilder( '\SMW\ConstraintFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintRegistry::class,
			new ConstraintRegistry( $this->constraintFactory )
		);
	}

	public function testGetConstraintKeys() {

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$this->assertInternalType(
			'array',
			$instance->getConstraintKeys()
		);
	}

	public function testRunHookOnInitConstraints() {

		$this->hookDispatcher->expects( $this->once() )
			->method( 'onInitConstraints' );

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->getConstraintKeys();
	}

	public function testGetConstraintByUnkownKey() {

		$constraint = $this->getMockBuilder( '\SMW\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$this->constraintFactory->expects( $this->atLeastOnce() )
			->method( 'newConstraintByClass' )
			->with( $this->equalTo( 'SMW\Constraint\Constraints\NullConstraint' ) )
			->will( $this->returnValue( $constraint ) );

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->getConstraintByKey( '__unknown__' );
	}

	public function testRegisterConstraintWithInstance() {

		$constraint = $this->getMockBuilder( '\SMW\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->registerConstraint( 'foo', $constraint );

		$this->assertInstanceOf(
			'\SMW\Constraint\Constraint',
			$instance->getConstraintByKey( 'foo' )
		);
	}

	public function testRegisterConstraintWithCallable() {

		$constraint = $this->getMockBuilder( '\SMW\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->registerConstraint( 'foo', function() use( $constraint ) {
			return $constraint; }
		);

		$this->assertInstanceOf(
			'\SMW\Constraint\Constraint',
			$instance->getConstraintByKey( 'foo' )
		);
	}

	public function testRegisterConstraintWithClassReference() {

		$constraint = $this->getMockBuilder( '\SMW\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$this->constraintFactory->expects( $this->atLeastOnce() )
			->method( 'newConstraintByClass' )
			->with( $this->equalTo( '__class__' ) )
			->will( $this->returnValue( $constraint ) );

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->registerConstraint( 'foo', '__class__' );

		$this->assertInstanceOf(
			'\SMW\Constraint\Constraint',
			$instance->getConstraintByKey( 'foo' )
		);
	}

	/**
	 * @dataProvider constraintKeyProvider
	 */
	public function testGetConstraintByKey( $key, $expected ) {

		$constraint = $this->getMockBuilder( '\SMW\Constraint\Constraint' )
			->disableOriginalConstructor()
			->getMock();

		$this->constraintFactory->expects( $this->atLeastOnce() )
			->method( 'newConstraintByClass' )
			->with( $this->equalTo( $expected ) )
			->will( $this->returnValue( $constraint ) );

		$instance = new ConstraintRegistry(
			$this->constraintFactory
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->getConstraintByKey( $key );
	}

	public function constraintKeyProvider() {

		yield [
			'allowed_namespaces',
			'SMW\Constraint\Constraints\NamespaceConstraint'
		];

		yield [
			'unique_value_constraint',
			'SMW\Constraint\Constraints\UniqueValueConstraint'
		];

		yield [
			'non_negative_integer',
			'SMW\Constraint\Constraints\NonNegativeIntegerConstraint'
		];

		yield [
			'must_exists',
			'SMW\Constraint\Constraints\MustExistsConstraint'
		];

		yield [
			'mandatory_properties',
			'SMW\Constraint\Constraints\MandatoryPropertiesConstraint'
		];

		yield [
			'shape_constraint',
			'SMW\Constraint\Constraints\ShapeConstraint'
		];
	}

}
