<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotators\NullProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullProfileAnnotatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$container = $this->getMockBuilder( '\SMWDIContainer' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotators\NullProfileAnnotator',
			new NullProfileAnnotator( $container )
		);
	}

	public function testMethodAccess() {

		$subject =new DIWikiPage( __METHOD__, NS_MAIN, '', '_QUERYadcb944aa33b2c972470b73964c547c0' );

		$container = new DIContainer(
			new ContainerSemanticData( $subject	)
		);

		$instance = new NullProfileAnnotator(
			$container
		);

		$instance->addAnnotation();

		$this->assertInstanceOf(
			'\SMW\DIProperty',
			$instance->getProperty()
		);

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->getContainer()
		);

		$this->assertInstanceOf(
			'\SMWContainerSemanticData',
			$instance->getSemanticData()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

}
