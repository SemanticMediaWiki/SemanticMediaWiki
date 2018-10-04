<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Query\ProfileAnnotators\SourceProfileAnnotator;
use SMW\Tests\TestEnvironment;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotators\SourceProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SourceProfileAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$profileAnnotator = $this->getMockBuilder( '\SMW\Query\ProfileAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotators\SourceProfileAnnotator',
			new SourceProfileAnnotator( $profileAnnotator )
		);
	}

	/**
	 * @dataProvider sourceDataProvider
	 */
	public function testCreateProfile( $source, $expected ) {

		$subject = new DIWikiPage( __METHOD__, NS_MAIN, '', '_QUERYe7d20a88' );

		$container = new DIContainer(
			new ContainerSemanticData( $subject	)
		);

		$instance = new SourceProfileAnnotator(
			new NullProfileAnnotator( $container ),
			$source
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function sourceDataProvider() {

		$provider = [];

		$provider[] = [ '', [
			'propertyCount' => 0
		] ];

		$provider[] = [ 'foo', [
			'propertyCount'  => 1,
			'propertyKeys'   => [ '_ASKSC' ],
			'propertyValues' => [ 'foo' ]
		] ];

		return $provider;
	}

}
