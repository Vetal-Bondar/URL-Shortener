<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class UrlShortener {

public function __construct() {
	//додаєм функцію запуску адмін меню
	add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		//шорткод
	add_shortcode( 'url_shortener', array( $this, 'render_shortcode' ) );
	//скрипт з формою
	add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		//обробка форми з AJAX
	add_action( 'wp_ajax_shorten_url', array( $this, 'shorten_url' ) );
	add_action( 'wp_ajax_nopriv_shorten_url', array( $this, 'shorten_url' ) );
	add_action( 'template_redirect', array( $this, 'redirection' ) );
}

	public static function activate() { //створення таблиці
		global $wpdb;
		$table_name = $wpdb->prefix . 'short_links';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			original_url text NOT NULL,
			short_code varchar(10) NOT NULL,
			clicks mediumint(9) DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function add_admin_menu() {
		add_menu_page( 'Всі посилання', 'URL Shortener', 'manage_options', 'url-shortener-page', array( $this, 'admin_page_html' ), 'dashicons-admin-links', 6 );
	}

	// адмінка
public function admin_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'short_links';

		//видалення
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['id'] ) ) {
			
			check_admin_referer( 'delete_link_' . $_GET['id'] ); //перевіряє чи був запит до адмінки надісланий з правильної сторінки
			$wpdb->delete( $table_name, array( 'id' => $_GET['id'] ) );
			echo '<div style="color: green; padding: 10px; border: 1px solid green; margin-bottom: 10px;">Успішно видалено.</div>';
		}
		// дістаєм список з БД 
		$results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		
		include plugin_dir_path( __FILE__ ) . 'views/admin-list.php';
	}

	//запускаєм шорткод
	public function render_shortcode() {
		ob_start();
		include plugin_dir_path( __FILE__ ) . 'views/form.php';
		return ob_get_clean();
	}

	public function enqueue_scripts() { //
		wp_enqueue_script( 'url-shortener-js', plugin_dir_url( __FILE__ ) . 'assets/script.js', array('jquery'), '1.0', true );
		wp_localize_script( 'url-shortener-js', 'url_shortener_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'url_shortener_nonce' )
		));
		wp_enqueue_style( 'url-shortener-css', plugin_dir_url( __FILE__ ) . 'assets/style.css' );
	}

//обробка запиту
	public function shorten_url() {

		check_ajax_referer( 'url_shortener_nonce', 'security' );

		$original_url = esc_url_raw( $_POST['url'] );
//валідація на правильність уведення посилання
		if ( empty( $original_url ) || ! filter_var( $original_url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( 'Перевірте правильність введення або додайте https://' );
		}

		// генеруєм код нового посилання
		$short_code = $this->generate_random_string( 5 );

		// записуєм все в БД
		global $wpdb;
		$table_name = $wpdb->prefix . 'short_links';

		$result = $wpdb->insert(
			$table_name,
			array(
				'original_url' => $original_url,
				'short_code'   => $short_code,
				'created_at'   => current_time( 'mysql' )),
			array( '%s', '%s', '%s' )
		);

		if ( $result ) {
			// якщо успішно то формуєм і повертаєм коротке посилання
			$short_url = site_url( '/' . $short_code );
			
			wp_send_json_success( $short_url ); //кажемо js що все ок щоб повернув коротке посилання
		} else {
			wp_send_json_error( 'Помилка.' );
		}
	}

	private function generate_random_string( $length = 5 ) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		return substr( str_shuffle( $characters ), 0, $length );
	}

	public function redirection() {
		$request_code = basename( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );

		global $wpdb;
		$table_name = $wpdb->prefix . 'short_links';
		$link = $wpdb->get_row( 
			$wpdb->prepare( "SELECT * FROM $table_name WHERE short_code = %s", $request_code ) 
		);

		if ( $link ) {
			// лічильник переходів за посиланням
			$wpdb->update( 
				$table_name, 
				array( 'clicks' => $link->clicks + 1 ),
				array( 'id' => $link->id ));

			wp_redirect( $link->original_url, 301 );
			exit;
		}
	}
}
register_activation_hook( __FILE__, array( 'UrlShortener', 'activate' ) );
new UrlShortener();