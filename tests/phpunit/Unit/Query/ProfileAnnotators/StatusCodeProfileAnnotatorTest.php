<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Query\ProfileAnnotators\StatusCodeProfileAnnotator;
use SMW\Tests\TestEnvironment;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotators\StatusCodeProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class StatusCodeProfileAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = TestEnvironment::newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$profileAnnotator = $this->getMockBuilder( '\SMW\Query\ProfileAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			StatusCodeProfileAnnotator::class,
			new StatusCodeProfileAnnotator( $profileAnnotator )
		);
	}

	/**
	 * @dataProvider codesDataProvider
	 */
	public function testCreateProfile( $codes, $expected ) {

		$subject = new DIWikiPage( __METHOD__, NS_MAIN, '', '_QUERYe7d20a88' );

		$container = new DIContainer(
			new ContainerSemanticData( $subject	)
		);

		$instance = new StatusCodeProfileAnnotator(
			new NullProfileAnnotator( $container ),
			$codes
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function codesDataProvider() {

		$provider[] = [
			[],
			[
				'propertyCount' => 0
			]
		];

		$provider[] = [
			[ 100 ],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => [ '_ASKCO' ],
				'propertyValues' => [ 100 ]
			]
		];

		return $provider;
	}

}
