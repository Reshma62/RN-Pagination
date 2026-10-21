<?php
/**
 * Bootstrap: widget + assets + the small hooks that expose page data of
 * JetEngine Listing Grids and Elementor Posts / Loop Grids to the pager.
 */

defined( 'ABSPATH' ) || exit;

final class RNP_Plugin {

	public static function init() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widget' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( __CLASS__, 'register_assets' ) );

		// JetEngine only prints the (signed) query into the grid's data-nav when
		// "Load more" is on. The pager needs it to request other pages.
		add_filter( 'jet-engine/listing/grid/add-query-data', '__return_true' );

		// Elementor Posts / Loop Grid: expose current page + max pages.
		add_filter( 'elementor/widget/render_content', array( __CLASS__, 'elementor_posts_meta' ), 10, 2 );
	}

	public static function register_category( $manager ) {
		$cats = $manager->get_categories();
		if ( ! isset( $cats['rn-widgets'] ) ) {
			$manager->add_category( 'rn-widgets', array( 'title' => 'RN Widgets', 'icon' => 'fa fa-plug' ) );
		}
	}

	public static function register_widget( $widgets_manager ) {
		require_once RNP_DIR . '/widgets/class-rnp-widget.php';
		$widgets_manager->register( new RNP_Widget() );
	}

	public static function register_assets() {
		$css = RNP_VERSION . '.' . filemtime( RNP_DIR . '/assets/rn-pagination.css' );
		$js  = RNP_VERSION . '.' . filemtime( RNP_DIR . '/assets/rn-pagination.js' );
		wp_register_style( 'rn-pagination', RNP_URL . 'assets/rn-pagination.css', array(), $css );
		wp_register_script( 'rn-pagination', RNP_URL . 'assets/rn-pagination.js', array( 'jquery' ), $js, true );
	}

	/**
	 * Appends a hidden marker with paging data to Elementor Pro Posts /
	 * Archive Posts / Loop Grid output so the pager can read it.
	 */
	public static function elementor_posts_meta( $content, $widget ) {
		if ( ! in_array( $widget->get_name(), array( 'posts', 'archive-posts', 'loop-grid' ), true ) ) {
			return $content;
		}
		if ( ! method_exists( $widget, 'get_query' ) ) {
			return $content;
		}
		$query = $widget->get_query();
		if ( ! $query instanceof WP_Query ) {
			return $content;
		}

		$type       = (string) $widget->get_settings_for_display( 'pagination_type' );
		$current    = method_exists( $widget, 'get_current_page' ) ? (int) $widget->get_current_page() : 1;
		$page_limit = (int) $widget->get_settings_for_display( 'pagination_page_limit' );
		$pages      = (int) $query->max_num_pages;
		if ( $page_limit > 0 ) {
			$pages = min( $pages, $page_limit );
		}

		return $content . sprintf(
			'<span class="rnp-meta" hidden data-page="%d" data-pages="%d" data-widget-id="%s" data-native="%s"></span>',
			max( 1, $current ),
			max( 1, $pages ),
			esc_attr( $widget->get_id() ),
			'' === $type ? 'off' : 'on'
		);
	}

	/**
	 * Same window as WP core paginate_links(): 1 2 … 7 8 [9] 10 11 … 49 50
	 *
	 * @return array Page numbers and 'dots'.
	 */
	public static function window( $current, $total, $end_size, $mid_size ) {
		$out  = array();
		$dots = false;
		for ( $n = 1; $n <= $total; $n++ ) {
			if ( $n === $current
				|| $n <= $end_size
				|| $n > $total - $end_size
				|| ( $n >= $current - $mid_size && $n <= $current + $mid_size ) ) {
				$out[] = $n;
				$dots  = true;
			} elseif ( $dots ) {
				$out[] = 'dots';
				$dots  = false;
			}
		}
		return $out;
	}
}
