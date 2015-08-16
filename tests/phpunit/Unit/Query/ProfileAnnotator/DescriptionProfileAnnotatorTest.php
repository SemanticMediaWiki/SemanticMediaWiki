<?php

namespace SMW\Tests\Query\ProfileAnnotator;

use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\ProfileAnnotator\DescriptionProfileAnnotator;
use SMW\Query\ProfileAnnotator\NullProfileAnnotator;
use SMW\Subobject;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Query\ProfileAnnotator\DescriptionProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DescriptionProfileTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$profileAnnotator = $this->getMockBuilder( '\SMW\Query\ProfileAnnotator\ProfileAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator\DescriptionProfileAnnotator',
			new DescriptionProfileAnnotator( $profileAnnotator, $description )
		);
	}

	public function testCreateProfile() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getQueryString' )
			->will( $this->returnValue( 'Foo' ) );

		$description->expects( $this->once() )
			->method( 'getSize' )
			->will( $this->returnValue( 2 ) );

		$description->expects( $this->once() )
			->method( 'getDepth' )
			->will( $this->returnValue( 42 ) );

		$profiler = new NullProfileAnnotator(
			new Subobject( DIWikiPage::newFromText( __METHOD__ )->getTitle() ),
			'ichimarukyuu'
		);

		$instance = new DescriptionProfileAnnotator( $profiler, $description );
		$instance->addAnnotation();

		$expected = array(
			'propertyCount'  => 3,
			'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE' ),
			'propertyValues' => array( 'Foo', 2, 42 )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);
	}

}
