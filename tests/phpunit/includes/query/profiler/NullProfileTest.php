<?php

namespace SMW\Test;

use SMW\HashIdGenerator;
use SMW\Query\Profiler\NullProfile;
use SMW\Subobject;

/**
 * @covers \SMW\Query\Profiler\NullProfile
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
class NullProfileTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Query\Profiler\NullProfile';
	}

	/**
	 * @since 1.9
	 *
	 * @return NullProfile
	 */
	private function newInstance() {
		return new NullProfile(
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
		$instance->addAnnotation();

		$this->assertInstanceOf( '\SMW\DIProperty', $instance->getProperty() );
		$this->assertInstanceOf( '\SMWDIContainer', $instance->getContainer() );
		$this->assertInstanceOf( '\SMWContainerSemanticData', $instance->getSemanticData() );
		$this->assertEmpty( $instance->getErrors() );

	}

}
