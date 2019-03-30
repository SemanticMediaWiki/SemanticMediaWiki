<?php

namespace SMW\Tests\Property\Constraint\Constraints;

use SMW\Property\Constraint\Constraints\CommonConstraint;
use SMW\Tests\TestEnvironment;
use SMW\DataItemFactory;

/**
 * @covers \SMW\Property\Constraint\Constraints\CommonConstraint
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CommonConstraintTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CommonConstraint::class,
			new CommonConstraint()
		);
	}

	public function testIsType() {

		$instance = new CommonConstraint();

		$this->assertTrue(
			$instance->isType( CommonConstraint::TYPE_INSTANT )
		);
	}

	public function testHasViolation() {

		$instance = new CommonConstraint();

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_allowed_namespaces() {

		$constraint = [
			'allowed_namespaces' => [ 'NS_HELP' ]
		];

		$expectedErrMsg = 'smw-constraint-schema-violation-allowed-namespace-no-match';

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'addErrorMsg' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addErrorMsg' )
			->with( $this->callback( function( $error ) use ( $expectedErrMsg ) {
				return $this->checkConstraintError( $error, $expectedErrMsg );
			} ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Bar' ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( 'Foo' ) ) );

		$instance = new CommonConstraint();

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function checkConstraintError( $error, $expectedErrMsg ) {

		if ( strpos( $error->__toString(), $expectedErrMsg ) !== false ) {
			return true;
		}

		return false;
	}

}
