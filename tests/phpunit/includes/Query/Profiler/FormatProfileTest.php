<?php

namespace SMW\Tests\Query\Profiler;

use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Profiler\FormatProfile;
use SMW\Query\Profiler\NullProfile;
use SMW\Subobject;

use Title;

/**
 * @covers \SMW\Query\Profiler\FormatProfile
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FormatProfileTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\Query\Profiler\FormatProfile',
			new FormatProfile( $profileAnnotator, 'table' )
		);
	}

	public function testCreateProfile() {

		$profiler = new NullProfile(
			new Subobject( Title::newFromText( __METHOD__ ) ),
			'foo'
		);

		$instance = new FormatProfile( $profiler, 'table' );
		$instance->addAnnotation();

		$expected = array(
			'propertyCount'  => 1,
			'propertyKeys'   => array( '_ASKFO' ),
			'propertyValues' => array( 'table' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);
	}

}
