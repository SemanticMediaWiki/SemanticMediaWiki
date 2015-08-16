<?php

namespace SMW\Tests\Query\ProfileAnnotator;

use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\ProfileAnnotator\FormatProfileAnnotator;
use SMW\Query\ProfileAnnotator\NullProfileAnnotator;
use SMW\Subobject;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Query\ProfileAnnotator\FormatProfileAnnotator
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

		$profileAnnotator = $this->getMockBuilder( '\SMW\Query\ProfileAnnotator\ProfileAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator\FormatProfileAnnotator',
			new FormatProfileAnnotator( $profileAnnotator, 'table' )
		);
	}

	public function testCreateProfile() {

		$profiler = new NullProfileAnnotator(
			new Subobject( DIWikiPage::newFromText( __METHOD__ )->getTitle() ),
			'foo'
		);

		$instance = new FormatProfileAnnotator( $profiler, 'table' );
		$instance->addAnnotation();

		$expected = array(
			'propertyCount'  => 1,
			'propertyKeys'   => array( '_ASKFO' ),
			'propertyValues' => array( 'table' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);
	}

}
