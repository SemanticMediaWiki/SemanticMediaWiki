<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use PHPUnit\Framework\TestCase;
use SMW\DataModel\ContainerSemanticData;
use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotator;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Query\ProfileAnnotators\SchemaLinkProfileAnnotator;
use SMW\Tests\TestEnvironment;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotators\SchemaLinkProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaLinkProfileAnnotatorTest extends TestCase {

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
			SchemaLinkProfileAnnotator::class,
			new SchemaLinkProfileAnnotator( $profileAnnotator, '' )
		);
	}

	public function testAddAnnotationOnInvalidSchemaLinkTypeThrowsException() {
		$profileAnnotator = $this->getMockBuilder( ProfileAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SchemaLinkProfileAnnotator( $profileAnnotator, [] );

		$this->expectException( '\RuntimeException' );
		$instance->addAnnotation();
	}

	/**
	 * @dataProvider SchemaLinkProvider
	 */
	public function testAddAnnotation( $SchemaLink, $expected ) {
		$subject = new DIWikiPage( __METHOD__, NS_MAIN, '', '_QUERYe7d20a88999' );

		$container = new DIContainer(
			new ContainerSemanticData( $subject	)
		);

		$instance = new SchemaLinkProfileAnnotator(
			new NullProfileAnnotator( $container ),
			$SchemaLink
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function SchemaLinkProvider() {
		yield [
			'',
			[
				'propertyCount' => 0
			]
		];

		yield [
			'Foo',
			[
				'propertyCount'  => 1,
				'propertyKeys'   => [ '_SCHEMA_LINK' ],
				'propertyValues' => [ 'smw/schema:Foo' ]
			]
		];
	}

}
