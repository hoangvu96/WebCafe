( function () {
	'use strict';

	var cfg    = window.cafeSearch || {};
	var timer  = null;
	var panel  = null;
	var active = false;

	function init() {
		var toggle = document.querySelector( '.search-toggle-open' );
		if ( ! toggle ) return;

		// Capture phase: fires BEFORE Kadence's own handler
		toggle.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			if ( active ) {
				close();
			} else {
				open( toggle );
			}
		}, true );
	}

	function buildPanel( anchor ) {
		panel = document.createElement( 'div' );
		panel.className = 'cafe-search-panel';
		panel.innerHTML =
			'<p class="cafe-search-title">' + cfg.label + '</p>' +
			'<div class="cafe-search-field-wrap">' +
				'<input type="search" class="cafe-search-input" placeholder="' + cfg.placeholder + '" autocomplete="off" />' +
				'<button type="button" class="cafe-search-submit" aria-label="' + cfg.submit + '">' +
					'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>' +
				'</button>' +
			'</div>' +
			'<div class="cafe-search-results"></div>';

		document.body.appendChild( panel );
		positionPanel( anchor );

		var input = panel.querySelector( '.cafe-search-input' );
		input.addEventListener( 'input', onInput );
		input.focus();

		document.addEventListener( 'click', onOutside );
		document.addEventListener( 'keydown', onKey );
		window.addEventListener( 'resize', function () { positionPanel( anchor ); } );
	}

	function positionPanel( anchor ) {
		if ( ! panel ) return;
		var rect  = anchor.getBoundingClientRect();
		var scrollY = window.scrollY || window.pageYOffset;
		var panelW  = Math.min( 380, window.innerWidth - 32 );
		var left    = rect.right - panelW;
		if ( left < 16 ) left = 16;
		panel.style.top   = ( rect.bottom + scrollY + 8 ) + 'px';
		panel.style.left  = left + 'px';
		panel.style.width = panelW + 'px';
	}

	function open( anchor ) {
		active = true;
		buildPanel( anchor );
		anchor.setAttribute( 'aria-expanded', 'true' );
	}

	function close() {
		active = false;
		if ( panel ) { panel.remove(); panel = null; }
		var toggle = document.querySelector( '.search-toggle-open' );
		if ( toggle ) toggle.setAttribute( 'aria-expanded', 'false' );
		document.removeEventListener( 'click', onOutside );
		document.removeEventListener( 'keydown', onKey );
	}

	function onOutside( e ) {
		if ( panel && !panel.contains( e.target ) ) {
			var toggle = document.querySelector( '.search-toggle-open' );
			if ( toggle && toggle.contains( e.target ) ) return;
			close();
		}
	}

	function onKey( e ) {
		if ( e.key === 'Escape' ) close();
	}

	function onInput( e ) {
		clearTimeout( timer );
		var q = e.target.value.trim();
		if ( q.length < 2 ) {
			var results = panel && panel.querySelector( '.cafe-search-results' );
			if ( results ) results.innerHTML = '';
			return;
		}
		timer = setTimeout( function () { fetchResults( q ); }, 300 );
	}

	function fetchResults( q ) {
		if ( !panel ) return;
		var results = panel.querySelector( '.cafe-search-results' );
		results.innerHTML = '<p class="cafe-search-loading">' + cfg.loading + '</p>';

		fetch( cfg.ajaxUrl + '?action=cafe_live_search&nonce=' + encodeURIComponent( cfg.nonce ) + '&q=' + encodeURIComponent( q ) )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) { if ( data.success ) renderResults( data.data, q ); } )
			.catch( function () {
				if ( panel ) panel.querySelector( '.cafe-search-results' ).innerHTML = '<p class="cafe-search-empty">' + cfg.error + '</p>';
			} );
	}

	function renderResults( data, q ) {
		if ( !panel ) return;
		var results = panel.querySelector( '.cafe-search-results' );
		if ( !data || !data.products.length ) {
			results.innerHTML = '<p class="cafe-search-empty">' + cfg.empty + '</p>';
			return;
		}
		var html = '<ul class="cafe-search-list">';
		data.products.forEach( function ( p ) {
			html += '<li class="cafe-search-item">' +
				'<a href="' + p.url + '" class="cafe-search-link">' +
					'<div class="cafe-search-name">' + p.title + '</div>' +
					'<div class="cafe-search-price">' + p.price + '</div>' +
				'</a>' +
				( p.thumb ? '<a href="' + p.url + '" class="cafe-search-thumb" tabindex="-1" aria-hidden="true"><img src="' + p.thumb + '" alt="" loading="lazy"/></a>' : '' ) +
				'</li>';
		} );
		html += '</ul>';
		if ( data.total > data.products.length ) {
			var label = cfg.more.replace( '%d', data.total );
			html += '<a href="' + data.shop_url + '" class="cafe-search-more">' + label + '</a>';
		}
		results.innerHTML = html;
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
