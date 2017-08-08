<?php

class WidgetChart {
	private $file;
	private $version = '1.5.0';
	private $common_field;

	function __construct( $file ) {
		$this->file         = $file;
		$this->common_field = 'wp_no_external_links_dashboard_chart';
		$this->capabilities_router();
		add_action( 'admin_enqueue_scripts', array( $this, 'add_css_js' ) );
		$this->ajax_hooks( $this->common_field, 'ajax_callback', false );
	}


	public function capabilities_router() {
		if ( current_user_can( 'manage_categories' ) ) {
			add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
		}
	}

	public function dashboard_widget_method( $post, $callback_args ) {
		$this->widget_content();
	}

	public function add_dashboard_widgets() {
		wp_add_dashboard_widget( $this->common_field, 'Популярные внешние ссылки', array( $this, 'dashboard_widget_method' ) );
	}

	public function widget_content( $m = 0, $echo = true ) {

		if($m == 'week'){
			$date = new DateTime(current_time('mysql'));
			$date = $date->modify('-1 week');
			$date = $date->format('Y-m-d').' 00:00:00';
        }else{
			$date = $m . '-01 00:00:00';
        }

		if ( $m == '0' ) {
			$date = false;
			$info = $this->sql_query( $date );
		} else {
			$info = $this->sql_query( $date );
		}
		ob_start();
		?>
        <form type="post" action="<?php echo admin_url( '/admin-ajax.php?action=' . $this->common_field ); ?>" class="dashboard__statistics-chart-filter">
			<?php $this->select_months( $m ); ?>
            <button class="button">Проверить</button>
        </form>
        <div class="dashboard__statistics-chart">


            <div class="dashboard__statistics-chart-table-header">
                <div class="dashboard__statistics-chart-table-header-item">
                    <div class="dashboard__statistics-chart-td-number">
                        #
                    </div>
                    <div class="dashboard__statistics-chart-td-link">
                        Ссылка
                    </div>
                    <div class="dashboard__statistics-chart-td-total">
                        Переходов
                    </div>
                </div>
            </div>

            <div class="dashboard__statistics-chart-table-body">
				<?php
                $i=0;
                foreach ( $info as $val ):
	                $i++;
                ?>
                    <div class="dashboard__statistics-chart-table-body-item">
                        <div class="dashboard__statistics-chart-td-number">
                            <?php echo $i;?>
                        </div>
                        <div class="dashboard__statistics-chart-td-link">
                            <a href="<?php echo $val->url; ?>" target="_blank"><?php echo $val->url; ?></a>
                        </div>
                        <div class="dashboard__statistics-chart-td-total">
							<?php echo $val->total; ?>
                        </div>
                    </div>
				<?php endforeach; ?>
            </div>

        </div>
		<?php
		$out = ob_get_contents();
		ob_end_clean();

		if ( $echo ) {
			echo $out;
		} else {
			return $out;
		}
	}

	public function add_css_js() {
		wp_register_style( $this->common_field . '_css', plugin_dir_url( $this->file ) . 'public/css/chart-style.css', array(), $this->version );
		wp_enqueue_style( $this->common_field . '_css' );
		wp_register_script(
			$this->common_field . '_js',
			plugin_dir_url( $this->file ) . 'public/js/chart.js',
			array(
				'jquery',
			),
			$this->version,
			true
		);
		wp_enqueue_script( $this->common_field . '_js' );
	}

	public function ajax_hooks( $field, $cb, $capabilities = true ) {
		add_action( 'wp_ajax_' . $field, array( $this, $cb ) );
		if ( $capabilities ) {
			add_action( 'wp_ajax_nopriv_' . $field, array( $this, $cb ) );
		}
	}

	public function ajax_callback() {
		$request = $_REQUEST;
		unset( $request['action'] );
		$request = array_map( 'trim', $request );
		$json    = '';
		if ( array_key_exists( 'm', $request ) ) {
			$json['html'] = $this->widget_content( $request['m'], false );
		}
		wp_send_json( $json );
	}


	public function sql_query( $date = false, $limit = 50 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'links_stats';
		if ( $date !== false ) {
			$out_sql = $wpdb->get_results( "
			SELECT `url` , COUNT(*)  
			AS total 
			FROM `{$table}` 
			WHERE `date`
			BETWEEN STR_TO_DATE('{$date}' , '%Y-%m-%d %H:%i:%s') 
            AND STR_TO_DATE('{$this->sql_query_date_helper($date)}', '%Y-%m-%d %H:%i:%s')
			GROUP BY `url` 
			ORDER BY total 
			DESC
        " );
		} else {
			$out_sql = $wpdb->get_results( "
			SELECT `url` , COUNT(*)  
			AS total 
			FROM `{$table}` 
			GROUP BY `url` 
			ORDER BY total 
			DESC
			 LIMIT {$limit}
			" );
		}

		return $out_sql;
	}

	private function sql_query_date_helper( $str ) {
		$timestamp = strtotime( $str );
		$date      = new DateTime();
		$date      = $date->setDate( date( 'Y', $timestamp ), date( 'n', $timestamp ), 1 );
		$date->modify( '+1 month' );

		return $date->format( 'Y-m-d H:i:s' );
	}

	private function select_months( $m = 0 ) {
		global $wpdb, $wp_locale;

		$months = $wpdb->get_results( "
          SELECT DISTINCT YEAR( `date` ) AS year,
           MONTH( `date` ) AS month 
           FROM {$wpdb->prefix}links_stats 
           ORDER BY `date` DESC
		" );

		$month_count = count( $months );
		if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
			return;
		}
		?>
        <label for="filter-by-date" class="screen-reader-text"><?php _e( 'Filter by date' ); ?></label>
        <select name="m" id="filter-by-date">
            <option<?php selected( $m, 'week' ); ?> value="week"><?php _e( 'Лучшие за неделю' ); ?></option>
            <option<?php selected( $m, 0 ); ?> value="0"><?php _e( 'All dates' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 == $arc_row->year ) {
					continue;
				}

				$month = zeroise( $arc_row->month, 2 );
				$year  = $arc_row->year;

				printf( "<option %s value='%s'>%s</option>\n",
					selected( $m, $arc_row->year . '-' . $month, false ),
					esc_attr( $arc_row->year . '-' . $month ),
					sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
				);
			}
			?>
        </select>
		<?php
	}

	public function replace_url( $url ) {
		return str_replace( get_site_url( get_current_blog_id(), get_option( 'wp_noexternallinks' )['LINK_SEP'] ), '', $url );
	}

}