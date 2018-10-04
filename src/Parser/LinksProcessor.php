<?php

namespace SMW\Parser;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LinksProcessor {

	/**
	 * Internal state for switching SMW link annotations off/on during parsing
	 * ([[SMW::on]] and [[SMW:off]])
	 *
	 * @var boolean
	 */
	private $isAnnotation = true;

	/**
	 * @var boolean
	 */
	private $isStrictMode = true;

	/**
	 * Whether a strict interpretation (e.g [[property::value:partOfTheValue::alsoPartOfTheValue]])
	 * or a more loose interpretation (e.g. [[property1::property2::value]]) for
	 * annotations is expected.
	 *
	 * @since 2.3
	 *
	 * @param boolean $isStrictMode
	 */
	public function isStrictMode( $isStrictMode ) {
		$this->isStrictMode = (bool)$isStrictMode;
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isAnnotation() {
		return $this->isAnnotation;
	}

	/**
	 * $smwgLinksInValues (default = false) determines which regexp pattern
	 * is returned, either a more complex (lib PCRE may cause segfaults if text
	 * is long) or a simpler (no segfaults found for those, but no links
	 * in values) pattern.
	 *
	 * If enabled (SMW accepts inputs like [[property::Some [[link]] in value]]),
	 * this may lead to PHP crashes (!) when very long texts are
	 * used as values. This is due to limitations in the library PCRE that
	 * PHP uses for pattern matching.
	 *
	 * @since 1.9
	 *
	 * @param boolean $linksInValues
	 *
	 * @return string
	 */
	public static function getRegexpPattern( $linksInValues = false ) {

		if ( $linksInValues ) {
			return '/\[\[             # Beginning of the link
				(?:([^:][^]]*):[=:])+ # Property name (or a list of those)
				(                     # After that:
				  (?:[^|\[\]]         #   either normal text (without |, [ or ])
				  |\[\[[^]]*\]\]      #   or a [[link]]
				  |\[[^]]*\]          #   or an [external link]
				)*)                   # all this zero or more times
				(?:\|([^]]*))?        # Display text (like "text" in [[link|text]]), optional
				\]\]                  # End of link
				/xu';
		}

		return '/\[\[             # Beginning of the link
			(?:([^:][^]]*):[=:])+ # Property name (or a list of those)
			([^\[\]]*)            # content: anything but [, |, ]
			\]\]                  # End of link
			/xu';
	}

	/**
	 * A method that precedes the process method, it takes care of separating
	 * value and caption (instead of leaving this to a more complex regexp).
	 *
	 * @since 1.9
	 *
	 * @param array $semanticLink expects (linktext, properties, value|caption)
	 *
	 * @return string
	 */
	public function preprocess( array $semanticLink ) {

		$value = '';
		$caption = false;

		if ( array_key_exists( 2, $semanticLink ) ) {

			// #1747 avoid a mismatch on an annotation like [[Foo|Bar::Foobar]]
			// where the left part of :: is split and would contain "Foo|Bar"
			// hence this type is categorized as no value annotation
			if ( strpos( $semanticLink[1], '|' ) !== false ) {
				return $semanticLink[0];
			}

			$parts = explode( '|', $semanticLink[2] );

			if ( array_key_exists( 0, $parts ) ) {
				$value = $parts[0];
			}
			if ( array_key_exists( 1, $parts ) ) {
				$caption = $parts[1];
			}
		}

		if ( $caption !== false ) {
			return [ $semanticLink[0], $semanticLink[1], $value, $caption ];
		}

		return [ $semanticLink[0], $semanticLink[1], $value ];
	}

	/**
	 * Function strips out the semantic attributes from a wiki link.
	 *
	 * @since 1.9
	 *
	 * @param array $semanticLink expects (linktext, properties, value|caption)
	 *
	 * @return string
	 */
	public function process( array $semanticLink ) {

		$valueCaption = false;
		$property = '';
		$value = '';

		if ( array_key_exists( 1, $semanticLink ) ) {

			// Use case [[Foo::=Bar]] (:= being the legacy notation < 1.4) where
			// the regex splits it into `Foo:` and `Bar` loosing `=` from the value.
			// Restore the link to its previous form of `Foo::=Bar` and reapply
			// a simple split.
			if( strpos( $semanticLink[0], '::=' ) && substr( $semanticLink[1], -1 ) == ':' ) {
				list( $semanticLink[1], $semanticLink[2] ) = explode( '::', $semanticLink[1] . ':=' . $semanticLink[2], 2 );
			}

			// #1252 Strict mode being disabled for support of multi property
			// assignments (e.g. [[property1::property2::value]])

			// #1066 Strict mode is to check for colon(s) produced by something
			// like [[Foo::Bar::Foobar]], [[Foo:::0049 30 12345678]]
			// In case a colon appears (in what is expected to be a string without a colon)
			// then concatenate the string again and split for the first :: occurrence
			// only
			if ( $this->isStrictMode && strpos( $semanticLink[1], ':' ) !== false && isset( $semanticLink[2] ) ) {
				list( $semanticLink[1], $semanticLink[2] ) = explode( '::', $semanticLink[1] . '::' . $semanticLink[2], 2 );
			}

			$property = $semanticLink[1];
		}

		if ( array_key_exists( 2, $semanticLink ) ) {
			$value = $semanticLink[2];
		}

		$value = LinksEncoder::removeLinkObfuscation( $value );

		if ( $value === '' ) { // silently ignore empty values
			return '';
		}

		if ( $property == 'SMW' ) {
			return $this->setAnnotation( $value );
		}

		if ( array_key_exists( 3, $semanticLink ) ) {
			$valueCaption = $semanticLink[3];
		}

		// Extract annotations and create tooltip.
		$properties = preg_split( '/:[=:]/u', $property );

		return [ $properties, $value, $valueCaption ];
	}

	private function setAnnotation( $value ) {

		switch ( $value ) {
			case 'on':
				$this->isAnnotation = true;
				break;
			case 'off':
				$this->isAnnotation = false;
				break;
		}

		return '';
	}

}
