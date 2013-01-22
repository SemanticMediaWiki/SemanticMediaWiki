<?php

namespace SMW\Test;

/**
 * Tests for the SMWExpElement deriving classes.
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
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWExpElementTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = array();

		$instances[] = new \SMWExpResource( 'foo' );
		$instances[] = new \SMWExpResource( 'foo', null );
		$instances[] = new \SMWExpResource( 'foo', new \SMWDIString( 'bar' ) );

		$instances[] = new \SMWExpNsResource( 'foo', 'bar', 'baz' );
		$instances[] = new \SMWExpNsResource( 'foo', 'bar', 'baz', null );
		$instances[] = new \SMWExpNsResource( 'foo', 'bar', 'baz', new \SMWDIString( 'bar' ) );

		$instances[] = new \SMWExpLiteral( 'foo' );
		$instances[] = new \SMWExpLiteral( 'foo', '' );
		$instances[] = new \SMWExpLiteral( 'foo', 'bar' );
		$instances[] = new \SMWExpLiteral( 'foo', '', null );
		$instances[] = new \SMWExpLiteral( 'foo', '', new \SMWDIString( 'bar' ) );
		$instances[] = new \SMWExpLiteral( 'foo', 'baz', new \SMWDIString( 'bar' ) );

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetDataItem( \SMWExpElement $element ) {
		$this->assertTypeOrValue( 'SMWDataItem', $element->getDataItem(), null );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetLexicalForm( \SMWExpElement $element ) {
		if ( method_exists( $element, 'getLexicalForm' ) ) {
			$this->assertType( 'string', $element->getLexicalForm() );
		}
		else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetDatatype( \SMWExpElement $element ) {
		if ( method_exists( $element, 'getDatatype' ) ) {
			$this->assertType( 'string', $element->getDatatype() );
		}
		else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetLocalName( \SMWExpElement $element ) {
		if ( method_exists( $element, 'getLocalName' ) ) {
			$this->assertType( 'string', $element->getLocalName() );
		}
		else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testgetNamespace( \SMWExpElement $element ) {
		if ( method_exists( $element, 'getNamespace' ) ) {
			$this->assertType( 'string', $element->getNamespace() );
		}
		else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetNamespaceId( \SMWExpElement $element ) {
		if ( method_exists( $element, 'getNamespaceId' ) ) {
			$this->assertType( 'string', $element->getNamespaceId() );
		}
		else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetQName( \SMWExpElement $element ) {
		if ( method_exists( $element, 'getQName' ) ) {
			$this->assertType( 'string', $element->getQName() );
		}
		else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetUri( \SMWExpElement $element ) {
		if ( method_exists( $element, 'getUri' ) ) {
			$this->assertType( 'string', $element->getUri() );
		}
		else {
			$this->assertTrue( true );
		}
	}
	/**
	 * @dataProvider instanceProvider
	 */
	public function testIsBlankNode( \SMWExpElement $element ) {
		if ( method_exists( $element, 'isBlankNode' ) ) {
			$this->assertType( 'boolean', $element->isBlankNode() );
		}
		else {
			$this->assertTrue( true );
		}
	}


}