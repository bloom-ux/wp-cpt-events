<?php
/**
 * Objetos de "eventos"
 *
 * @package BloomUx\WP_CPT_Events
 */

use Queulat\Post_Object;
use Underscore\Types\Strings;
use Spatie\CalendarLinks\Link;

/**
 * Objetos de "eventos"
 */
class Event_Post_Object extends Post_Object {
	const DEFAULT_ATTENDANCE = 'OfflineEventAttendanceMode';
	const DEFAULT_STATUS     = 'EventScheduled';

	/**
	 * Obtener modos de asistencia a un evento
	 *
	 * @return array
	 * @see https://schema.org/EventAttendanceModeEnumeration
	 */
	public static function get_attendance_modes() : array {
		return array(
			'OfflineEventAttendanceMode' => _x( 'Presencial', 'tipos de asistencia', 'cpt_event' ),
			'OnlineEventAttendanceMode'  => _x( 'Online', 'tipos de asistencia', 'cpt_event' ),
			'MixedEventAttendanceMode'   => _x( 'Mixto o semipresencial', 'tipos de asistencia', 'cpt_event' ),
		);
	}

	/**
	 * Obtener opciones de calendarización
	 *
	 * @return array Opciones de calendarización
	 * @see https://schema.org/EventStatusType
	 */
	public static function get_stati() : array {
		return array(
			'EventScheduled'   => _x( 'Sin modificaciones de calendarización', 'status del evento', 'cpt_event' ),
			'EventMovedOnline' => _x( 'Cambia de presencial a online', 'status del evento', 'cpt_event' ),
			'EventRescheduled' => _x( 'Recalendarizado', 'status del evento', 'cpt_event' ),
			'EventCancelled'   => _x( 'Cancelado', 'status del evento', 'cpt_event' ),
			'EventPostponed'   => _x( 'Postpuesto', 'status del evento', 'cpt_event' ),
		);
	}

	/**
	 * Permite obtener ciertos metadatos como propiedades
	 *
	 * @param string $key Nombre de la propiedad que intenta obtener.
	 * @return string|array Valor que devuelve el método o propiedad accedida
	 */
	public function __get( string $key ) {
		if ( 'dtstart' === $key ) {
			return $this->get_dt_start();
		}
		if ( 'dtstart_day' === $key ) {
			return $this->get_formatted_date( 'j' );
		}
		if ( 'dtstart_month_name' === $key ) {
			return $this->get_dt_start_month_name();
		}
		if ( 'dtstart_time_hour' === $key ) {
			return $this->post->dtstart_time_hour;
		}
		if ( 'dtstart_time_minutes' === $key ) {
			return $this->post->dtstart_time_minutes;
		}
		if ( 'event_place' === $key ) {
			return $this->post->event_location;
		}
		return parent::__get( $key );
	}

	/**
	 * Permitir llamar métodos de la clase como snakeCase
	 *
	 * @param array $name Nombre del método.
	 * @param mixed $arguments Argumentos para el método.
	 * @return mixed Lo que sea que devuelve el método compatible
	 * @throws BadFunctionCallException En caso de que no exista método compatible.
	 */
	public function __call( $name, $arguments ) {
		$method = Strings::toSnakeCase( $name );
		if ( ! method_exists( $this, $method ) ) {
			throw new BadFunctionCallException( "no existe el método ${name} ({$method})" );
		}
		return call_user_func_array( array( $this, $method ), $arguments );
	}

	/**
	 * Obtener fecha de inicio en el formato especificado
	 *
	 * @param string $format Formato de fecha (ver doc de php.net).
	 * @return string Fecha formateada y traducida
	 */
	public function get_formatted_date( string $format = '' ) : string {
		return mysql2date( $format, $this->post->dtstart, true );
	}

