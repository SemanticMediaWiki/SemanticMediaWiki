<?php

namespace SMW\Tests\Annotator;

use SMW\Annotator\NullPropertyAnnotator;

/**
 * @covers \SMW\Annotator\NullPropertyAnnotator
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Annotator\NullPropertyAnnotator',
			new NullPropertyAnnotator( $semanticData )
		);
	}

	public function testMethodAccess() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new NullPropertyAnnotator( $semanticData );

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$instance->getSemanticData()
		);

		$this->assertInstanceOf(
			'\SMW\Annotator\NullPropertyAnnotator',
			$instance->addAnnotation()
		);

	}

}
