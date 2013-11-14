<?php

namespace SMW\Test;

use SMW\HashIdGenerator;
use SMW\NullProfiler;
use SMW\Subobject;

/**
 * @covers \SMW\NullProfiler
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullProfilerTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\NullProfiler';
	}

	/**
	 * @since 1.9
	 *
	 * @return NullProfiler
	 */
	private function newInstance() {
		return new NullProfiler(
			new Subobject( $this->newTitle() ),
			new HashIdGenerator( 'Foo' )
		);
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testAvailableMethods() {

		$instance = $this->newInstance();
		$instance->createProfile();

		$this->assertInstanceOf( '\SMW\DIProperty', $instance->getProperty() );
		$this->assertInstanceOf( '\SMWDIContainer', $instance->getContainer() );
		$this->assertInstanceOf( '\SMWContainerSemanticData', $instance->getSemanticData() );
		$this->assertEmpty( $instance->getErrors() );

	}

}
