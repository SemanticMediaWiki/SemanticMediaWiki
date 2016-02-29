<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ValueConstraintValidator;
use SMW\DataItemFactory;

/**
 * @covers \SMW\DataValues\ValueConstraintValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ValueConstraintValidatorTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;

	protected function setUp() {
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueConstraintValidator',
			ValueConstraintValidator::newInstance()
		);
	}

	public function testDoValidate() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( array( 'getProperty', 'getDataItem', 'getTypeID' ) )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Bar' ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Foo bar' ) ) );

		$instance = ValueConstraintValidator::newInstance();

		$instance->doValidate( $dataValue );
	}

}
