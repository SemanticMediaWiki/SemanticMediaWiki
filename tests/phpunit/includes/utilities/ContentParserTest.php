<?php

namespace SMW\Test;

use SMW\ParserOutputGenerator;

use Title;

/**
 * Tests for the ParserOutputGenerator class
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
 * @covers \SMW\ParserOutputGenerator
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ParserOutputGeneratorTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ParserOutputGenerator';
	}

	/**
	 * Helper method that returns a ParserOutputGenerator object
	 *
	 * @since 1.9
	 *
	 * @return ParserOutputGenerator
	 */
	private function getInstance( Title $title = null ) {
		return new ParserOutputGenerator( $title === null ? $this->newTitle() : $title );
	}

	/**
	 * @test ParserOutputGenerator::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test ParserOutputGenerator::generate
	 * @dataProvider titleRevisionDataProvider
	 *
	 * @since 1.9
	 */
	public function testRun( $test, $expected ) {

		$reflector = $this->newReflector();
		$instance  = $this->getInstance( $test['title'] );

		$revision  = $reflector->getProperty( 'revision' );
		$revision->setAccessible( true );
		$revision->setValue( $instance, $test['revision'] );

		$options  = $reflector->getProperty( 'parserOptions' );
		$options->setAccessible( true );
		$options->setValue( $instance, $test['parserOptions'] );

		$instance->generate();

		if ( $expected['error'] ) {
			$this->assertInternalType( 'array', $instance->getErrors() );
		} else {
			$this->assertInstanceOf( 'ParserOutput', $instance->getOutput() );
		}
	}

	/**
	 * Provides title and wikiPage samples
	 *
	 * @return array
	 */
	public function titleRevisionDataProvider() {

		// Mocking this object was not really an option as
		// the Parser is quite complex
		$parserOptions = new \ParserOptions();
		$parserOptions->setTargetLanguage( $this->getLanguage() );

		$provider = array();

		// #0 Title does not exists
		$title = $this->newMockObject( array(
			'getDBkey' => 'Lila',
			'exists'   => false
		) )->getMockTitle();

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => null,
				'parserOptions' => null
			),
			array(
				'error'   => true
			)
		);

		// #1 Valid revision generates a valid ParserOuput object
		$title = $this->newMockObject( array(
			'getDBkey' => 'Lula',
			'exists'   => true
		) )->getMockTitle();

		$revision = $this->newMockObject( array(
			'getId'   => 9001,
			'getUser' => 'Lala',
			'getText' => 'Lala',
		) )->getMockRevision();

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
				'parserOptions' => $parserOptions
			),
			array(
				'error'   => false
			)
		);

		return $provider;
	}

}
