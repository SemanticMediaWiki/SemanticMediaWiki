/**
 * Rendering behaviour of the vendored jQuery-Autocomplete widget
 * (res/jquery/jquery.autocomplete.js), driven through its public instance so
 * the observer is the suggestions container the user sees.
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	require( '../../res/jquery/jquery.autocomplete.js' );

	/**
	 * Render a set of suggestions for a given query and return the widget
	 * instance, whose suggestionsContainer holds what the user sees.
	 *
	 * @param {Array} suggestions
	 * @param {string} query
	 * @return {Object}
	 */
	function render( suggestions, query ) {
		var $input = $( '<input>' ).appendTo( document.body );

		$input.autocomplete( {
			minChars: 0,
			delimiter: '\n',
			triggerSelectOnValidInput: false
		} );

		var instance = $input.autocomplete();
		instance.suggestions = suggestions;
		instance.currentValue = query;
		instance.suggest();

		return instance;
	}

	function suggestion( value ) {
		return { value: value, data: value };
	}

	QUnit.module( 'jquery.autocomplete' );

	QUnit.test( 'an empty-query suggestion value cannot inject markup into the container', function ( assert ) {
		assert.expect( 2 );
		var instance = render(
			[ suggestion( 'safe value' ), suggestion( '<img src=x onerror="window.__xss=1">' ) ],
			''
		);
		var $container = $( instance.suggestionsContainer );

		assert.strictEqual(
			$container.find( 'img' ).length,
			0,
			'the suggestion value produces no live element in the dropdown'
		);
		assert.true(
			$container.html().indexOf( '&lt;img' ) !== -1,
			'the markup is rendered as escaped text instead'
		);
	} );

	QUnit.test( 'a typed substring is still highlighted for a benign value', function ( assert ) {
		assert.expect( 2 );
		var instance = render(
			[ suggestion( 'alpha' ), suggestion( 'hello world' ), suggestion( 'omega' ) ],
			'hello'
		);
		var $suggestions = $( instance.suggestionsContainer ).find( '.autocomplete-suggestion' );

		assert.strictEqual( $suggestions.length, 3, 'every suggestion is rendered' );
		assert.strictEqual(
			$suggestions.filter( ':contains("hello world")' ).find( 'strong' ).text(),
			'hello',
			'the typed substring is wrapped for highlighting'
		);
	} );

}() );
