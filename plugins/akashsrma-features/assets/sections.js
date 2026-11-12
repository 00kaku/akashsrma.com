/**
 * Content → Sections: reorder sections by dragging or with the arrows, add and remove them, and choose a
 * collection's posts. Saving is an ordinary form post; the rows' order is the order sent.
 */
/* global jQuery */
( function ( $ ) {
	const list = $( '.akashsrma-sections__list' );
	list.sortable( {
		handle: '.akashsrma-sections__handle',
		axis: 'y',
		placeholder: 'akashsrma-sections__placeholder',
	} );

	list.on( 'click', '[data-move]', function () {
		const row = $( this ).closest( '.akashsrma-sections__row' );
		if ( Number( this.dataset.move ) < 0 ) {
			row.prev().before( row );
		} else {
			row.next().after( row );
		}
		$( this ).trigger( 'focus' );
	} );

	list.on( 'click', '[data-remove]', function () {
		$( this ).closest( '.akashsrma-sections__row' ).remove();
	} );

	// A collection's chosen posts: search to add (WordPress's REST API), drag to order, × to remove, up to
	// data-max. Changing the row's collection clears them, as they belong to the other one.
	const routes = list.data( 'routes' ) || {};
	const pickerOf = ( el ) =>
		$( el )
			.closest( '.akashsrma-sections__row' )
			.find( '.akashsrma-sections__picker' );
	const typeOf = ( picker ) =>
		picker
			.closest( '.akashsrma-sections__row' )
			.find( 'select[name$="[post_type]"]' )
			.val();
	const sync = ( picker ) => {
		const max = Number( picker.data( 'max' ) );
		const count = picker.find( '.akashsrma-sections__picks li' ).length;
		picker
			.find( '.akashsrma-sections__search' )
			.prop( 'disabled', count >= max );
		picker
			.find( '.akashsrma-sections__count' )
			.text(
				count >= max
					? `${ count } of ${ max } chosen — remove one to add another.`
					: `${ count } of ${ max } chosen.`
			);
	};
	const add = ( picker, id, title ) => {
		const item = $( '<li>' ).append(
			'<span class="akashsrma-sections__pick-handle dashicons dashicons-menu" aria-hidden="true"></span>',
			$( '<span class="akashsrma-sections__pick-title">' ).text( title ),
			$( '<input type="hidden">' ).attr( {
				name: picker.data( 'field' ),
				value: id,
			} ),
			'<button type="button" class="button-link akashsrma-sections__unpick" aria-label="Remove"><span class="dashicons dashicons-no-alt"></span></button>'
		);
		picker.find( '.akashsrma-sections__picks' ).append( item );
		sync( picker );
	};
	const setup = ( scope ) =>
		scope.find( '.akashsrma-sections__picker' ).each( function () {
			const picker = $( this );
			picker.find( '.akashsrma-sections__picks' ).sortable( {
				handle: '.akashsrma-sections__pick-handle',
				axis: 'y',
			} );
			picker.find( '.akashsrma-sections__search' ).autocomplete( {
				minLength: 2,
				delay: 250,
				source( request, respond ) {
					const taken = picker
						.find( 'input[type="hidden"]' )
						.map( ( i, input ) => Number( input.value ) )
						.get();
					wp.apiFetch( {
						path: wp.url.addQueryArgs( routes[ typeOf( picker ) ], {
							search: request.term,
							per_page: 10,
							_fields: 'id,title',
						} ),
					} )
						.then( ( posts ) =>
							respond(
								posts
									.filter(
										( post ) => ! taken.includes( post.id )
									)
									.map( ( post ) => ( {
										label: wp.htmlEntities.decodeEntities(
											post.title.rendered
										),
										value: post.id,
									} ) )
							)
						)
						.catch( () => respond( [] ) );
				},
				select( event, ui ) {
					event.preventDefault();
					add( picker, ui.item.value, ui.item.label );
					$( this ).val( '' );
				},
				focus( event ) {
					event.preventDefault();
				},
			} );
			sync( picker );
		} );
	setup( list );
	list.on( 'click', '.akashsrma-sections__unpick', function () {
		const picker = pickerOf( this );
		$( this ).closest( 'li' ).remove();
		sync( picker );
	} );
	list.on( 'change', 'select[name$="[post_type]"]', function () {
		const picker = pickerOf( this );
		picker.find( '.akashsrma-sections__picks' ).empty();
		sync( picker );
	} );

	$( '[data-add]' ).on( 'click', function () {
		const template = $( `template[data-row="${ this.dataset.add }"]` );
		const html = template.html().replaceAll( '__i__', `n${ Date.now() }` );
		const row = $( html ).appendTo( list );
		setup( row );
		row.find( 'select' ).first().trigger( 'focus' );
	} );
} )( jQuery );
