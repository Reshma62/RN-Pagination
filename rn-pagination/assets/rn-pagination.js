/**
 * RN Pagination
 *
 * One pager, three back-ends:
 *  - main       server-rendered links (optionally swapped in via AJAX)
 *  - jet        JetEngine Listing Grid → JetEngine's own signed "load more" request, replacing items
 *  - elementor  Elementor Posts / Loop Grid → fetch ?e-page-{id}=N and swap that widget
 * Re-reads the grid after JetSmartFilters / any re-render, so page counts stay correct.
 */
( function ( $ ) {
	'use strict';

	var ARROWS = { prev: 'M15 18l-6-6 6-6', next: 'M9 18l6-6-6-6' };

	/* ------------------------------------------------------------------ */
	/* Markup (twin of RNP_Widget::render_items)                           */
	/* ------------------------------------------------------------------ */

	function pageWindow( cur, total, end, mid ) {
		var out = [], dots = false;
		for ( var n = 1; n <= total; n++ ) {
			if ( n === cur || n <= end || n > total - end || ( n >= cur - mid && n <= cur + mid ) ) {
				out.push( n );
				dots = true;
			} else if ( dots ) {
				out.push( 'dots' );
				dots = false;
			}
		}
		return out;
	}

	function esc( s ) {
		return String( s ).replace( /[&<>"']/g, function ( ch ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ ch ];
		} );
	}

	function build( c, cur, total, href ) {
		if ( total <= 1 && c.hideSingle ) {
			return '';
		}

		function arrow( dir ) {
			return c.arrows ? '<svg class="rnp__arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="' + ARROWS[ dir ] + '" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>' : '';
		}

		function pn( dir, page, enabled ) {
			if ( ! enabled && 'hide' === c.disabled ) {
				return '';
			}
			var label = '<span>' + esc( 'prev' === dir ? c.prev : c.next ) + '</span>';
			var inner = 'prev' === dir ? arrow( 'prev' ) + label : label + arrow( 'next' );
			var cls = 'rnp__item rnp__pn rnp__' + dir;
			if ( ! enabled ) {
				return '<li><span class="' + cls + ' is-disabled" aria-disabled="true">' + inner + '</span></li>';
			}
			return '<li><a class="' + cls + '" href="' + esc( href( page ) ) + '" data-page="' + page + '" rel="' + dir + '">' + inner + '</a></li>';
		}

		var html = '<ul class="rnp__list">';
		if ( 'numbers' !== c.type ) {
			html += pn( 'prev', cur - 1, cur > 1 );
		}
		if ( 'prev_next' !== c.type ) {
			pageWindow( cur, total, c.endSize, c.midSize ).forEach( function ( n ) {
				if ( 'dots' === n ) {
					html += '<li><span class="rnp__item rnp__dots">&hellip;</span></li>';
				} else if ( n === cur ) {
					html += '<li><span class="rnp__item rnp__num is-current" aria-current="page">' + n + '</span></li>';
				} else {
					html += '<li><a class="rnp__item rnp__num" href="' + esc( href( n ) ) + '" data-page="' + n + '">' + n + '</a></li>';
				}
			} );
		}
		if ( 'numbers' !== c.type ) {
			html += pn( 'next', cur + 1, cur < total );
		}
		html += '</ul>';

		if ( c.info ) {
			html += '<div class="rnp__info">' + esc( c.info.replace( '{current}', cur ).replace( '{total}', total ) ) + '</div>';
		}
		return html;
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	function byId( id ) {
		return id ? document.getElementById( id ) : null;
	}

	function urlWith( key, page ) {
		var u = new URL( window.location.href );
		if ( page > 1 ) {
			u.searchParams.set( key, page );
		} else {
			u.searchParams.delete( key );
		}
		return u.toString();
	}

	function scrollToEl( el, offset ) {
		if ( ! el ) {
			return;
		}
		var top = el.getBoundingClientRect().top + window.pageYOffset - ( offset || 0 );
		if ( Math.abs( top - window.pageYOffset ) > 40 ) {
			window.scrollTo( { top: Math.max( 0, top ), behavior: 'smooth' } );
		}
	}

	function fetchDoc( url ) {
		return fetch( url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'rn-pagination' } } )
			.then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( 'HTTP ' + r.status );
				}
				return r.text();
			} )
			.then( function ( html ) {
				return new DOMParser().parseFromString( html, 'text/html' );
			} );
	}

	function reinitElementor( el ) {
		var ef = window.elementorFrontend;
		if ( ! ef || ! ef.elementsHandler ) {
			return;
		}
		$( el ).find( '.elementor-element' ).addBack( '.elementor-element' ).each( function () {
			ef.elementsHandler.runReadyTrigger( $( this ) );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Adapters                                                            */
	/* ------------------------------------------------------------------ */

	function jetAdapter( target ) {
		var root = byId( target );
		if ( root && ! root.querySelector( '.jet-listing-grid__items' ) ) {
			return null;
		}
		if ( ! root ) {
			if ( target ) {
				return null;
			}
			var first = document.querySelector( '.jet-listing-grid__items' );
			root = first && ( first.closest( '.elementor-widget' ) || first.closest( '.jet-listing-grid' ) );
		}
		if ( ! root ) {
			return null;
		}

		function items() {
			return root.querySelector( '.jet-listing-grid__items' );
		}

		return {
			name: 'jet',
			root: root,
			param: 'rnp_' + ( target || 'grid' ),
			state: function () {
				var el = items();
				if ( ! el ) {
					return null;
				}
				return {
					page: parseInt( el.getAttribute( 'data-page' ), 10 ) || 1,
					pages: parseInt( el.getAttribute( 'data-pages' ), 10 ) || 1
				};
			},
			go: function ( n ) {
				return new Promise( function ( resolve, reject ) {
					var el = items();
					var $items = $( el );
					var nav = $items.data( 'nav' );
					if ( 'string' === typeof nav ) {
						try {
							nav = JSON.parse( nav );
						} catch ( e ) {
							nav = null;
						}
					}
					if ( ! window.JetEngine || ! window.JetEngine.ajaxGetListing || ! nav || ! nav.query || $.isEmptyObject( nav.query ) ) {
						reject( new Error( 'RN Pagination: this Listing Grid has no query data.' ) );
						return;
					}
					var widget = el.closest( '.elementor-widget' );
					window.JetEngine.ajaxGetListing( {
						handler: 'listing_load_more',
						container: $items,
						masonry: $items.hasClass( 'jet-listing-grid__masonry' ) ? $items : false,
						slider: false,
						append: false,
						query: nav.query,
						widgetSettings: nav.widget_settings,
						page: n,
						elementID: widget ? widget.getAttribute( 'data-id' ) : false,
						queriedID: $items.data( 'queried-id' ) || false
					}, function () {
						el.setAttribute( 'data-page', n );
						$( document ).trigger( 'rn-pagination/changed', [ n, el ] );
						resolve();
					}, function () {
						reject( new Error( 'RN Pagination: request failed.' ) );
					} );
				} );
			},
			// Server already renders page 1; deep-linked pages are loaded after boot.
			urlPageOnBoot: true
		};
	}

	function elementorAdapter( target ) {
		var root = byId( target );
		if ( ! root ) {
			if ( target ) {
				return null;
			}
			var meta0 = document.querySelector( '.rnp-meta' );
			root = meta0 && meta0.closest( '.elementor-widget' );
		}
		if ( ! root || ! root.querySelector( '.rnp-meta' ) ) {
			return null;
		}
		var widgetId = root.getAttribute( 'data-id' );
		root.classList.add( 'rnp-native-hidden' );

		function meta() {
			var all = root.querySelectorAll( '.rnp-meta' );
			return all[ all.length - 1 ];
		}

		return {
			name: 'elementor',
			root: root,
			param: 'e-page-' + widgetId, // Elementor reads this itself → refresh shows the same page.
			native: meta().getAttribute( 'data-native' ),
			state: function () {
				var m = meta();
				return m ? {
					page: parseInt( m.getAttribute( 'data-page' ), 10 ) || 1,
					pages: parseInt( m.getAttribute( 'data-pages' ), 10 ) || 1
				} : null;
			},
			go: function ( n ) {
				root.classList.add( 'rnp-loading' );
				return fetchDoc( urlWith( 'e-page-' + widgetId, n ) ).then( function ( doc ) {
					var fresh = doc.querySelector( '.elementor-element-' + widgetId );
					if ( ! fresh ) {
						throw new Error( 'RN Pagination: grid not found in the next page.' );
					}
					root.innerHTML = fresh.innerHTML;
					root.classList.remove( 'rnp-loading' );
					reinitElementor( root );
					$( document ).trigger( 'rn-pagination/changed', [ n, root ] );
				} ).catch( function ( e ) {
					root.classList.remove( 'rnp-loading' );
					throw e;
				} );
			},
			urlPageOnBoot: false
		};
	}

	/* ------------------------------------------------------------------ */
	/* Pager instance                                                      */
	/* ------------------------------------------------------------------ */

	function Pager( nav ) {
		this.nav = nav;
		try {
			this.c = JSON.parse( nav.getAttribute( 'data-rnp' ) || '{}' );
		} catch ( e ) {
			this.c = {};
		}
		this.busy = false;
		nav._rnp = this;

		if ( 'main' === this.c.mode ) {
			if ( this.c.ajax ) {
				this.bindMainAjax();
			}
			return;
		}
		this.attach( 0 );
	}

	Pager.prototype.attach = function ( attempt ) {
		var self = this, c = this.c, a = null;

		if ( 'jet' === c.mode || 'auto' === c.mode ) {
			a = jetAdapter( c.target );
		}
		if ( ! a && ( 'elementor' === c.mode || 'auto' === c.mode ) ) {
			a = elementorAdapter( c.target );
		}

		if ( ! a ) {
			// Grid may render late (lazy load / JetSmartFilters) — retry for a few seconds.
			if ( attempt < 12 ) {
				setTimeout( function () {
					self.attach( attempt + 1 );
				}, 400 );
			} else if ( ! c.edit ) {
				this.nav.innerHTML = '';
			}
			return;
		}

		this.a = a;

		if ( 'elementor' === a.name && 'off' === a.native ) {
			this.nav.innerHTML = c.edit ? '<div class="rnp__notice">RN Pagination: set <b>Pagination → Numbers</b> in the Posts / Loop Grid widget.</div>' : '';
			return;
		}

		this.render();

		this.nav.addEventListener( 'click', function ( e ) {
			var link = e.target.closest( 'a[data-page]' );
			if ( ! link ) {
				return;
			}
			e.preventDefault();
			self.go( parseInt( link.getAttribute( 'data-page' ), 10 ), true );
		} );

		// Re-sync when the grid is re-rendered by someone else (filters, sorting…).
		var timer;
		new MutationObserver( function () {
			if ( self.busy ) {
				return;
			}
			clearTimeout( timer );
			timer = setTimeout( function () {
				self.render();
			}, 60 );
		} ).observe( a.root, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'data-page', 'data-pages' ] } );

		if ( c.urlSync ) {
			window.addEventListener( 'popstate', function () {
				var p = parseInt( new URL( window.location.href ).searchParams.get( a.param ), 10 ) || 1;
				var st = a.state();
				if ( st && p !== st.page ) {
					self.go( p, false, true );
				}
			} );

			if ( a.urlPageOnBoot ) {
				var want = parseInt( new URL( window.location.href ).searchParams.get( a.param ), 10 ) || 1;
				var st = a.state();
				if ( st && want > 1 && want !== st.page && want <= st.pages ) {
					self.go( want, false, true );
				}
			}
		}
	};

	Pager.prototype.render = function () {
		var a = this.a, c = this.c;
		var st = a.state();
		if ( ! st ) {
			return;
		}
		var href = function ( n ) {
			return urlWith( a.param, n );
		};
		this.nav.innerHTML = build( c, Math.min( st.page, st.pages ), st.pages, href );
		this.nav.classList.toggle( 'is-empty', ! this.nav.innerHTML );
	};

	Pager.prototype.go = function ( n, scroll, fromHistory ) {
		var self = this, a = this.a, c = this.c;
		var st = a.state();
		if ( this.busy || ! st || n < 1 || n > st.pages || n === st.page ) {
			return;
		}
		this.busy = true;
		this.nav.classList.add( 'is-loading' );

		a.go( n ).then( function () {
			if ( c.urlSync && ! fromHistory ) {
				window.history.pushState( { rnp: n }, '', urlWith( a.param, n ) );
			} else if ( c.urlSync && fromHistory && ! window.history.state ) {
				window.history.replaceState( { rnp: n }, '', urlWith( a.param, n ) );
			}
			if ( scroll && c.scroll ) {
				scrollToEl( a.root, c.scrollOffset );
			}
		} ).catch( function ( e ) {
			window.console && console.warn( e ); // eslint-disable-line no-console
		} ).then( function () {
			self.busy = false;
			self.nav.classList.remove( 'is-loading' );
			self.render();
		} );
	};

	/* Main query + "load without reload": fetch the link, swap content + pager. */
	Pager.prototype.bindMainAjax = function () {
		var self = this, c = this.c;
		var widget = this.nav.closest( '.elementor-element' );
		var widgetId = widget && widget.getAttribute( 'data-id' );

		function load( url, push ) {
			var content = document.querySelector( c.content );
			if ( ! content || self.busy ) {
				window.location.href = url;
				return;
			}
			self.busy = true;
			content.classList.add( 'rnp-loading' );

			fetchDoc( url ).then( function ( doc ) {
				var freshContent = doc.querySelector( c.content );
				var freshNav = widgetId && doc.querySelector( '.elementor-element-' + widgetId + ' .rnp' );
				if ( ! freshContent ) {
					throw new Error( 'content not found' );
				}
				content.innerHTML = freshContent.innerHTML;
				reinitElementor( content );
				if ( freshNav ) {
					self.nav.innerHTML = freshNav.innerHTML;
				}
				if ( push ) {
					window.history.pushState( { rnpMain: 1 }, '', url );
				}
				if ( c.scroll ) {
					scrollToEl( content, c.scrollOffset );
				}
				$( document ).trigger( 'rn-pagination/changed', [ url, content ] );
			} ).catch( function () {
				window.location.href = url;
			} ).then( function () {
				self.busy = false;
				content.classList.remove( 'rnp-loading' );
			} );
		}

		this.nav.addEventListener( 'click', function ( e ) {
			var link = e.target.closest( 'a[href]' );
			if ( ! link || e.metaKey || e.ctrlKey || e.shiftKey ) {
				return;
			}
			e.preventDefault();
			load( link.href, true );
		} );

		window.addEventListener( 'popstate', function ( e ) {
			if ( e.state && e.state.rnpMain ) {
				load( window.location.href, false );
			}
		} );
		window.history.replaceState( { rnpMain: 1 }, '', window.location.href );
	};

	/* ------------------------------------------------------------------ */
	/* Boot                                                                */
	/* ------------------------------------------------------------------ */

	function init( scope ) {
		( scope || document ).querySelectorAll( '.rnp[data-rnp]' ).forEach( function ( nav ) {
			if ( ! nav._rnp ) {
				new Pager( nav ); // eslint-disable-line no-new
			}
		} );
	}

	$( window ).on( 'elementor/frontend/init', function () {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/rn_universal_pagination.default', function ( $scope ) {
			init( $scope[ 0 ] );
		} );
	} );

	$( function () {
		init( document );
	} );

	window.RNPagination = { init: init, build: build };
} )( jQuery );
