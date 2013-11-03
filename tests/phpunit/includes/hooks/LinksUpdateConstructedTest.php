<?php

namespace SMW\Test;

use SMW\LinksUpdateConstructed;
use SMW\ExtensionContext;

use ParserOutput;
use LinksUpdate;
use Title;

/**
 * @covers \SMW\LinksUpdateConstructed
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class LinksUpdateConstructedTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\LinksUpdateConstructed';
	}

	/**
	 * @since 1.9
	 *
	 * @return LinksUpdateConstructed
	 */
	private function newInstance( Title $title = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
			$title->resetArticleID( rand( 1, 1000 ) );
		}

		$mockStore = $this->newMockBuilder()->newObject( 'Store' );

		$parserOutput = new ParserOutput();
		$parserOutput->setTitleText( $title->getPrefixedText() );

		$context = new ExtensionContext();
		$context->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', $mockStore );

		$instance = new LinksUpdateConstructed( new LinksUpdate( $title, $parserOutput ) );
		$instance->invokeContext( $context );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testProcess() {

		$instance       = $this->newInstance();
		$updateObserver = new MockUpdateObserver();

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'UpdateObserver', $updateObserver );

		$this->assertTrue(
			$instance->process(),
			'asserts that process() always returns true'
		);

		$this->assertEquals(
			'runStoreUpdater',
			$updateObserver->getNotifier(),
			'asserts that the invoked observer was notified'
		);

	}

}
