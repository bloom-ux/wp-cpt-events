<?php
/**
 * Crear consultas de eventos
 *
 * @package BloomUx\WP_CPT_Events
 */

use Queulat\Post_Query;

/**
 * Crear consultas de eventos
 *
 * @method Event_Post_Object current
 */
class Event_Post_Query extends Post_Query {

	/**
	 * Obtener el slug del tipo de contenido
	 *
	 * @return string
	 */
	public function get_post_type() : string {
		return 'event';
	}

	/**
	 * Obtener el nombre de la clase para los objetos de esta query
	 *
	 * @return string
	 */
	public function get_decorator() : string {
		return Event_Post_Object::class;
	}

	/**
	 * Obtener parámetros predeterminados para consultas de este tipo
	 *
	 * @return array Filtra por fecha de término y ordena por fecha inicio
	 */
	public function get_default_args() : array {
		// phpcs:disable
		return array(
			'meta_query'    => array(
				array(
					'key'     => 'dtend',
					'value'   => date_i18n( 'Y-m-d H:i:s' ),
					'compare' => '>=',
					'type'    => 'DATETIME',
				),
			),
			'no_found_rows' => true,
			'orderby'       => 'meta_value',
			'meta_key'      => 'dtstart',
			'meta_type'     => 'DATETIME',
			'order'         => 'ASC',
		);
		// phpcs:enable
	}
}
