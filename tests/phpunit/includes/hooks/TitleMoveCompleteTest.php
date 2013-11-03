<?php

namespace SMW\Test;

use SMW\TitleMoveComplete;
use SMW\ExtensionContext;

use WikiPage;

/**
 * @covers \SMW\TitleMoveComplete
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
class TitleMoveCompleteTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\TitleMoveComplete';
	}

	/**
	 * @since 1.9
	 *
	 * @return TitleMoveComplete
	 */
	private function newInstance( $oldTitle = null, $newTitle = null, $user = null, $oldId = 0, $newId = 0, $settings = array() ) {

		if ( $oldTitle === null ) {
			$oldTitle = $this->newMockBuilder()->newObject( 'Title' );
		}

		if ( $newTitle === null ) {
			$newTitle = $this->newMockBuilder()->newObject( 'Title' );
		}

		if ( $user === null ) {
			$user = $this->getUser();
		}

		$context = new ExtensionContext();
		$context->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Settings', $this->newSettings( $settings ) );

		$instance = new TitleMoveComplete( $oldTitle, $newTitle, $user, $oldId, $newId );
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
	public function testProcessOnMock() {

		$settings = array(
			'smwgCacheType'             => 'hash',
			'smwgAutoRefreshOnPageMove' => true,
		);

		$instance = $this->newInstance( null, null, null, 0 , 0, $settings );

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', $this->newMockBuilder()->newObject( 'Store' ) );

		$result = $instance->process();

		// Post-process check
		$this->assertTrue(
			$result,
			'Asserts that process() always returns true'
		);

	}

}
