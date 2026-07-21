<?php
/**
 * Адмін-інтерфейс: меню «Данные сайта» + вкладка «Подключение».
 *
 * Нейтральна ідентичність, нейтральні повідомлення про помилки (FR-032).
 *
 * @package SiteData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Клас SD_Admin.
 */
class SD_Admin {

	const CAP  = 'manage_options';
	const SLUG = 'sd-connection';

	/**
	 * Пункт меню зі стандартною іконкою (без брендингу).
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Данные сайта', 'sd' ),
			__( 'Данные сайта', 'sd' ),
			self::CAP,
			self::SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-database',
			80
		);
	}

	/**
	 * Реєстрація опцій.
	 */
	public static function register_settings() {
		SD_Settings::register();
	}

	/**
	 * Рендер вкладки «Подключение».
	 */
	public static function render_page() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$has_secret = '' !== SD_Settings::secret();
		$configured = SD_Settings::is_configured();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Данные сайта — Подключение', 'sd' ); ?></h1>

			<p>
				<strong><?php esc_html_e( 'Статус:', 'sd' ); ?></strong>
				<?php echo $configured ? esc_html__( 'Настроено', 'sd' ) : esc_html__( 'Не настроено', 'sd' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'sd_connection' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="sd_endpoint"><?php esc_html_e( 'Адрес приёма', 'sd' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( SD_Settings::OPT_ENDPOINT ); ?>" id="sd_endpoint"
								type="url" class="regular-text code"
								value="<?php echo esc_attr( SD_Settings::endpoint() ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sd_site_id"><?php esc_html_e( 'Идентификатор сайта', 'sd' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( SD_Settings::OPT_SITE_ID ); ?>" id="sd_site_id"
								type="text" class="regular-text code"
								value="<?php echo esc_attr( SD_Settings::site_id() ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sd_secret"><?php esc_html_e( 'Секретный ключ', 'sd' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( SD_Settings::OPT_SECRET ); ?>" id="sd_secret"
								type="password" class="regular-text code" autocomplete="new-password"
								placeholder="<?php echo $has_secret ? '••••••••' : ''; ?>" value="" />
							<p class="description">
								<?php esc_html_e( 'Ключ показывается один раз при выдаче в панели. Оставьте поле пустым, чтобы не менять сохранённый ключ.', 'sd' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sd_test_connection" />
				<?php wp_nonce_field( 'sd_test_connection' ); ?>
				<?php submit_button( __( 'Проверить соединение', 'sd' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Обробник «Проверить соединение» — нейтральні тости.
	 */
	public static function handle_test_connection() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'sd' ) );
		}
		check_admin_referer( 'sd_test_connection' );

		$notice = 'error';
		if ( SD_Settings::is_configured() ) {
			$result = SD_Heartbeat::send();
			$notice = $result['ok'] ? 'success' : 'error';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => self::SLUG,
					'sd_test'      => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
