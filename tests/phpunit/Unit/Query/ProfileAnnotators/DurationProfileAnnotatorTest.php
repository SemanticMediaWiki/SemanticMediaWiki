<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotators\DurationProfileAnnotator;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Tests\Utils\UtilityFactory;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotators\DurationProfileAnnotator
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

		$profileAnnotator = $this->getMockBuilder( '\SMW\Query\ProfileAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotators\DurationProfileAnnotator',
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

		$provider = [];

		$provider[] = [ 0, [
			'propertyCount' => 0
		] ];

		$provider[] = [ 0.9001, [
			'propertyCount'  => 1,
			'propertyKeys'   => [ '_ASKDU' ],
			'propertyValues' => [ 0.9001 ]
		] ];

		return $provider;
	}

}
