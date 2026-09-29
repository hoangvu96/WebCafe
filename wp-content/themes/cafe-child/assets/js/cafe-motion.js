// Scroll reveal — IntersectionObserver, GPU-safe (transform + opacity only)
(function () {
	if ( ! ( 'IntersectionObserver' in window ) ) return;
	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) return;

	var selectors = [
		'.woocommerce ul.products li.product',
		'.cafe-cats .wp-block-column',
		'.cafe-commit .wp-block-column',
		'.entry-content h2',
		'.entry-content h3',
		'.cafe-bean-info',
		'.wc-block-grid__product',
	].join( ', ' );

	var targets = document.querySelectorAll( selectors );
	if ( ! targets.length ) return;

	// Tag sibling groups for stagger delay
	var groups = {};
	targets.forEach( function ( el ) {
		var parent = el.parentElement;
		var key = parent ? parent.dataset.revealGroup || ( parent.dataset.revealGroup = Math.random().toString(36).slice(2) ) : 'root';
		groups[ key ] = groups[ key ] || [];
		groups[ key ].push( el );
	} );

	Object.values( groups ).forEach( function ( siblings ) {
		siblings.forEach( function ( el, i ) {
			el.classList.add( 'cafe-reveal' );
			el.style.transitionDelay = ( i * 80 ) + 'ms';
		} );
	} );

	var observer = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'cafe-revealed' );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.08, rootMargin: '0px 0px -32px 0px' }
	);

	targets.forEach( function ( el ) { observer.observe( el ); } );
} )();
