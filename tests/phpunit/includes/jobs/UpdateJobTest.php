<?php

namespace SMW\Test;

use SMW\UpdateJob;

use Title;
/**
 * Tests for the UpdateJob class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\UpdateJob
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class UpdateJobTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\UpdateJob';
	}

	/**
	 * Helper method that returns a UpdateJob object
	 *
	 * @since 1.9
	 *
	 * @return UpdateJob
	 */
	private function getInstance( Title $title = null ) {
		$instance = new UpdateJob( $title === null ? $this->newTitle() : $title );

		// Set smwgEnableUpdateJobs to false in order to avoid having jobs being
		// inserted as real jobs to the queue
		$instance->setSettings( $this->getSettings( array( 'smwgEnableUpdateJobs' => false ) ) );
		return $instance;
	}

	/**
	 * @test UpdateJob::__construct
	 *
	 * FIXME Delete SMWUpdateJob assertion after all references to
	 * SMWUpdateJob have been removed
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
		$this->assertInstanceOf( 'SMWUpdateJob', $this->getInstance() );
	}

	/**
	 * @test UpdateJob::run
	 * @dataProvider titleWikiPageDataProvider
	 *
	 * @since 1.9
	 */
	public function testRun( $test, $expected ) {

		$instance = $this->getInstance( $test['title'] );
		$instance->setStore( $this->newMockObject()->getMockStore() );

		// Provides access to the wikiPage property and
		// replace it with a controllable mock bject
		$reflector = $this->newReflector();
		$wikiPage  = $reflector->getProperty( 'wikiPage' );
		$wikiPage->setAccessible( true );
		$wikiPage->setValue( $instance, $test['wikiPage'] );

		$this->assertEquals( $expected['result'], $instance->run() );
	}

	/**
	 * Provides title and wikiPage samples
	 *
	 * @return array
	 */
	public function titleWikiPageDataProvider() {

		$provider = array();
		$wikiPage = $this->newMockObject()->getMockWikiPage();

		// #0 Title does not exists, deleteSubject() is being executed
		$title = $this->newMockObject( array(
			'getDBkey' => 'Lila',
			'exists'   => false
		) )->getMockTitle();

		$provider[] = array(
			array(
				'title'     => $title,
				'wikiPage'  => $wikiPage,
			),
			array(
				'result'    => true
			)
		);

		// #1 No revision, no further activities
		$title = $this->newMockObject( array(
			'getDBkey' => 'Lala',
			'exists'   => true
		) )->getMockTitle();

		$provider[] = array(
			array(
				'title'    => $title,
				'wikiPage' => null
			),
			array(
				'result'   => false
			)
		);

		// #2 Valid revision and parserOuput
		$title = $this->newMockObject( array(
			'getDBkey' => 'Lula',
			'exists'   => true
		) )->getMockTitle();

		$revision = $this->newMockObject( array(
			'getId'   => 9001,
			'getUser' => 'Lala'
		) )->getMockRevision();

		$wikiPage = $this->newMockObject( array(
			'getRevision'       => $revision,
			'makeParserOptions' => $this->newMockObject()->getMockParserOptions(),
			'getParserOutput'   => $this->newParserOutput()
		) )->getMockWikiPage();

		$provider[] = array(
			array(
				'title'    => $title,
				'wikiPage' => $wikiPage
			),
			array(
				'result'      => true
			)
		);

		return $provider;
	}

}