	/**
	 * Obtener fecha de inicio del evento
	 *
	 * @return string Fecha inicio en formato Y-m-d
	 */
	public function get_dt_start() : string {
		$fdate = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $this->post->dtstart );
		return $fdate ? $fdate->format( 'Y-m-d' ) : '';
	}

	/**
	 * Obtener la fecha de inicio como objeto DateTimeImmutable
	 *
	 * @return null|DateTimeImmutable Fecha de inicio o nulo si no es válida
	 */
	public function get_start_datetime() : ?DateTimeImmutable {
		$dtstart = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $this->post->dtstart, wp_timezone() );
		return isset( $dtstart ) ? $dtstart : null;
	}

	/**
	 * Obtener fecha de término como objeto DateTimeImmutable
	 *
	 * @return null|DateTimeImmutable Fecha de término o nulo si no es válida
	 */
	public function get_end_datetime() : ?DateTimeImmutable {
		$dtend = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $this->post->dtend, wp_timezone() );
		return isset( $dtend ) ? $dtend : null;
	}

	/**
	 * Obtener mes de inicio como string
	 *
	 * @return string Nombre del mes de inicio del evento
	 */
	public function get_dt_start_month_name() : string {
		$fdate = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $this->post->dtstart );
		if ( ! $fdate ) {
			return '';
		}
		return date_i18n( 'M', $fdate->format( 'U' ) );
	}

	/**
	 * Obtener fecha(s) de realización
	 *
	 * @return string Fecha de inicio y término, si corresponde
	 */
	public function get_date_range() : string {
		/* translators: Formato fecha en vista individual */
		$format  = __( 'j \d\e F Y', 'cpt_event' );
		$dtstart = mysql2date( $format, $this->post->dtstart );
		$dtend   = mysql2date( $format, $this->post->dtend );
		if ( $dtstart === $dtend ) {
			return $dtstart;
		}
		/* translators: %1 fecha de inicio y %2 fecha de término */
		return sprintf( __( '%1$s al %2$s', 'cpt_event' ), $dtstart, $dtend );
	}

	/**
	 * Obtener el rango de duración del evento, como string
	 *
	 * @return string Hora de inicio y/o término
	 */
	public function get_time_range() : string {
		if ( (bool) $this->post->event_full_day ) {
			/* translators: %s hora de inicio */
			return sprintf( __( 'Desde las %shrs.', 'cpt_event' ), $this->get_formatted_date( 'H:i' ) );
		}

		$time_start = mysql2date( 'H:i', $this->post->dtstart, true );
		$time_end   = mysql2date( 'H:i', $this->post->dtend, true );

		if ( $time_start === $time_end ) {
			/* translators: %s hora de inicio */
			return sprintf( __( '%shrs.', 'cpt_event' ), $time_start );
		}

		/* translators: %1: hora de inicio; %2: hora de término */
		return sprintf( __( '%1$s - %2$shrs.', 'cpt_event' ), $time_start, $time_end );
	}

	/**
	 * Obtener enlace para agregar evento a calendario de Google
	 *
	 * @return string URL para agregar a Google Calendar
	 */
	public function get_calendar_link() : string {
		static $link;
		if ( $link instanceof Link ) {
			return $link->google();
		}
		try {
			$start     = $this->get_formatted_date( 'c' );
			$from_date = new DateTime( $start, wp_timezone() );
			$to_date   = new DateTime( $this->post->dtend, wp_timezone() );
			$link      = Link::create(
				$this->post->post_title,
				$from_date,
				$to_date,
				false
			);
			$link->description(
				sprintf(
					/* translators: %s: enlace permanente al post */
					__( 'Más información en: %s', 'cpt_event' ),
					get_permalink( $this->post )
				)
			);
			$address = ! empty( $this->post->event_geo->address ) ? $this->post->event_geo->address : '';
			if ( 'OnlineEventAttendanceMode' !== $this->post->event_type && ! empty( $address ) ) {
				$link->address(
					$address
				);
			}
			return $link->google();
		} catch ( \Exception $e ) {
			if ( function_exists( 'SimpleLogger' ) ) {
				SimpleLogger()->debug(
					'Error al parsear fecha para enlace de Google Calendar',
					array(
						'exception' => $e->__toString(),
					)
				);
			}
			$link = '';
		}
		return $link;
	}
}
