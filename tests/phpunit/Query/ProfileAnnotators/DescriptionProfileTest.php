<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Container;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\Query\Language\Description;
use SMW\Query\ProfileAnnotator;
use SMW\Query\ProfileAnnotators\DescriptionProfileAnnotator;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\Query\ProfileAnnotators\DescriptionProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class DescriptionProfileTest extends TestCase {

	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$profileAnnotator = $this->getMockBuilder( ProfileAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DescriptionProfileAnnotator::class,
			new DescriptionProfileAnnotator( $profileAnnotator, $description )
		);
	}

	public function testCreateProfile() {
		$subject = new WikiPage( __METHOD__, NS_MAIN, '', 'foo' );

		$container = new Container(
			new ContainerSemanticData( $subject	)
		);

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getQueryString' )
			->willReturn( 'Foo' );

		$description->expects( $this->once() )
			->method( 'getSize' )
			->willReturn( 2 );

		$description->expects( $this->once() )
			->method( 'getDepth' )
			->willReturn( 42 );

		$instance = new DescriptionProfileAnnotator(
			new NullProfileAnnotator( $container ),
			$description
		);

		$instance->addAnnotation();

		$expected = [
			'propertyCount'  => 3,
			'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE' ],
			'propertyValues' => [ 'Foo', 2, 42 ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);
	}

}
