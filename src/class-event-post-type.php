<?php
/**
 * Definición del tipo de contenido "evento"
 *
 * @package BloomUx\WP_CPT_Events
 */

use Queulat\Post_Type;

/**
 * Definición del tipo de contenido "evento"
 */
class Event_Post_Type extends Post_Type {

	/**
	 * Construir instancia, registrar hooks
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Registrar hooks de acciones y filtros
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_filter( "rest_{$this->get_post_type()}_collection_params", array( $this, 'filter_api_collection_params' ), 10 );
		add_filter( "rest_{$this->get_post_type()}_query", array( $this, 'filter_api_query' ), 10, 2 );
		add_filter( "rest_prepare_{$this->get_post_type()}", array( $this, 'filter_api_item' ), 10, 3 );
		add_filter( 'wpseo_schema_graph_pieces', array( $this, 'enable_seo_schema' ), 10, 2 );
		add_filter( 'wp_robots', array( $this, 'filter_robots_meta' ), PHP_INT_MAX - 5 );
	}

	/**
	 * Filtrar valor de meta tag "robots"
	 *
	 * @param array $robots Configuraciones de indexación.
	 * @return array Tag filtrado: noindex si el evento ya terminó
	 */
	public function filter_robots_meta( array $robots ) : array {
		if ( ! is_singular( 'event' ) ) {
			return $robots;
		}
		$event            = new Event_Post_Object();
		$today            = new DateTimeImmutable( 'now', wp_timezone() );
		$started_no_dtend = ! $event->get_end_datetime() && $today > $event->get_start_datetime();
		$is_finished      = $event->get_end_datetime() && $today > $event->get_end_datetime();
		if ( $started_no_dtend || $is_finished ) {
			$robots['index']   = false;
			$robots['noindex'] = true;
		}
		return $robots;
	}

	/**
	 * Encolar scripts de administración
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		// phpcs:ignore
		wp_enqueue_script( 'wp-cpt-event-backend', plugins_url( '/assets/src/js/backend.js', __DIR__ ), array( 'jquery' ), false, true );
	}

	/**
	 * Habilitar integración microdata Yoast SEO
	 *
	 * @param array $pieces "Piezas" del schema de microdatos.
	 * @return array Agregamos microdata de eventos
	 */
	public function enable_seo_schema( $pieces ) {
		require_once __DIR__ . '/class-event-schema.php';
		$pieces[] = new Event_Schema();
		return $pieces;
	}

	/**
	 * Añadir datos personalizados a respuesta de la API
	 *
	 * @param WP_REST_Response $response Respuesta de la API.
	 * @param WP_Post          $post Item de evento como objeto de post.
	 * @param WP_REST_Request  $request Petición a la API.
	 * @return WP_REST_Response Respuesta de la API, filtrada
	 */
	public function filter_api_item( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) {
		$data             = $response->get_data();
		$data['dtstart']  = mysql2date( 'c', $post->dtstart );
		$data['dtend']    = mysql2date( 'c', $post->dtend );
		$data['full_day'] = (bool) $post->event_full_day;
		$response->set_data( $data );
		return $response;
	}

	/**
	 * Filtrar consulta a la API de eventos
	 *
	 * @param array           $args Parámetros para consulta a WordPress.
	 * @param WP_REST_Request $request Petición a la API.
	 * @return array Parámetros de consulta filtrados
	 */
	public function filter_api_query( array $args, WP_REST_Request $request ) {
		$event_query    = new Event_Post_Query();
		$default_params = $event_query->get_default_args();
		if ( $request->get_param( 'orderby' ) === 'dtstart' ) {
			foreach ( $default_params as $key => $val ) {
				$args[ $key ] = $val;
			}
		}
		if ( ! empty( $request->get_param( 'events-tax' ) ) ) {
			add_action(
				'pre_get_posts',
				function( WP_Query $q ) {
					$new_tax_query = array_reduce(
						(array) $q->get( 'tax_query' ),
						function( $carry, $item ) {
							if ( is_array( $item ) ) {
								$item['include_children'] = true;
							}
							$carry[] = $item;
							return $carry;
						},
						array()
					);
					$q->set( 'tax_query', $new_tax_query );
				}
			);
		}
		return $args;
	}

