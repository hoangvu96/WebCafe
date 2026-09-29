( function () {
	'use strict';

	function setOpen( root, open ) {
		var toggle = root.querySelector( '.cafe-lang__toggle' );
		var menu   = root.querySelector( '.cafe-lang__menu' );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		menu.hidden = ! open;
		root.classList.toggle( 'is-open', open );
		if ( open ) {
			var current = menu.querySelector( '[aria-current="true"]' ) || menu.querySelector( 'a' );
			if ( current ) current.focus();
		}
	}

	function closeAll( except ) {
		document.querySelectorAll( '.cafe-lang.is-open' ).forEach( function ( root ) {
			if ( root !== except ) setOpen( root, false );
		} );
	}

	function init() {
		document.querySelectorAll( '.cafe-lang' ).forEach( function ( root ) {
			var toggle = root.querySelector( '.cafe-lang__toggle' );
			toggle.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var open = ! root.classList.contains( 'is-open' );
				closeAll( root );
				setOpen( root, open );
			} );

			root.addEventListener( 'keydown', function ( e ) {
				if ( ! root.classList.contains( 'is-open' ) ) return;
				var links = Array.prototype.slice.call( root.querySelectorAll( '.cafe-lang__option' ) );
				var index = links.indexOf( document.activeElement );
				if ( e.key === 'Escape' ) {
					setOpen( root, false );
					toggle.focus();
				} else if ( e.key === 'ArrowDown' || e.key === 'ArrowUp' ) {
					e.preventDefault();
					var next = ( index + ( e.key === 'ArrowDown' ? 1 : -1 ) + links.length ) % links.length;
					links[ next ].focus();
				}
			} );

			root.addEventListener( 'focusout', function ( e ) {
				if ( e.relatedTarget && ! root.contains( e.relatedTarget ) ) setOpen( root, false );
			} );
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! e.target.closest( '.cafe-lang' ) ) closeAll( null );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
