<?php
/**
 * Plugin Name:       Данные сайта
 * Description:       Приёмник данных сайта: вывод контактных данных и отчёт статуса подключения.
 * Version:           0.1.0
 * Requires PHP:      8.3
 * Text Domain:       sd
 *
 * Нейтральна ідентичність: жодних згадок централізованої панелі керування.
 * Vanilla PHP на WordPress Plugin API — без Laravel (Конституція, Принцип I).
 *
 * @package SiteData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Прямий доступ заборонено.
}

define( 'SD_VERSION', '0.1.0' );
define( 'SD_PATH', plugin_dir_path( __FILE__ ) );
define( 'SD_SIG_VERSION', 'v1' );          // версія схеми підпису (contract §0.2)
define( 'SD_HEARTBEAT_PATH', '/v1/heartbeat' ); // логічний шлях, що підписується (contract §2)
define( 'SD_CRON_HOOK', 'sd_send_heartbeat' );
define( 'SD_CRON_SCHEDULE', 'sd_every_minute' );

require_once SD_PATH . 'includes/class-sd-settings.php';
require_once SD_PATH . 'includes/class-sd-signer.php';
require_once SD_PATH . 'includes/class-sd-heartbeat.php';
require_once SD_PATH . 'includes/class-sd-admin.php';

/**
 * Кастомний cron-інтервал ~60 c (A-2, FR-009).
 *
 * @param array $schedules Наявні розклади.
 * @return array
 */
function sd_add_cron_schedule( $schedules ) {
	$schedules[ SD_CRON_SCHEDULE ] = array(
		'interval' => 60,
		'display'  => __( 'Кожну хвилину (Данные сайта)', 'sd' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'sd_add_cron_schedule' );

/**
 * Активація: запланувати heartbeat.
 */
function sd_activate() {
	if ( ! wp_next_scheduled( SD_CRON_HOOK ) ) {
		wp_schedule_event( time(), SD_CRON_SCHEDULE, SD_CRON_HOOK );
	}
}
register_activation_hook( __FILE__, 'sd_activate' );

/**
 * Деактивація: зняти розклад.
 */
function sd_deactivate() {
	$timestamp = wp_next_scheduled( SD_CRON_HOOK );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, SD_CRON_HOOK );
	}
}
register_deactivation_hook( __FILE__, 'sd_deactivate' );

// Обробник cron -> відправка heartbeat.
add_action( SD_CRON_HOOK, array( 'SD_Heartbeat', 'run' ) );

// Адмінка (меню + вкладка «Подключение»).
add_action( 'admin_menu', array( 'SD_Admin', 'register_menu' ) );
add_action( 'admin_init', array( 'SD_Admin', 'register_settings' ) );
add_action( 'admin_post_sd_test_connection', array( 'SD_Admin', 'handle_test_connection' ) );
