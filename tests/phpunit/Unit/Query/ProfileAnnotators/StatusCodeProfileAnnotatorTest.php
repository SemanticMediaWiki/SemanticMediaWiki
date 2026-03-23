<?php

namespace SMW\Tests\Unit\Query\ProfileAnnotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Container;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\Query\ProfileAnnotator;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Query\ProfileAnnotators\StatusCodeProfileAnnotator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\ProfileAnnotators\StatusCodeProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class StatusCodeProfileAnnotatorTest extends TestCase {

	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataValidator = TestEnvironment::newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$profileAnnotator = $this->getMockBuilder( ProfileAnnotator::class )
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
		$subject = new WikiPage( __METHOD__, NS_MAIN, '', '_QUERYe7d20a88' );

		$container = new Container(
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
