<?php
/**
 * Зберігання конфігурації плагіна у WordPress Options API.
 *
 * Три значення: публічний site-id, секретний ключ підпису (HMAC), публічний
 * ендпоінт проксі. Секрет ніколи не показується повторно (FR-004/FR-024);
 * жодних захардкоджених адрес CRM (Принцип II).
 *
 * @package SiteData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Клас SD_Settings — доступ до конфігурації.
 */
class SD_Settings {

	const OPT_SITE_ID  = 'sd_site_id';
	const OPT_SECRET   = 'sd_signing_secret';
	const OPT_ENDPOINT = 'sd_endpoint_url';

	/**
	 * Публічний site-ідентифікатор.
	 *
	 * @return string
	 */
	public static function site_id() {
		return (string) get_option( self::OPT_SITE_ID, '' );
	}

	/**
	 * Секретний ключ підпису. Пріоритет — wp-config константа (поза БД).
	 *
	 * @return string
	 */
	public static function secret() {
		if ( defined( 'SD_SIGNING_SECRET' ) && '' !== SD_SIGNING_SECRET ) {
			return (string) SD_SIGNING_SECRET;
		}
		return (string) get_option( self::OPT_SECRET, '' );
	}

	/**
	 * Публічний ендпоінт проксі (єдина адреса, яку знає плагін).
	 *
	 * @return string
	 */
	public static function endpoint() {
		if ( defined( 'SD_ENDPOINT_URL' ) && '' !== SD_ENDPOINT_URL ) {
			return (string) SD_ENDPOINT_URL;
		}
		return (string) get_option( self::OPT_ENDPOINT, '' );
	}

	/**
	 * Чи задано мінімальну конфігурацію для звітів.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== self::site_id() && '' !== self::secret() && '' !== self::endpoint();
	}

	/**
	 * Зареєструвати опції (autoload=no для секрету).
	 */
	public static function register() {
		register_setting(
			'sd_connection',
			self::OPT_SITE_ID,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'sd_connection',
			self::OPT_ENDPOINT,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
		register_setting(
			'sd_connection',
			self::OPT_SECRET,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_secret' ),
				'default'           => '',
				'autoload'          => false,
			)
		);
	}

	/**
	 * Санітизація секрету: порожнє поле НЕ перезаписує збережене значення
	 * (secret ніколи не рендериться назад у форму).
	 *
	 * @param string $value Введене значення.
	 * @return string
	 */
	public static function sanitize_secret( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return (string) get_option( self::OPT_SECRET, '' ); // лишити як є
		}
		return sanitize_text_field( $value );
	}
}
