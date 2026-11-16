<?php

declare( strict_types = 1 );

namespace SMW\ResultFormats;

/**
 * Register new formats with
 *
 * $registry->newFormat()
 *     ->withName( 'your-format' )
 *     ->andMessageKey( 'your-message-key' )
 *     ->andParameterDefinitions( [] )
 *     ->andPresenterBuilder( function() {
 *         return new YourResultPresenter();
 *     } )
 *     ->register();
 *
 * @since 3.2
 */
class ResultFormatRegistry {

	/**
	 * @var ResultFormat[]
	 */
	private $resultFormats;

	public function newFormat(): ResultFormatRegistrator {
		return new ResultFormatRegistrator( function( ResultFormat $info ) {
			$this->resultFormats[$info->getName()] = $info;
		} );
	}

	/**
	 * @private Package private
	 * TODO: remove from public interface
	 */
	public function getFormatByName( string $name ): ResultFormat {
		return $this->resultFormats[$name];
	}

}
