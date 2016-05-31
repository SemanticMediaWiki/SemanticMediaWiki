<?php

namespace SMW\Tests\PropertyAnnotators;

use SMW\PropertyAnnotators\NullPropertyAnnotator;

/**
 * @covers \SMW\PropertyAnnotators\NullPropertyAnnotator
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
			'\SMW\PropertyAnnotators\NullPropertyAnnotator',
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
			'\SMW\PropertyAnnotators\NullPropertyAnnotator',
			$instance->addAnnotation()
		);

	}

}
