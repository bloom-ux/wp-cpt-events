<?php
/**
 * Meta datos de eventos
 *
 * @package BloomUx\WP_CPT_Events
 */

use Queulat\Metabox;
use Queulat\Forms\Node_Factory;
use Queulat\Forms\Element\Input;
use Queulat\Forms\Element\Select;
use Queulat\Forms\Element\Input_Url;
use Queulat\Forms\Element\Google_Map;
use Queulat\Forms\Element\Input_Text;
use Queulat\Forms\Element\Input_Radio;
use Queulat\Forms\Element\Input_Checkbox;

/**
 * Meta datos de eventos
 *
 * @package BloomUx\WP_CPT_Events
 */
class Event_Metabox extends Metabox {

	/**
	 * Inicializar meta box
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		add_action( $this->get_id() . '_metabox_data_updated', array( $this, 'append_data' ), 10, 2 );
	}

	/**
	 * Complementar meta datos del evento. Agrega dtstart y dtevent en formato MySQL DATETIME
	 *
	 * @param array $data Meta datos sanitizados.
	 * @param int   $post_id ID del post que se está actualizando.
	 * @return void
	 */
	public function append_data( $data, $post_id ) {
		if ( ! empty( $data['dtstart_date'] ) ) {
			$dtstart_time = $data['dtstart_time'] ?? '00:00';
			$dtstart      = DateTime::createFromFormat( 'Y-m-d H:i:s', "{$data['dtstart_date']} {$dtstart_time}:00" );
			if ( $dtstart ) {
				update_post_meta( $post_id, 'dtstart', $dtstart->format( 'Y-m-d H:i:s' ) );
				// por defecto la fecha de término es la misma que inicio.
				$dtend = clone $dtstart;
				if ( ! empty( $data['dtend_date'] ) ) {
					$maybe_dtend = DateTime::createFromFormat( 'Y-m-d', $data['dtend_date'] );
					// la fecha de término debe ser igual o superior a la de inicio.
					if ( $maybe_dtend instanceof \DateTime ) {
						if ( $maybe_dtend >= $dtstart ) {
							$dtend = $maybe_dtend;
						} else {
							// la fecha de término era inferior; corregir.
							update_post_meta( $post_id, 'event_dtend_date', $dtstart->format( 'Y-m-d' ) );
						}
					}
				}
				if ( isset( $data['full_day'] ) ) {
					$dtend = $dtend->setTime( 23, 59, 59 );
				} elseif ( ! empty( $data['dtend_time'] ) ) {
					list( $hour, $minutes ) = explode( ':', $data['dtend_time'] );
					$dtend->setTime( $hour, $minutes, 0 );
				}
				update_post_meta( $post_id, 'dtend', $dtend->format( 'Y-m-d H:i:s' ) );
			}
		}
	}

	/**
	 * Obtener campos del meta box
	 *
	 * @return Queulat\Forms\Node_Interface[] Campos para el meta box
	 */
	public function get_fields() : array {
		$fields = array(
			Node_Factory::make(
				Input_Checkbox::class,
				array(
					'name'    => 'featured',
					'label'   => 'Evento Destacado',
					'options' => array(
						1 => 'Este es un evento destacado',
					),
				)
			),
			Node_Factory::make(
				Input::class,
				array(
					'name'       => 'dtstart_date',
					'label'      => 'Fecha de Inicio',
					'attributes' => array(
						'type' => 'date',
					),
				)
			),
			Node_Factory::make(
				Input::class,
				array(
					'name'       => 'dtstart_time',
					'label'      => 'Hora de Inicio',
					'attributes' => array(
						'type' => 'time',
					),
					'properties' => array(
						'description' => 'Utilizar formato 24 horas (p.ej: 15:00)',
					),
				)
			),
			Node_Factory::make(
				Input_Checkbox::class,
				array(
					'name'    => 'full_day',
					'label'   => 'Día completo',
					'options' => array(
						1 => 'El evento dura todo el día (o no tiene horario de término)',
					),
				)
			),
			Node_Factory::make(
				Input::class,
				array(
					'name'       => 'dtend_time',
					'label'      => 'Hora de término',
					'attributes' => array(
						'type' => 'time',
					),
					'properties' => array(
						'description' => 'Utilizar formato 24 horas (p.ej: 15:00)',
					),
				)
			),
			Node_Factory::make(
				Input::class,
				array(
					'name'       => 'dtend_date',
					'label'      => 'Fecha de término',
					'attributes' => array(
						'type' => 'date',
					),
				)
			),
			Node_Factory::make(
				Select::class,
				array(
					'name'                   => 'status',
					'label'                  => 'Estado del evento',
					'options'                => Event_Post_Object::get_stati(),
					'properties.description' => 'Indica si el evento ha tenido cambio de modalidad o se ha recalendarizado',
				)
			),
			Node_Factory::make(
				Input_Radio::class,
				array(
					'name'    => 'type',
					'label'   => 'Tipo de evento',
					'options' => Event_Post_Object::get_attendance_modes(),
				)
			),
			Node_Factory::make(
				Input_Url::class,
				array(
					'name'                   => 'location_url',
					'label'                  => 'URL del evento',
					'attributes'             => array(
						'class' => 'widefat',
					),
					'properties.description' => 'Indica el link a la página web donde se realizará el evento (perfil de instagram, canal de YouTube u otro)',
				)
			),
			Node_Factory::make(
				Input_Text::class,
				array(
					'name'                   => 'virtual_location_name',
					'label'                  => 'Nombre de la ubicación virtual',
					'attributes.class'       => 'regular-text',
					'properties.description' => 'Nombre de la página donde se realizará el evento; por ejemplo "Mi canal de YouTube"',
				)
			),
			Node_Factory::make(
				Input_Text::class,
				array(
					'name'       => 'location',
					'label'      => 'Lugar del evento (presencial)',
					'attributes' => array(
						'class' => 'widefat',
					),
					'properties' => array(
						'description' => 'P.ej: "Calle o avenida ###, localidad, indicaciones para llegar"',
					),
				)
			),
			Node_Factory::make(
				Google_Map::class,
				array(
					'name'  => 'geo',
					'label' => 'Busca la dirección',
				)
			),
		);
		return $fields;
	}

