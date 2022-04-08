<?php
/**
 * Microdata para integración con YoastSEO
 *
 * @package BloomUx\WP_CPT_Events
 */

use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Integración con Yoast SEO para generar microdata
 *
 * @package BloomUx\WP_CPT_Events
 */
class Event_Schema extends Abstract_Schema_Piece {
	/**
	 * Generar microdata para incluir en schema
	 *
	 * @return null|array Nulo si no cumple requisitos básicos o array con microdata
	 */
	public function generate() {
		$event           = new Event_Post_Object();
		$attendance_mode = array_key_exists( $event->event_type, $event::get_attendance_modes() ) ? $event->event_type : $event::DEFAULT_ATTENDANCE;
		$status          = array_key_exists( $event->event_status, $event::get_stati() ) ? $event->event_status : $event::DEFAULT_STATUS;
		$schema          = array(
			'@context'            => 'http://schema.org',
			'@type'               => 'Event',
			'name'                => $event->post_title,
			'startDate'           => $event->get_formatted_date( 'c' ),
			'endDate'             => mysql2date( 'c', $event->dtend ),
			'eventStatus'         => "https://schema.org/$status",
			'eventAttendanceMode' => "https://schema.org/$attendance_mode",
			'location'            => array(
				'name' => $event->event_place,
			),
		);
		$locations       = array();
		if ( in_array( $attendance_mode, array( 'OfflineEventAttendanceMode', 'MixedEventAttendanceMode' ), true ) ) {
			$geo = $event->event_geo;
			if ( $geo ) {
				$location['type']    = 'Place';
				$location['name']    = $event->event_place;
				$location['geo']     = array(
					'@type'     => 'GeoCoordinates',
					'latitude'  => (float) $geo->lat ?? 0,
					'longitude' => (float) $geo->lng ?? 0,
				);
				$postal              = array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => $geo->address ?? '',
					'addressLocality' => $this->get_place_prop_by_type( $geo->components, 'locality' ),
					'addressRegion'   => $this->get_place_prop_by_type( $geo->components, 'administrative_area_level_1' ),
					'addressCountry'  => $this->get_place_prop_by_type( $geo->components, 'country' ),
				);
				$location['address'] = $postal;
				$locations[]         = $location;
			}
		}
		if ( in_array( $attendance_mode, array( 'OnlineEventAttendanceMode', 'MixedEventAttendanceMode' ), true ) ) {
			$location    = array(
				'@type' => 'VirtualLocation',
				'url'   => esc_url_raw( $this->get_event_url( $event ) ),
				'name'  => $event->event_virtual_location_name,
			);
			$locations[] = $location;
		}
		if ( ! $locations ) {
			return null;
		}
		$schema['description'] = $event->post_content;
		$schema['image']       = wp_get_attachment_image_url( get_post_thumbnail_id( $event->ID ), 'large' );
		return $schema;
	}

	/**
	 * Obtener URL para un evento
	 *
	 * @param Event_Post_Object $event Instancia del evento.
	 * @return string URL asociada al evento (evento virtual, primer link o permalink)
	 */
	private function get_event_url( $event ) : string {
		if ( ! empty( $event->event_location_url ) && filter_var( $event->event_location_url, FILTER_VALIDATE_URL ) ) {
			return $event->event_location_url;
		}
		$content_url = get_url_in_content( apply_filters( 'the_content', $event->post_content ) );
		if ( ! empty( $content_url ) && filter_var( $content_url, FILTER_VALIDATE_URL ) ) {
			return $content_url;
		}
		return get_permalink( $event->ID );
	}

	/**
	 * Obtener propiedad del objeto de "lugar" del evento
	 *
	 * @param array  $props Set de todas las propiedades.
	 * @param string $type  Key de la propiedad deseada.
	 * @return string Valor de la propiedad o string vacío
	 */
	private function get_place_prop_by_type( $props, string $type ) : string {
		foreach ( $props as $prop ) {
			if ( in_array( $type, $prop->types, true ) ) {
				return $prop->long_name;
			}
		}
		return '';
	}

	/**
	 * Indica cuándo se debe incluir este trozo de schema
	 *
	 * @return bool Cuando el usuario está viendo una página de evento
	 */
	public function is_needed() {
		return is_singular( 'event' );
	}
}
