<?php

namespace SMW\Test;

use SMW\ExtensionContext;
use SMW\UpdateJob;

use Title;

/**
 * @covers \SMW\UpdateJob
 * @covers \SMW\JobBase
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
class UpdateJobTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\UpdateJob';
	}

	/**
	 * @since 1.9
	 *
	 * @return UpdateJob
	 */
	private function newInstance( Title $title = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		$settings = $this->newSettings( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false // false in order to avoid having jobs being inserted
		) );

		$mockStore = $this->newMockBuilder()->newObject( 'Store' );

		$context   = new ExtensionContext();

		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $mockStore );
		$container->registerObject( 'Settings', $settings );

		$instance = new UpdateJob( $title );
		$instance->invokeContext( $context );

		return $instance;
	}

	/**
	 * FIXME Delete SMWUpdateJob assertion after all references to
	 * SMWUpdateJob have been removed
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
		$this->assertInstanceOf( $this->getClass(), new \SMWUpdateJob( $this->newTitle() ) );
	}

	/**
	 * @since 1.9
	 */
	public function testDefaultContext() {
		$instance = new UpdateJob( $this->newTitle() );
		$this->assertInstanceOf( '\SMW\ContextResource', $instance->withContext() );
	}

	/**
	 * @since 1.9
	 */
	public function testRun() {

		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'exists' => true
		) );

		$this->assertFalse(
			$this->newInstance( $title )->run(),
			'Asserts that the run() returns false due to a missing ParserOutput object'
		);

	}

	/**
	 * @dataProvider titleWikiPageDataProvider
	 *
	 * @since 1.9
	 */
	public function testRunOnMockObjects( $setup, $expected ) {

		$instance  = $this->newInstance( $setup['title'] );

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'ContentParser', $setup['contentParser'] );

		$this->assertEquals(
			$expected['result'],
			$instance->run(),
			'Asserts run() in terms of the available ContentParser object'
		);
	}

	/**
	 * @return array
	 */
	public function titleWikiPageDataProvider() {

		$provider = array();

		// #0 Title does not exists, deleteSubject() is being executed
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey' => 'Lila',
			'exists'   => false
		) );

		$provider[] = array(
			array(
				'title'         => $title,
				'contentParser' => null
			),
			array(
				'result'        => true
			)
		);

		// #1 No revision, no further activities
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey' => 'Lala',
			'exists'   => true
		) );

		$contentParser = $this->newMockBuilder()->newObject( 'ContentParser', array(
			'getOutput' => null
		) );

		$provider[] = array(
			array(
				'title'         => $title,
				'contentParser' => $contentParser
			),
			array(
				'result'        => false
			)
		);

		// #2 Valid revision and parserOuput
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey' => 'Lula',
			'exists'   => true
		) );

		$contentParser = $this->newMockBuilder()->newObject( 'ContentParser', array(
			'getOutput' => $this->newMockBuilder()->newObject( 'ParserOutput' )
		) );

		$provider[] = array(
			array(
				'title'         => $title,
				'contentParser' => $contentParser
			),
			array(
				'result'        => true
			)
		);

		return $provider;
	}

}
