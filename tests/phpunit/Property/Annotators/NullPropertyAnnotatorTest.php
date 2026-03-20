<?php

namespace SMW\Tests\Property\Annotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\SemanticData;

/**
 * @covers \SMW\Property\Annotators\NullPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class NullPropertyAnnotatorTest extends TestCase {

	public function testCanConstruct() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$this->assertInstanceOf(
			NullPropertyAnnotator::class,
			new NullPropertyAnnotator( $semanticData )
		);
	}

	public function testMethodAccess() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$instance = new NullPropertyAnnotator( $semanticData );

		$this->assertInstanceOf(
			SemanticData::class,
			$instance->getSemanticData()
		);

		$this->assertInstanceOf(
			NullPropertyAnnotator::class,
			$instance->addAnnotation()
		);
	}

}
