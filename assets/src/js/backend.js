;( function( $ ){
	var maybeHideFields = function(){
		var eventType = $('input[name="event_metabox[type]"]:checked').val();
		switch ( eventType ) {
			case 'OfflineEventAttendanceMode':
				$('input[name="event_metabox[location_url]"]').closest('tr').hide();
				$('input[name="event_metabox[virtual_location_name]"]').closest('tr').hide();
				$('input[name="event_metabox[location]"]').closest('tr').show();
				$('.gmapsearch__components').closest('tr').show();
				break;
			case 'OnlineEventAttendanceMode':
				$('input[name="event_metabox[location_url]"]').closest('tr').show();
				$('input[name="event_metabox[virtual_location_name]"]').closest('tr').show();
				$('input[name="event_metabox[location]"]').closest('tr').hide();
				$('.gmapsearch__components').closest('tr').hide();
				break;
			case 'MixedEventAttendanceMode':
				$('input[name="event_metabox[location_url]"]').closest('tr').show();
				$('input[name="event_metabox[virtual_location_name]"]').closest('tr').show();
				$('input[name="event_metabox[location]"]').closest('tr').show();
				$('.gmapsearch__components').closest('tr').show();
				break;
		}
	};
	$( document ).ready( function(){
		maybeHideFields();
		$( 'input[name="event_metabox[type]"]' ).on( 'change', function(){
			maybeHideFields();
		} );
	} );
} )( jQuery );
