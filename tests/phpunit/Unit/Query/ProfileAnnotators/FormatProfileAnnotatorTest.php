<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotators\FormatProfileAnnotator;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Tests\Utils\UtilityFactory;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotators\FormatProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FormatProfileAnnotatorTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\Query\ProfileAnnotators\FormatProfileAnnotator',
			new FormatProfileAnnotator( $profileAnnotator, 'table' )
		);
	}

	public function testCreateProfile() {

		$subject =new DIWikiPage( __METHOD__, NS_MAIN, '', 'foo' );

		$container = new DIContainer(
			new ContainerSemanticData( $subject	)
		);

		$instance = new FormatProfileAnnotator(
			new NullProfileAnnotator( $container ),
			'table'
		);

		$instance->addAnnotation();

		$expected = [
			'propertyCount'  => 1,
			'propertyKeys'   => [ '_ASKFO' ],
			'propertyValues' => [ 'table' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);
	}

}
