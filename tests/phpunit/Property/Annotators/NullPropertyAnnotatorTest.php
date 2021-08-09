<?php

namespace SMW\Tests\Property\Annotators;

use SMW\Property\Annotators\NullPropertyAnnotator;

/**
 * @covers \SMW\Property\Annotators\NullPropertyAnnotator
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
			'\SMW\Property\Annotators\NullPropertyAnnotator',
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
			'\SMW\Property\Annotators\NullPropertyAnnotator',
			$instance->addAnnotation()
		);

	}

}
