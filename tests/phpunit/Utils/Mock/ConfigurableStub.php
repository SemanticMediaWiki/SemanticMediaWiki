<?php

namespace SMW\Tests\Utils\Mock;

/**
 * Convenience stub builder, PHPUnit_Framework_TestCase::createConfiguredMock (PHPUnit 5.6)
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 */
class ConfigurableStub extends \PHPUnit_Framework_TestCase {

	/**
	 * @since 2.5
	 *
	 * @param $originalClassName
	 * @param array $configuration
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	public function createConfiguredAbstractStub( $originalClassName, array $configuration ) {

		$o = $this->getMockBuilder( $originalClassName )
			->disableOriginalConstructor()
			->setMethods( array_keys( $configuration ) )
			->getMockForAbstractClass();

		foreach ( $configuration as $method => $returnValue ) {

			if ( $returnValue === null ) {
				continue;
			}

			// PHPUnit 3.8 requires $o->expects( $this->any() )
			$o->expects( $this->any() )->method( $method )->will( $this->returnValue( $returnValue ) );
		}

		return $o;
	}

	/**
	 * @since 2.5
	 *
	 * @param $originalClassName
	 * @param array $configuration
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	public function createConfiguredStub( $originalClassName, array $configuration ) {

		$o = $this->getMockBuilder( $originalClassName )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $configuration as $method => $returnValue ) {

			if ( $returnValue === null ) {
				continue;
			}

			// PHPUnit 3.8 requires $o->expects( $this->any() )
			$o->expects( $this->any() )->method( $method )->will( $this->returnValue( $returnValue ) );
		}

		return $o;
	}

}
