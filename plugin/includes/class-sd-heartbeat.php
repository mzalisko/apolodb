<?php
/**
 * Відправник heartbeat статусу підключення.
 *
 * Підписує компактний JSON (contract §2.1) і надсилає через `wp_remote_post`
 * на єдиний публічний ендпоінт проксі. Читає лише код відповіді.
 *
 * @package SiteData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Клас SD_Heartbeat.
 */
class SD_Heartbeat {

	/**
	 * Обробник cron.
	 */
	public static function run() {
		if ( ! SD_Settings::is_configured() ) {
			return;
		}
		self::send();
	}

	/**
	 * Побудувати, підписати й надіслати heartbeat.
	 *
	 * @return array {
	 *   @type bool   $ok         Чи 2xx-відповідь.
	 *   @type int    $status     HTTP-код (0 при мережевій помилці).
	 *   @type string $error_code Машинний код помилки (без топології).
	 * }
	 */
	public static function send() {
		$site_id   = SD_Settings::site_id();
		$secret    = SD_Settings::secret();
		$endpoint  = SD_Settings::endpoint();
		$timestamp = time();
		$nonce     = SD_Signer::nonce();

		// Тіло: рівно поля цієї фічі (contract §2.1). Хешуємо саме ці байти.
		$raw_body = wp_json_encode(
			array(
				'site_id'   => $site_id,
				'status'    => 'online',
				'timestamp' => $timestamp,
				'nonce'     => $nonce,
			)
		);

		$canonical = SD_Signer::canonical( 'POST', SD_HEARTBEAT_PATH, $raw_body, $site_id, $timestamp, $nonce );
		$signature = SD_Signer::signature( $canonical, $secret );

		$url = untrailingslashit( $endpoint ) . SD_HEARTBEAT_PATH;

		$response = wp_remote_post(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 0,
				'blocking'    => true,
				'headers'     => array(
					'Content-Type'      => 'application/json; charset=utf-8',
					'X-DB-Site-Id'      => $site_id,
					'X-DB-Timestamp'    => (string) $timestamp,
					'X-DB-Nonce'        => $nonce,
					'X-DB-Signature'    => $signature,
					'X-DB-Sig-Version'  => SD_SIG_VERSION,
				),
				'body'        => $raw_body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'         => false,
				'status'     => 0,
				'error_code' => 'connection_failed',
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		return array(
			'ok'         => ( $status >= 200 && $status < 300 ),
			'status'     => $status,
			'error_code' => ( $status >= 200 && $status < 300 ) ? '' : 'request_rejected',
		);
	}
}
