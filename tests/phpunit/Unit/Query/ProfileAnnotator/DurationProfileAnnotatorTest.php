<?php

namespace SMW\Tests\Query\ProfileAnnotator;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotator\DurationProfileAnnotator;
use SMW\Query\ProfileAnnotator\NullProfileAnnotator;
use SMW\Tests\Utils\UtilityFactory;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotator\DurationProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DurationProfileAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$profileAnnotator = $this->getMockBuilder( '\SMW\Query\ProfileAnnotator\ProfileAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator\DurationProfileAnnotator',
			new DurationProfileAnnotator( $profileAnnotator, 0.42 )
		);
	}

	/**
	 * @dataProvider durationDataProvider
	 */
	public function testCreateProfile( $duration, $expected ) {

		$subject =new DIWikiPage( __METHOD__, NS_MAIN, '', 'foo' );

		$container = new DIContainer(
			new ContainerSemanticData( $subject	)
		);

		$instance = new DurationProfileAnnotator(
			new NullProfileAnnotator( $container ),
			$duration
		);

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
