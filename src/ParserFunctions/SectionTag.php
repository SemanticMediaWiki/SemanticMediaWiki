<?php

namespace SMW\ParserFunctions;

use Parser;
use PPFrame;
use Html;

/**
 * To support the generation of <section> ... </section>
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SectionTag {

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var PPFrame
	 */
	private $frame;

	/**
	 * @since 3.0
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 */
	public function __construct( Parser $parser, PPFrame $frame ) {
		$this->parser = $parser;
		$this->frame = $frame;
	}

	/**
	 * @since 3.0
	 *
	 * @param Parser $parser
	 * @param boolean $supportSectionTag
	 *
	 * @return boolean
	 */
	public static function register( Parser $parser, $supportSectionTag = true ) {

		if ( $supportSectionTag === false ) {
			return false;
		}

		$parser->setHook( 'section', function( $input, array $args, Parser $parser, PPFrame $frame ) {
			return ( new self( $parser, $frame ) )->parse( $input, $args );
		} );

		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $input
	 * @param array $args
	 *
	 * @return string
	 */
	public function parse( $input, array $args ) {

		$attributes = [];
		$title = $this->parser->getTitle();

		foreach( $args as $name => $value ) {
			$value = htmlspecialchars( $value );

			if ( $name === 'class' ) {
				$attributes['class'] = $value;
			}

			if ( $name === 'id' ) {
				$attributes['id'] = $value;
			}
		}

		if ( $title !== null && $title->getNamespace() === SMW_NS_PROPERTY ) {
			$attributes['class'] = ( isset( $attributes['class'] ) ? ' ' : '' ) . "smw-property-specification";
		}

		return Html::rawElement(
			'section',
			$attributes,
			$this->parser->recursiveTagParse( $input, $this->frame )
		);
	}

}
