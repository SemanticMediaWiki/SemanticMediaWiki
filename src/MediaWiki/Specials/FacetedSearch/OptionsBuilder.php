<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Html\Html;
use SMW\Localizer\MessageLocalizerTrait;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class OptionsBuilder {

	use MessageLocalizerTrait;

	/**
	 * @since 3.2
	 */
	public function __construct( private Profile $profile ) {
	}

	private function option( $value, $label, bool $selected ): string {
		return Html::element( 'option', [ 'value' => $value, 'selected' => $selected ], (string)$label );
	}

	/**
	 * @since 3.2
	 */
	public function profiles( string $profile = '' ): string {
		$html = [];
		$list = $this->profile->getProfileList();

		if ( $profile == '' ) {
			$profile = $this->profile->getProfileName();
		}

		ksort( $list );

		foreach ( $list as $key => $val ) {

			$message_key = $val;
			$key = str_replace( '_profile', '', $key );

			$val = $this->msg( $val );

			if ( strpos( $val, $message_key ) !== false ) {
				$val = $key;
			}

			$html[] = $this->option( $key, $val, $profile === $key );
		}

		return implode( '', $html );
	}

	/**
	 * @since 3.2
	 */
	public function format( string $format ): string {
		$html = [];
		$list = [
			'table' => $this->msg( "smw-facetedsearch-format-table" )
		];

		if ( $format == '' ) {
			$format = $this->profile->get( 'result.default_format' );
		}

		foreach ( $list as $key => $val ) {
			$html[] = $this->option( $key, $val, $format === $key );
		}

		return implode( '', $html );
	}

	/**
	 * @since 3.2
	 */
	public function size( int $size ): string {
		$html = [];
		$list = $this->profile->get( 'result.paging_limit' );

		if ( $size == 0 ) {
			$size = $this->profile->get( 'result.default_limit' );
		}

		foreach ( $list as $val ) {
			$html[] = $this->option( $val, $val, $size === $val );
		}

		return implode( '', $html );
	}

	/**
	 * @since 3.2
	 */
	public function order( string $order ): string {
		$html = [];

		$list = [
			'asc' => "Title (A-Z)",
			'desc' => "Title (Z-A)",
			'recent' => "Most recent",
		// 'score' => "Relevance"
		];

		foreach ( $list as $key => $val ) {
			$html[] = $this->option( $key, $val, $order === $key );
		}

		return implode( '', $html );
	}

	/**
	 * @since 3.2
	 */
	public function previous( int $size, int $offset ): string {
		if ( $offset < 1 ) {
			return $this->msg( 'smw_result_prev' );
		}

		return Html::rawElement(
			'button',
			[
				'class' => 'button-link',
				'name' => 'offset',
				'value' => max( $offset - $size, 0 ),
				'onchange' => 'this.form.submit()',
				'form' => 'search-input-form'
			],
			$this->msg( 'smw_result_prev' )
		);
	}

	/**
	 * @since 3.2
	 */
	public function next( int $size, int $offset, bool $hasFurtherResults ): string {
		if ( !$hasFurtherResults ) {
			return $this->msg( 'smw_result_next' );
		}

		return Html::rawElement(
			'button',
			[
				'class' => 'button-link',
				'name' => 'offset',
				'value' => $offset + $size,
				'onchange' => 'this.form.submit()',
				'form' => 'search-input-form'
			],
			$this->msg( 'smw_result_next' )
		);
	}

}
