const Encore = require( '@symfony/webpack-encore' );
const path = require( 'path' );
const directoryName = path.dirname( __filename ).split('/').pop();
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

Encore
	.setOutputPath( 'assets/dist' )
	.setPublicPath( `/${directoryName}/assets/dist` )
	.setManifestKeyPrefix( '' )
	.enableSingleRuntimeChunk( )
	.enableSourceMaps( ! Encore.isProduction() )
	.enableVersioning( Encore.isProduction() )
	.cleanupOutputBeforeBuild( )
	.enableSassLoader( )
	.enablePostCssLoader( )
	.addPlugin( new DependencyExtractionWebpackPlugin() )
	.addStyleEntry( 'backend-styles', './assets/src/sass/backend.scss' )
	.addStyleEntry( 'frontend-styles', './assets/src/sass/frontend.scss' )
	.addEntry( 'editor-block', './assets/src/js/editor-block.js' )
	.addEntry( 'backend-scripts', './assets/src/js/backend.js' )
	.addEntry( 'frontend-scripts', './assets/src/js/frontend.js' )
;

/**
 * Webpack configuration object.
 *
 * Edit for advanced configs.
 */
let config = Encore.getWebpackConfig();

module.exports = config;
