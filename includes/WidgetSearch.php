<?php

class WidgetSearch {
	private static $file;
	private static $version;

	private static $common_field;

	private function __clone() {
	}

	private function __construct() {
	}

	public static function run( $file ) {
		self::$file         = $file;
		self::$version      = '1.5.0';
		self::$common_field = 'wp_no_external_links_dashboard_statistics';
		self::capabilities_router();
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_css_js' ) );
		self::ajax_hooks( self::$common_field, 'ajax_callback', false );

	}

	public static function capabilities_router() {
		if ( current_user_can( 'manage_categories' ) ) {
			add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widgets' ) );
		}
	}

	public static function dashboard_widget_function( $post, $callback_args ) {
		self::widget_content();
	}

	public static function add_dashboard_widgets() {
		wp_add_dashboard_widget( self::$common_field, 'Статистика закрытых ссылок', array( __CLASS__, 'dashboard_widget_function' ) );
	}

	public static function widget_content() {
		?>
        <div class="dashboard__statistics">
            <div class="dashboard__statistics-fields">
                <div class="dashboard__statistics-field">
                    <div class="dashboard__statistics-header">
                        <form method="post"
                              action="<?php echo admin_url( 'admin-ajax.php?action=' . self::$common_field ); ?>">
                            <input class="dashboard__statistics-search-link" name="search-link" type="text"
                                   data-attr-action="<?php echo admin_url( 'admin-ajax.php?action=' . self::$common_field ); ?>"
                                   placeholder="Ссылка.."/>
                            <button class="button">Проверить</button>
                            <input class="button clear" value="Очистить"/>
                        </form>
                    </div>

                    <div class="dashboard__statistics-content"></div>
                </div>
            </div>
        </div>
		<?php
	}

	public static function add_css_js() {
		wp_register_style( self::$common_field . '_css', plugin_dir_url( self::$file ) . 'public/css/statistic-style.css', array(), self::$version );
		wp_enqueue_style( self::$common_field . '_css' );

		wp_register_script( self::$common_field . '_js', plugin_dir_url( self::$file ) . 'public/js/statistic-script.js', array(
			'jquery',
			'jquery-ui-autocomplete'
		), self::$version, true );
		wp_enqueue_script( self::$common_field . '_js' );
	}

	public static function ajax_hooks( $field, $cb, $capabilities = true ) {
		add_action( 'wp_ajax_' . $field, array( __CLASS__, $cb ) );
		if ( $capabilities ) {
			add_action( 'wp_ajax_nopriv_' . $field, array( __CLASS__, $cb ) );
		}
	}

	public static function ajax_callback() {
		$request      = $_REQUEST;
		$request      = array_map( 'trim', $request );
		$url          = false;
		$autocomplete = false;

		if ( empty( $request['term'] ) ) {
			$url = $request['search-link'] = self::replace_url( $request['search-link'] );
		} else {
			$autocomplete = true;
			$url          = $request['term'] = self::replace_url( $request['term'] );
		}

		if ( $url || $url = '' || $url = ' ' ) {
			$message = self::sql( $url, $autocomplete );
		} else {
			$message = false;
		}

		wp_send_json( $message );
	}

	public static function sql( $url, $autocomplete = false ) {
		global $wpdb;

		$url = esc_sql( $url );

		$table = $wpdb->prefix . 'links_stats';

		if ( $autocomplete ) {
			$out_sql = self::sql_query( $url, 'text' );
		} else {
			$out_sql = self::sql_query( $url, 'html' );
		}

		if ( is_array( $out_sql ) && count( $out_sql ) > 0 ) {

			if ( ! $autocomplete ) {
				$out = array(
					'items' => self::items_wrap_html( $out_sql )
				);
			} else {
				$out = $out_sql;
			}

		} else {
			$out = false;
		}

		return $out;
	}

	public static function sql_query( $url, $type = 'html' ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'links_stats';
		$out_sql = false;

		if ( $type == 'text' ) {
			$out_sql = $wpdb->get_col( "
            SELECT  url
            FROM $table
            WHERE url LIKE  '%$url%'
            GROUP BY url
            " );

		} elseif ( $type == 'html' ) {
			$out_sql = $wpdb->get_results( "
                SELECT  COUNT( * ) , `url`
                FROM `{$table}`
                WHERE `url` LIKE  '%{$url}%'
                GROUP BY `url`
                LIMIT 20
            ",
				ARRAY_N
			);

		}

		return $out_sql;
	}

	public static function items_wrap_html( $item ) {
		$res = '';
		if ( is_array( $item ) ) {
			foreach ( $item as $val ) {
				$res .= '<div class="dashboard__statistics-conten-item"><a href="' . $val[1] . '" target="_blank">' . $val[1] . '</a> <div class="dashboard__statistics-conten-item-number"> '.$val[0].'</div> </div>';
			}
		} else {
			$res .= '<div class="dashboard__statistics-conten-item"><a href="' . $item[1] . '" target="_blank">' . $item[1] . '</a> <div class="dashboard__statistics-conten-item-number"> '.$item[0].'</div> </div>';
		}

		return $res;
	}


	public static function replace_url( $url ) {
		return str_replace( get_site_url( get_current_blog_id(), get_option( 'wp_noexternallinks' )['LINK_SEP'] ), '', $url );
	}

}