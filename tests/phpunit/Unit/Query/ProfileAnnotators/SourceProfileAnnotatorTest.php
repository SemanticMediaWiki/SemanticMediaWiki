<?php

namespace SMW\Tests\Unit\Query\ProfileAnnotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Container;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\Query\ProfileAnnotator;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Query\ProfileAnnotators\SourceProfileAnnotator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\ProfileAnnotators\SourceProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SourceProfileAnnotatorTest extends TestCase {

	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$profileAnnotator = $this->getMockBuilder( ProfileAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SourceProfileAnnotator::class,
			new SourceProfileAnnotator( $profileAnnotator )
		);
	}

	/**
	 * @dataProvider sourceDataProvider
	 */
	public function testCreateProfile( $source, $expected ) {
		$subject = new WikiPage( __METHOD__, NS_MAIN, '', '_QUERYe7d20a88' );

		$container = new Container(
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