	/**
	 * Sanitizar fecha
	 *
	 * @param string $input Fecha en formato Y-m-d.
	 * @return string Fecha sanitizada o string vacío
	 */
	public function sanitize_date( string $input ) : string {
		$date = DateTime::createFromFormat( 'Y-m-d', $input );
		if ( $date instanceof DateTime ) {
			return $date->format( 'Y-m-d' );
		}
		return '';
	}

	/**
	 * Sanitizar string de tiempo
	 *
	 * @param string $input Tiempo en formato "hora:minutos".
	 * @return string Tiempo sanitizado o string vacío
	 */
	public function sanitize_time( string $input ) : string {
		$time = DateTime::createFromFormat( 'H:i', $input );
		if ( $time instanceof DateTime ) {
			return $time->format( 'H:i' );
		}
		return '';
	}

	/**
	 * Validar status del evento
	 *
	 * @param string $input Status sin sanitizar.
	 * @return string Status sanitizado
	 * @see Event_Post_Object::get_stati()
	 */
	public function validate_event_status( string $input ) : string {
		return array_key_exists( $input, Event_Post_Object::get_stati() ) ? $input : Event_Post_Object::DEFAULT_STATUS;
	}

	/**
	 * Validar tipo de evento (online, presencial, mixto)
	 *
	 * @param string $input Tipo de evento.
	 * @return string Tipo de evento sanitizado
	 * @see Event_Post_Object::get_attendance_modes()
	 */
	public function validate_event_type( string $input ) : string {
		return array_key_exists( $input, Event_Post_Object::get_attendance_modes() ) ? $input : Event_Post_Object::DEFAULT_ATTENDANCE;
	}

	/**
	 * Sanitizar datos del meta box
	 *
	 * @param array $data Datos del metabox sin sanitizar.
	 * @return array Datos sanitizados
	 */
	public function sanitize_data( array $data ) : array {
		$sanitized = queulat_sanitizer(
			$data,
			array(
				'featured'              => array( 'boolval' ),
				'dtstart_date'          => array( array( $this, 'sanitize_date' ) ),
				'dtstart_time'          => array( array( $this, 'sanitize_time' ) ),
				'full_day'              => array( 'boolval' ),
				'dtend_time'            => array( array( $this, 'sanitize_time' ) ),
				'dtend_date'            => array( array( $this, 'sanitize_date' ) ),
				'status'                => array( array( $this, 'validate_event_status' ) ),
				'type'                  => array( array( $this, 'validate_event_type' ) ),
				'location'              => array( 'sanitize_text_field' ),
				'location_url'          => array( 'esc_url_raw' ),
				'virtual_location_name' => array( 'sanitize_text_field' ),
				'geo.address'           => array( 'sanitize_text_field' ),
				'geo.lat'               => array( 'floatval' ),
				'geo.lng'               => array( 'floatval' ),
				'geo.zoom'              => array( 'absint' ),
				'geo.components'        => array( 'json_decode' ),
			)
		);
		if ( isset( $sanitized['geo'] ) ) {
			$sanitized['geo'] = (object) $sanitized['geo'];
		}
		return $sanitized;
	}
}
