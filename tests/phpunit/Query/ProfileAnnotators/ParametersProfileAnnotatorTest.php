<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Container;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\Query\ProfileAnnotator;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Query\ProfileAnnotators\ParametersProfileAnnotator;
use SMW\Query\Query;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\Query\ProfileAnnotators\ParametersProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ParametersProfileAnnotatorTest extends TestCase {

	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$profileAnnotator = $this->getMockBuilder( ProfileAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ParametersProfileAnnotator::class,
			new ParametersProfileAnnotator( $profileAnnotator, $query )
		);
	}

	public function testCreateProfile() {
		$subject = new WikiPage( __METHOD__, NS_MAIN, '', 'foo' );

		$container = new Container(
			new ContainerSemanticData( $subject	)
		);

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getLimit' )
			->willReturn( 42 );

		$query->expects( $this->once() )
			->method( 'getOffset' )
			->willReturn( 0 );

		$query->expects( $this->once() )
			->method( 'getQueryMode' )
			->willReturn( 1 );

		$instance = new ParametersProfileAnnotator(
			new NullProfileAnnotator( $container ),
			$query
		);

		$instance->addAnnotation();

		$expected = [
			'propertyCount'  => 1,
			'propertyKeys'   => [ '_ASKPA' ],
			'propertyValues' => [ '{"limit":42,"offset":0,"sort":[],"order":[],"mode":1}' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);
	}

}
