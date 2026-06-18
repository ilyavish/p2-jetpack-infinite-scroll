( function( $ ) {
	'use strict';

	function postIdFromElement( element ) {
		var id = $( element ).attr( 'id' ) || '';

		if ( id.indexOf( 'prologue-' ) !== 0 ) {
			return '';
		}

		return id.replace( 'prologue-', '' );
	}

	function rememberP2Post( element ) {
		var id = $( element ).attr( 'id' );
		var postId = postIdFromElement( element );

		if ( ! id || ! postId ) {
			return;
		}

		if ( $.isArray( window.postsOnPage ) && $.inArray( id, window.postsOnPage ) === -1 ) {
			window.postsOnPage.push( id );
		}

		if ( typeof window.postsOnPageQS === 'string' && window.postsOnPageQS.indexOf( 'vp[]=' + postId ) === -1 ) {
			window.postsOnPageQS += '&vp[]=' + postId;
		}
	}

	function bindAppendedEntry( element ) {
		var $entry = $( element );

		rememberP2Post( element );

		$entry
			.find( '> div.postcontent, > h4, li[id^="comment"] > div.commentcontent, li[id^="comment"] > h4' )
			.off( '.p2JetpackInfiniteScroll' )
			.on( 'mouseenter.p2JetpackInfiniteScroll', function() {
				$( this ).parents( 'li' ).eq( 0 ).addClass( 'selected' );
			} )
			.on( 'mouseleave.p2JetpackInfiniteScroll', function() {
				$( this ).parents( 'li' ).eq( 0 ).removeClass( 'selected' );
			} );

		$entry.find( 'a.comment-reply-link' )
			.off( '.p2JetpackInfiniteScroll' )
			.on( 'click.p2JetpackInfiniteScroll', function() {
				$( '*' ).removeClass( 'replying' );
				$( this ).parents( 'li' ).eq( 0 ).addClass( 'replying' );
				$( '#respond' ).addClass( 'replying' ).show();
				$( '#comment' ).trigger( 'focus' );
			} );

		if ( window.wp && window.wp.a11y && typeof window.wp.a11y.speak === 'function' ) {
			window.wp.a11y.speak( '' );
		}

		$entry.find( 'abbr[title]' ).each( function() {
			if ( window.locale && window.p2txt && typeof window.locale.parseISO8601 === 'function' ) {
				var date = window.locale.parseISO8601( $( this ).attr( 'title' ) );

				if ( date ) {
					$( this ).html(
						window.p2txt.date_time_format
							.replace( '%1$s', window.locale.date( window.p2txt.time_format, date ) )
							.replace( '%2$s', window.locale.date( window.p2txt.date_format, date ) )
					);
				}
			}
		} );
	}

	function normalizePostList() {
		$( '#postlist > .infinite-wrap' ).each( function() {
			var $wrapper = $( this );
			var $posts = $wrapper.children( 'li[id^="prologue-"]' );

			if ( $posts.length ) {
				$wrapper.replaceWith( $posts );
			}
		} );
	}

	$( document.body ).on( 'post-load', function() {
		normalizePostList();

		$( '#postlist > li[id^="prologue-"]' ).not( '.p2-jetpack-is-bound' ).each( function() {
			$( this ).addClass( 'p2-jetpack-is-bound' );
			bindAppendedEntry( this );
		} );
	} );
}( jQuery ) );
