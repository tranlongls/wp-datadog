<?php
/**
 * An example plugin for a membership course walking readers through how to work with GitHub.
 *
 * @link              https://github.com/tranlongls/wp-datadog
 * @since             1.0.0
 * @package           WPHW
 *
 * @wordpress-plugin
 * Plugin Name:       WP DataDog
 * Plugin URI:        https://github.com/tranlongls/wp-datadog
 * Description:       DataDog plugin, change your datadog configuration in Settings
 * Version:           1.0.0
 * Author:            Long Tran
 * Author URI:        http://tranlong.n4u.vn/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
define( 'DATADOG__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DATADOG__OPTION_NAME', "datadog_settings" );

require_once DATADOG__PLUGIN_DIR . 'DogStatsd.php';
require_once DATADOG__PLUGIN_DIR . 'Tracer.php';
require_once DATADOG__PLUGIN_DIR . 'Span.php';
require_once DATADOG__PLUGIN_DIR . 'settings.php';

function datadogFinish() {
	$time_elapsed = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
	$settings = get_option( DATADOG__OPTION_NAME );
	if($settings['tracer_enable']){
		$tracerHost = $settings['tracer_host'];
		$tracerPort = $settings['tracer_port'];
		global $wp;
		if(is_admin()){
			$resource = $_SERVER['REQUEST_URI'];	
		}
		else{
			$resource = "/" . $wp->request;
		}

		$tracer = new Tracer([
			"host" => $tracerHost,
			"port" => $tracerPort,
			"service" => $settings['tracer_service']
 		]);

 		$span = new Span([
 			"start" => ($_SERVER['REQUEST_TIME_FLOAT'] * 1000 * 1000),
 			"resource" => $resource
 		]);

 		$span->finish();
 		$tracer->flush($span->export());

 		$tracer->sendRequest();
	}

	if($settings['metric_enable']){
		$metricHost = $settings['metric_host'];
		$metricPort = $settings['metric_port'];
		$metricConfig = [
			"host" => $metricHost,
			"port" => $metricPort
		];
		$statsd = new DogStatsd($metricConfig);
		$statsd->increment($settings['prefix'].'request_count');
		$statsd->timing($settings['prefix'].'request_duration', $time_elapsed);
	}
}
register_shutdown_function('datadogFinish');

