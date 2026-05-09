<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

/**
 * @license GPL-2.0-or-later
 * @since   7.0
 */
class Node {

	public array $children = [];

	public function __construct(
		public string $id,
		public string|array $content = ''
	) {
	}

	public function hasNode( string $id ): bool {
		if ( isset( $this->children[$id] ) ) {
			return true;
		}

		foreach ( $this->children as $child ) {
			if ( $child->hasNode( $id ) ) {
				return true;
			}
		}

		return false;
	}

	public function getNode( string $id ): ?self {
		if ( isset( $this->children[$id] ) ) {
			return $this->children[$id];
		}

		foreach ( $this->children as $child ) {
			if ( $child->hasNode( $id ) ) {
				return $child->getNode( $id );
			}
		}

		return null;
	}

	public function addChild( self $node ): void {
		$this->children[$node->id] = $node;
	}

	public function getString(): string {
		$text = is_array( $this->content )
			? implode( '', $this->content )
			: $this->content;

		if ( $text === '' ) {
			$text = '<li><div class="blank-item">' . $this->id . '</div>';
		}

		// Remove the last </li> from the current <li> element to ensure
		// the <ul> becomes part of the <li> element otherwise the elements
		// aren't correct positioned as per HTML standard.
		if ( $this->children !== [] && substr( $text, -5 ) === '</li>' ) {
			$text = substr_replace( $text, "", -5 );
		}

		if ( $this->children !== [] ) {
			$text .= '<ul class="child">';
		}

		foreach ( $this->children as $child ) {
			$text .= $child->getString();
		}

		if ( $this->children !== [] ) {
			$text .= '</ul></li>';
		}

		return $text;
	}
}
