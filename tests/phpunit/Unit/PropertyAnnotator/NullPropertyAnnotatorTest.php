<?php

namespace SMW\Tests\PropertyAnnotator;

use SMW\PropertyAnnotator\NullPropertyAnnotator;

/**
 * @covers \SMW\PropertyAnnotator\NullPropertyAnnotator
 * @group semantic-mediawiki
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
			'\SMW\PropertyAnnotator\NullPropertyAnnotator',
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
			'\SMW\PropertyAnnotator\NullPropertyAnnotator',
			$instance->addAnnotation()
		);

	}

}
