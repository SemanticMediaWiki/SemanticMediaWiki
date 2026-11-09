/**
 * Security regression test for the DataTables integration.
 *
 * An author-authored `.smw-datatable` table may carry DataTables cell
 * `data-search`/`data-filter` attributes, which the bundled DataTables reads as a
 * column's orthogonal filter data, decodes and, for a value containing `&`,
 * assigns to a detached element's innerHTML -- executing author markup on init.
 * attach() must strip these before DataTables reads them. `data-order`/`data-sort`
 * feed only sorting and type detection and carry SMW's own sort values, so they
 * must survive.
 *
 * SMW CI does not run QUnit, so this file is verified locally and in review.
 */

const path = require( 'path' );

const dataTable = require( path.resolve( __dirname, '../../res/smw/printer/ext.smw.tableprinter.js' ) );

QUnit.module( 'ext.smw.tableprinter', function () {

	// The bundled DataTables module is unavailable here; the strip runs before
	// mw.loader.using resolves it, so stub the thenable to a no-op `.done`.
	function stubLoader() {
		mw.loader.using = function () {
			return { done: function () {} };
		};
	}

	QUnit.test( 'attach() strips the author DataTables filter attributes before initialisation', function ( assert ) {
		stubLoader();

		document.body.innerHTML =
			'<table class="datatable"><tbody>' +
			'<tr><td data-search="x" data-filter="y">a</td></tr>' +
			'</tbody></table>';

		dataTable.attach( $( '.datatable' ) );

		assert.strictEqual(
			$( '.datatable td' ).attr( 'data-search' ),
			undefined,
			'the author data-search attribute is removed before DataTables reads it'
		);
		assert.strictEqual(
			$( '.datatable td' ).attr( 'data-filter' ),
			undefined,
			'the author data-filter attribute is removed before DataTables reads it'
		);
	} );

	QUnit.test( 'attach() keeps data-order and data-sort so SMW sort values survive', function ( assert ) {
		stubLoader();

		document.body.innerHTML =
			'<table class="datatable"><tbody>' +
			'<tr><td data-order="24613542025926" data-sort="24613542025926">a</td></tr>' +
			'</tbody></table>';

		dataTable.attach( $( '.datatable' ) );

		assert.strictEqual(
			$( '.datatable td' ).attr( 'data-order' ),
			'24613542025926',
			'the SMW data-order sort value survives initialisation'
		);
		assert.strictEqual(
			$( '.datatable td' ).attr( 'data-sort' ),
			'24613542025926',
			'the data-sort attribute survives initialisation'
		);
	} );
} );
