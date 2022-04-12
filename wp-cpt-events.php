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

add_action( 'init', 'bloom_ux_wp_cpt_events_register_tax', 50 );

/**
 * Registrar una taxonomía personalizada para clasificar eventos
 *
 * @return void
 */
function bloom_ux_wp_cpt_events_register_tax() {
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

add_action( 'init', 'bloom_ux_wp_cpt_events_register_block' );

/**
 * Registrar el bloque para el editor
 *
 * @return void
 */
function bloom_ux_wp_cpt_events_register_block() {
	wp_register_script(
		'bloom_ux_wp_cpt_events_runtime',
		bloom_ux_get_path_from_manifest( 'runtime.js' ),
		array(),
		null,
		false
	);
	wp_register_script(
		'bloom_ux_wp_cpt_events_block',
		bloom_ux_get_path_from_manifest( 'editor-block.js' ),
		array( 'bloom_ux_wp_cpt_events_runtime' ),
		null,
		false
	);
	register_block_type(
		'bloom-ux/wp-cpt-events',
		array(
			'title' => 'Eventos',
			'icon' => 'calendar-alt',
			'category' => 'widgets',
			'editor_script' => 'bloom_ux_wp_cpt_events_block'
		)
	);
}

function bloom_ux_get_path_from_manifest( string $key ) : string {
	static $manifest;
	if ( ! $manifest ) {
		$manifest = json_decode( file_get_contents( __DIR__ . '/assets/dist/manifest.json' ) );
	}
	if ( ! isset( $manifest->{$key } ) ) {
		return '';
	}
	return plugins_url( $manifest->{$key} );
}