	/**
	 * Añadir posibilidad de ordenar elementos por fecha de inicio
	 *
	 * @param array $params Parámetros para consulta a la API.
	 * @return array Parámetros más opciones custom
	 */
	public function filter_api_collection_params( array $params ) {
		$params['orderby']['enum'][] = 'dtstart';
		return $params;
	}

	/**
	 * Obtener slug del tipo de post
	 *
	 * @return string
	 */
	public function get_post_type() : string {
		return 'event';
	}

	/**
	 * Obtener parámetros para registrar el tipo de contenido
	 *
	 * @return array
	 */
	public function get_post_type_args() : array {
		return array(
			'label'                 => __( 'Eventos', 'cpt_event' ),
			'labels'                => array(
				'name'                     => __( 'Eventos', 'cpt_event' ),
				'singular_name'            => __( 'Eventos', 'cpt_event' ),
				'add_new'                  => __( 'Añadir nueva', 'cpt_event' ),
				'add_new_item'             => __( 'Añadir nueva entrada', 'cpt_event' ),
				'edit_item'                => __( 'Editar entrada', 'cpt_event' ),
				'new_item'                 => __( 'Nueva entrada', 'cpt_event' ),
				'view_item'                => __( 'Ver entrada', 'cpt_event' ),
				'view_items'               => __( 'Ver entradas', 'cpt_event' ),
				'search_items'             => __( 'Buscar entradas', 'cpt_event' ),
				'not_found'                => __( 'No se encontraron entradas.', 'cpt_event' ),
				'not_found_in_trash'       => __( 'Ningún post encontrado en la papelera.', 'cpt_event' ),
				'parent_item_colon'        => null,
				'all_items'                => __( 'Eventos', 'cpt_event' ),
				'archives'                 => __( 'Eventos', 'cpt_event' ),
				'attributes'               => __( 'Atributos de entrada', 'cpt_event' ),
				'insert_into_item'         => __( 'Insertar en la entrada', 'cpt_event' ),
				'uploaded_to_this_item'    => __( 'Subido a esta entrada', 'cpt_event' ),
				'featured_image'           => __( 'Imagen destacada', 'cpt_event' ),
				'set_featured_image'       => __( 'Establecer imagen destacada', 'cpt_event' ),
				'remove_featured_image'    => __( 'Quitar imagen destacada', 'cpt_event' ),
				'use_featured_image'       => __( 'Usar como imagen destacada', 'cpt_event' ),
				'filter_items_list'        => __( 'Lista de entradas filtradas', 'cpt_event' ),
				'items_list_navigation'    => __( 'Navegación por el listado de entradas', 'cpt_event' ),
				'items_list'               => __( 'Lista de entradas', 'cpt_event' ),
				'item_published'           => __( 'Entrada publicada.', 'cpt_event' ),
				'item_published_privately' => __( 'Entrada publicada de forma privada.', 'cpt_event' ),
				'item_reverted_to_draft'   => __( 'Entrada convertida a borrador.', 'cpt_event' ),
				'item_scheduled'           => __( 'Entrada programada.', 'cpt_event' ),
				'item_updated'             => __( 'Entrada actualizada.', 'cpt_event' ),
				'menu_name'                => __( 'Eventos', 'cpt_event' ),
				'name_admin_bar'           => __( 'Eventos', 'cpt_event' ),
			),
			'description'           => __( 'Calendario de eventos académicos, seminarios, etc.', 'cpt_event' ),
			'public'                => true,
			'hierarchical'          => false,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'show_in_admin_bar'     => true,
			'menu_position'         => null,
			'menu_icon'             => 'dashicons-calendar-alt',
			'capability_type'       => array(
				0 => 'event',
				1 => 'events',
			),
			'map_meta_cap'          => true,
			'register_meta_box_cb'  => null,
			'taxonomies'            => array( 'events_tax', 'locations' ),
			'has_archive'           => true,
			'query_var'             => 'event',
			'can_export'            => true,
			'delete_with_user'      => false,
			'rewrite'               => array(
				'with_front' => false,
				'feeds'      => true,
				'pages'      => true,
				'slug'       => 'eventos',
				'ep_mask'    => 1,
			),
			'supports'              => array(
				'title',
				'editor',
				'thumbnail',
				'revisions',
				'author',
			),
			'show_in_rest'          => true,
			'rest_base'             => 'events',
			'rest_controller_class' => false,
		);
	}
}
