<?php

namespace SMW\Tests\Query\Profiler;

use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Profiler\DurationProfile;
use SMW\Query\Profiler\NullProfile;
use SMW\Subobject;

use Title;

/**
 * @covers \SMW\Query\Profiler\DurationProfile
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DurationProfileTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$profileAnnotator = $this->getMockBuilder( '\SMW\Query\Profiler\ProfileAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\Profiler\DurationProfile',
			new DurationProfile( $profileAnnotator, 0.42 )
		);
	}

	/**
	 * @dataProvider durationDataProvider
	 */
	public function testCreateProfile( $duration, $expected ) {

		$profiler = new NullProfile(
			new Subobject( Title::newFromText( __METHOD__ ) ),
			'foo'
		);

		$instance = new DurationProfile( $profiler, $duration );
		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);
	}

	public function durationDataProvider() {

		$provider = array();

		$provider[] = array( 0, array(
			'propertyCount' => 0
		) );

		$provider[] = array( 0.9001, array(
			'propertyCount'  => 1,
			'propertyKeys'   => array( '_ASKDU' ),
			'propertyValues' => array( 0.9001 )
		) );

		return $provider;
	}

}
