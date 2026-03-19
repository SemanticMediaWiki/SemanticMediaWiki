<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Container;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\Query\ProfileAnnotator;
use SMW\Query\ProfileAnnotators\DurationProfileAnnotator;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\Query\ProfileAnnotators\DurationProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class DurationProfileAnnotatorTest extends TestCase {

	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$profileAnnotator = $this->getMockBuilder( ProfileAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DurationProfileAnnotator::class,
			new DurationProfileAnnotator( $profileAnnotator, 0.42 )
		);
	}

	/**
	 * @dataProvider durationDataProvider
	 */
	public function testCreateProfile( $duration, $expected ) {
		$subject = new WikiPage( __METHOD__, NS_MAIN, '', 'foo' );

		$container = new Container(
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
