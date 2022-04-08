<?php
/**
 * Plugin Name: Administración de eventos (Agenda de actividades)
 * Plugin URI: https://github.com/bloom-ux/wp-cpt-events/
 * Description: Calendario de eventos y actividades
 * Version: 0.1.0
 * Author: Bloom User Experience
 * Author URI: https://www.bloom-ux.com
 * License: GPL-3.0-or-later
 *
 * @package BloomUx\WP_CPT_Events
 */

register_activation_hook(
	__FILE__,
	function() {
		require_once __DIR__ . '/src/class-event-post-type.php';
		Event_Post_Type::activate_plugin();
	}
);

add_action(
	'plugins_loaded',
	function() {
		require_once __DIR__ . '/src/class-event-post-type.php';
		require_once __DIR__ . '/src/class-event-post-query.php';
		require_once __DIR__ . '/src/class-event-post-object.php';
		require_once __DIR__ . '/src/class-event-metabox.php';
		$metabox = new Event_Metabox( 'event', 'Información del Evento', 'event' );
		$metabox->init();
	}
);

add_action( 'init', array( 'Event_Post_Type', 'register_post_type' ) );

add_action( 'init', 'event_cpt_register_tax', 50 );

/**
 * Registrar una taxonomía personalizada para clasificar eventos
 *
 * @return void
 */
function event_cpt_register_tax() {
	register_taxonomy(
		'events_tax',
		array( 'event' ),
		array(
			'labels'            => array(
				'name'          => _x( 'Categorías de eventos', 'cpt_event' ),
				'singular_name' => _x( 'Categoría de evento', 'cpt_event' ),
			),
			'public'            => true,
			'show_in_nav_menus' => false,
			'show_ui'           => true,
			'show_tagcloud'     => false,
			'hierarchical'      => true,
			'rewrite'           => array(
				'slug'       => 'ver-calendario',
				'with_front' => false,
			),
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rest_base'         => 'events-tax',
		)
	);
}
