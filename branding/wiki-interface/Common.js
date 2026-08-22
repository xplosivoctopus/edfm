/* EDFM: two small, purely presentational enhancements. Neither is required
 * for the site to function -- both degrade gracefully with JS off -- and
 * neither is achievable in pure CSS, which is why they exist here instead
 * of in MediaWiki:Common.css. Kept deliberately tiny: no framework, no
 * build step, vanilla DOM APIs only.
 */
( function () {
	'use strict';

	/* 1. Eyebrow placement (see Template:Article header, .edfm-eyebrow in
	 * Common.css). MediaWiki always renders the real #firstHeading (the
	 * page's H1) before the article body in the DOM -- there is no
	 * template or CSS hook that lets wikitext content appear earlier than
	 * that. The eyebrow is authored as ordinary wikitext at the top of the
	 * article; this just moves that already-rendered, already-accessible
	 * element to sit visually above the title instead of below it. If this
	 * script doesn't run for any reason, the eyebrow still renders --
	 * directly under the title rather than above it -- so nothing breaks,
	 * this only affects placement.
	 */
	function placeEyebrow() {
		var eyebrow = document.querySelector( '.edfm-eyebrow' );
		var heading = document.getElementById( 'firstHeading' );
		if ( eyebrow && heading && heading.parentNode ) {
			heading.parentNode.insertBefore( eyebrow, heading );
		}
	}

	/* 2. "You are here" sidebar highlighting. MediaWiki's default sidebar
	 * has no built-in current-page indication -- this compares each
	 * sidebar link's URL to the current page and flags the match so
	 * Common.css can style it (see .edfm-nav-current). Wrapped in try/catch
	 * per-link only so one malformed href can't stop the rest from being
	 * checked.
	 */
	function highlightCurrentNavLink() {
		var links = document.querySelectorAll( '#mw-panel .vector-menu-content-list a[href]' );
		/* Compare pathname *and* query string, not just pathname. Most
		 * content pages use clean /wiki/Page_Name URLs where pathname alone
		 * is unambiguous, but action views (edit, history, diff, page
		 * info, ...) all route through the same /index.php pathname and
		 * are only distinguished by their query string (?action=edit vs
		 * ?action=info etc.) -- comparing pathname only would wrongly
		 * highlight every sidebar link that happens to also point at
		 * /index.php, e.g. "Page information" while actually viewing the
		 * edit-source screen.
		 */
		var here = window.location.pathname + window.location.search;
		for ( var i = 0; i < links.length; i++ ) {
			var a = links[ i ];
			try {
				var target = new URL( a.href, window.location.href );
				if ( target.pathname + target.search === here ) {
					a.parentNode.classList.add( 'edfm-nav-current' );
				}
			} catch ( e ) {
				/* malformed href on this one link -- skip it, not fatal */
			}
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			placeEyebrow();
			highlightCurrentNavLink();
		} );
	} else {
		placeEyebrow();
		highlightCurrentNavLink();
	}
}() );
