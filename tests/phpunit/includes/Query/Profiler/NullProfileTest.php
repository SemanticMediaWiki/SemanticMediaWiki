<?php

namespace SMW\Tests\Query\Profiler;

use SMW\Query\Profiler\NullProfile;
use SMW\Subobject;

use Title;

/**
 * @covers \SMW\Query\Profiler\NullProfile
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullProfileTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$subobject = $this->getMockBuilder( '\SMW\Subobject' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\Profiler\NullProfile',
			new NullProfile( $subobject, 'abc' )
		);
	}

	public function testMethodAccess() {

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );
		$instance = new NullProfile( $subobject, 'adcb944aa33b2c972470b73964c547c0' );

		$instance->addAnnotation();

		$this->assertInstanceOf(
			'\SMW\DIProperty',
			$instance->getProperty()
		);

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->getContainer()
		);

		$this->assertInstanceOf(
			'\SMWContainerSemanticData',
			$instance->getSemanticData()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);

		$this->assertEquals(
			'_QUERYadcb944aa33b2c972470b73964c547c0',
			$subobject->getSubobjectId()
		);
	}

}
