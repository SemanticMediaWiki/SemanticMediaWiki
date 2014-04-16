<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\Query\Profiler\FormatProfile;
use SMW\Query\Profiler\NullProfile;
use SMW\HashIdGenerator;
use SMW\Subobject;

use Title;

/**
 * @covers \SMW\Query\Profiler\FormatProfile
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
class FormatProfileTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMW\Query\Profiler\FormatProfile';
	}

	/**
	 * @return FormatProfile
	 */
	private function newInstance( $format = 'Foo' ) {

		$profiler = new NullProfile(
			new Subobject( Title::newFromText( __METHOD__ ) ),
			new HashIdGenerator( 'Foo' )
		);

		return new FormatProfile( $profiler, $format );
	}

	/**
	 * @since 1.9
	 */
	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testCreateProfile() {

		$instance = $this->newInstance( 'Foo' );
		$instance->addAnnotation();

		$expected = array(
			'propertyCount'  => 1,
			'propertyKeys'   => array( '_ASKFO' ),
			'propertyValues' => array( 'Foo' )
		);

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);

	}

}
