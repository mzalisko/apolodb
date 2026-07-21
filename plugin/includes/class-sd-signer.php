<?php
/**
 * HMAC-підписувач запитів.
 *
 * Дзеркало `contracts/ingest-contract.md` §1 — канонічний рядок і бекенд
 * ПОВИННІ збігатися байт-у-байт (Конституція, Принцип I).
 *
 * @package SiteData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Клас SD_Signer — будує канонічний рядок і HMAC-SHA256 підпис.
 * Плагін ТІЛЬКИ підписує; перевірку робить бекенд (constant-time).
 */
class SD_Signer {

	/**
	 * Згенерувати одноразовий nonce (128-біт CSPRNG, base64url).
	 *
	 * @return string
	 */
	public static function nonce() {
		return self::base64url( random_bytes( 16 ) );
	}

	/**
	 * Канонічний рядок (contract §1.2): 7 полів, з'єднаних LF, без кінцевого LF.
	 *
	 *   v1 \n UPPER(METHOD) \n path \n LOWERHEX(SHA-256(raw_body)) \n site-id \n timestamp \n nonce
	 *
	 * Тіло прив'язується через SHA-256-дайджест (стійкість до delimiter-injection).
	 *
	 * @param string $method    HTTP-метод (напр. POST).
	 * @param string $path      Логічний шлях, що підписується (SD_HEARTBEAT_PATH).
	 * @param string $raw_body  Точні байти тіла, що надсилаються.
	 * @param string $site_id   Публічний site-ідентифікатор.
	 * @param int    $timestamp Unix epoch секунди (UTC).
	 * @param string $nonce     Одноразовий nonce.
	 * @return string
	 */
	public static function canonical( $method, $path, $raw_body, $site_id, $timestamp, $nonce ) {
		$body_digest = hash( 'sha256', $raw_body ); // lowercase hex
		return implode(
			"\n",
			array(
				SD_SIG_VERSION,
				strtoupper( $method ),
				$path,
				$body_digest,
				$site_id,
				(string) $timestamp,
				$nonce,
			)
		);
	}

	/**
	 * Значення заголовка X-DB-Signature: "sha256=" + lowercase-hex(HMAC-SHA256).
	 *
	 * @param string $canonical Канонічний рядок.
	 * @param string $secret    Секретний ключ підпису (HMAC-секрет).
	 * @return string
	 */
	public static function signature( $canonical, $secret ) {
		return 'sha256=' . hash_hmac( 'sha256', $canonical, $secret );
	}

	/**
	 * base64url без padding.
	 *
	 * @param string $bin Двійкові дані.
	 * @return string
	 */
	private static function base64url( $bin ) {
		return rtrim( strtr( base64_encode( $bin ), '+/', '-_' ), '=' );
	}
}
