<?php
/**
 * RN Pagination widget.
 *
 * Main query  → real links (/page/2/, keeps ?s= / filters / sort), optional AJAX swap.
 * Listing Grid → AJAX through JetEngine's own signed "load more" request.
 * Posts / Loop Grid → AJAX fetch of ?e-page-{id}=N and swap of that widget.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

class RNP_Widget extends Widget_Base {

	public function get_name() {
		return 'rn_universal_pagination';
	}

	public function get_title() {
		return 'RN Pagination';
	}

	public function get_icon() {
		return 'eicon-post-navigation';
	}

	public function get_categories() {
		return array( 'rn-widgets' );
	}

	public function get_keywords() {
		return array( 'pagination', 'pager', 'page', 'jetengine', 'listing', 'posts', 'loop' );
	}

	public function get_style_depends() {
		return array( 'rn-pagination' );
	}

	public function get_script_depends() {
		return array( 'rn-pagination' );
	}

	protected function register_controls() {

		/* ---------------- Content: Query ---------------- */
		$this->start_controls_section( 'section_query', array( 'label' => 'Paginate what?' ) );

		$this->add_control(
			'source',
			array(
				'label'   => 'Source',
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'      => 'Auto detect',
					'main'      => 'Page / archive query (blog, shop, search)',
					'jet'       => 'JetEngine Listing Grid',
					'elementor' => 'Elementor Posts / Loop Grid',
				),
			)
		);

		$this->add_control(
			'target_id',
			array(
				'label'       => 'Grid CSS ID',
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'e.g. my-grid',
				'description' => 'Set the same ID in the grid widget → Advanced → CSS ID. Leave empty to use the first grid on the page.',
				'condition'   => array( 'source!' => 'main' ),
				'ai'          => array( 'active' => false ),
			)
		);

		$this->add_control(
			'elementor_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'In the Posts / Loop Grid widget set <b>Pagination</b> to <b>Numbers</b> (any type). Its own pagination is hidden automatically and this widget takes over.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array( 'source' => 'elementor' ),
			)
		);

		$this->add_control(
			'main_ajax',
			array(
				'label'        => 'Load pages without reload',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'condition'    => array( 'source' => array( 'main', 'auto' ) ),
			)
		);

		$this->add_control(
			'content_selector',
			array(
				'label'       => 'Content area selector',
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '.elementor-widget-archive-posts',
				'description' => 'The element that holds the posts — it is replaced with the next page\'s version.',
				'condition'   => array( 'main_ajax' => 'yes', 'source' => array( 'main', 'auto' ) ),
				'ai'          => array( 'active' => false ),
			)
		);

		$this->end_controls_section();

		/* ---------------- Content: Layout ---------------- */
		$this->start_controls_section( 'section_layout', array( 'label' => 'Layout' ) );

		$this->add_control(
			'type',
			array(
				'label'   => 'Type',
				'type'    => Controls_Manager::SELECT,
				'default' => 'numbers_prev_next',
				'options' => array(
					'numbers_prev_next' => 'Numbers + Previous / Next',
					'numbers'           => 'Numbers only',
					'prev_next'         => 'Previous / Next only',
				),
			)
		);

		$this->add_control( 'prev_label', array( 'label' => 'Previous label', 'type' => Controls_Manager::TEXT, 'default' => 'Previous', 'condition' => array( 'type!' => 'numbers' ) ) );
		$this->add_control( 'next_label', array( 'label' => 'Next label', 'type' => Controls_Manager::TEXT, 'default' => 'Next', 'condition' => array( 'type!' => 'numbers' ) ) );

		$this->add_control(
			'show_arrows',
			array(
				'label'        => 'Arrows',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'type!' => 'numbers' ),
			)
		);

		$this->add_control(
			'disabled_mode',
			array(
				'label'     => 'On first / last page',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'disable',
				'options'   => array(
					'disable' => 'Show Previous / Next greyed out',
					'hide'    => 'Hide Previous / Next',
				),
				'condition' => array( 'type!' => 'numbers' ),
			)
		);

		$this->add_control(
			'end_size',
			array(
				'label'       => 'Pages at the edges',
				'type'        => Controls_Manager::NUMBER,
				'default'     => 1,
				'min'         => 1,
				'max'         => 5,
				'condition'   => array( 'type!' => 'prev_next' ),
			)
		);

		$this->add_control(
			'mid_size',
			array(
				'label'       => 'Pages around the current',
				'type'        => Controls_Manager::NUMBER,
				'default'     => 1,
				'min'         => 0,
				'max'         => 5,
				'description' => '1 = 1 … 4 [5] 6 … 20',
				'condition'   => array( 'type!' => 'prev_next' ),
			)
		);

		$this->add_control(
			'show_info',
			array(
				'label'        => 'Show "Page X of Y"',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'info_text',
			array(
				'label'     => 'Info text',
				'type'      => Controls_Manager::TEXT,
				'default'   => 'Page {current} of {total}',
				'condition' => array( 'show_info' => 'yes' ),
			)
		);

		$this->add_control(
			'hide_single',
			array(
				'label'        => 'Hide when there is only one page',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'scroll_top',
			array(
				'label'        => 'Scroll to the grid after page change',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'scroll_offset',
			array(
				'label'     => 'Scroll offset (px)',
				'type'      => Controls_Manager::NUMBER,
				'default'   => 100,
				'condition' => array( 'scroll_top' => 'yes' ),
			)
		);

		$this->add_control(
			'url_sync',
			array(
				'label'        => 'Keep page number in the URL',
				'description'  => 'So refresh / back button / shared links open the same page (AJAX sources).',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => 'Alignment',
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => array(
					'flex-start'    => array( 'title' => 'Left', 'icon' => 'eicon-text-align-left' ),
					'center'        => array( 'title' => 'Center', 'icon' => 'eicon-text-align-center' ),
					'flex-end'      => array( 'title' => 'Right', 'icon' => 'eicon-text-align-right' ),
					'space-between' => array( 'title' => 'Justified', 'icon' => 'eicon-text-align-justify' ),
				),
				'selectors' => array( '{{WRAPPER}} .rnp__list' => 'justify-content: {{VALUE}};', '{{WRAPPER}} .rnp' => 'align-items: {{VALUE}};' ),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	private function register_style_controls() {

		/* ---------------- Style: General ---------------- */
		$this->start_controls_section( 'style_general', array( 'label' => 'General', 'tab' => Controls_Manager::TAB_STYLE ) );

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => 'Space between items',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'size' => 8, 'unit' => 'px' ),
				'selectors'  => array( '{{WRAPPER}} .rnp__list' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo', 'selector' => '{{WRAPPER}} .rnp__item' ) );

		$this->add_control(
			'loading_opacity',
			array(
				'label'     => 'Grid opacity while loading',
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ) ),
				'default'   => array( 'size' => 0.5 ),
				'selectors' => array( '{{WRAPPER}} .rnp' => '--rnp-loading-opacity: {{SIZE}};' ),
			)
		);

		$this->end_controls_section();

		/* ---------------- Style: Numbers ---------------- */
		$this->start_controls_section(
			'style_numbers',
			array(
				'label'     => 'Page numbers',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'type!' => 'prev_next' ),
			)
		);

		$this->add_responsive_control(
			'num_size',
			array(
				'label'      => 'Size',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 20, 'max' => 80 ) ),
				'default'    => array( 'size' => 40, 'unit' => 'px' ),
				'selectors'  => array( '{{WRAPPER}} .rnp__num' => 'min-width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'num_radius',
			array(
				'label'      => 'Border radius',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array( 'top' => 50, 'right' => 50, 'bottom' => 50, 'left' => 50, 'unit' => '%', 'isLinked' => true ),
				'selectors'  => array( '{{WRAPPER}} .rnp__num' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'num_border', 'selector' => '{{WRAPPER}} .rnp__num' ) );

		$this->start_controls_tabs( 'num_tabs' );
		foreach ( array(
			'normal' => array( 'Normal', '{{WRAPPER}} .rnp__num', '#1f2937', '' ),
			'hover'  => array( 'Hover', '{{WRAPPER}} a.rnp__num:hover', '#1f2937', '#f3f4f6' ),
			'active' => array( 'Active', '{{WRAPPER}} .rnp__num.is-current', '#ffffff', '#F5A623' ),
		) as $key => $tab ) {
			$this->start_controls_tab( 'num_tab_' . $key, array( 'label' => $tab[0] ) );
			$this->add_control( 'num_color_' . $key, array( 'label' => 'Text color', 'type' => Controls_Manager::COLOR, 'default' => $tab[2], 'selectors' => array( $tab[1] => 'color: {{VALUE}};' ) ) );
			$this->add_control( 'num_bg_' . $key, array( 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'default' => $tab[3], 'selectors' => array( $tab[1] => 'background-color: {{VALUE}};' ) ) );
			$this->add_control( 'num_bc_' . $key, array( 'label' => 'Border color', 'type' => Controls_Manager::COLOR, 'selectors' => array( $tab[1] => 'border-color: {{VALUE}};' ) ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();

		$this->add_control( 'dots_color', array( 'label' => 'Dots (…) color', 'type' => Controls_Manager::COLOR, 'default' => '#9ca3af', 'separator' => 'before', 'selectors' => array( '{{WRAPPER}} .rnp__dots' => 'color: {{VALUE}};' ) ) );

		$this->end_controls_section();

		/* ---------------- Style: Prev / Next ---------------- */
		$this->start_controls_section(
			'style_prevnext',
			array(
				'label'     => 'Previous / Next',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'type!' => 'numbers' ),
			)
		);

		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'pn_typo', 'selector' => '{{WRAPPER}} .rnp__pn' ) );

		$this->add_responsive_control(
			'pn_padding',
			array(
				'label'      => 'Padding',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'top' => 10, 'right' => 20, 'bottom' => 10, 'left' => 20, 'unit' => 'px', 'isLinked' => false ),
				'selectors'  => array( '{{WRAPPER}} .rnp__pn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'pn_radius',
			array(
				'label'      => 'Border radius',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array( 'top' => 999, 'right' => 999, 'bottom' => 999, 'left' => 999, 'unit' => 'px', 'isLinked' => true ),
				'selectors'  => array( '{{WRAPPER}} .rnp__pn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'           => 'pn_border',
				'selector'       => '{{WRAPPER}} .rnp__pn',
				'fields_options' => array(
					'border' => array( 'default' => 'solid' ),
					'width'  => array( 'default' => array( 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1, 'isLinked' => true ) ),
					'color'  => array( 'default' => '#e5e7eb' ),
				),
			)
		);

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'pn_shadow', 'selector' => '{{WRAPPER}} .rnp__pn' ) );

		$this->start_controls_tabs( 'pn_tabs' );
		foreach ( array(
			'normal' => array( 'Normal', '{{WRAPPER}} .rnp__pn', '#1f2937', '#ffffff' ),
			'hover'  => array( 'Hover', '{{WRAPPER}} a.rnp__pn:hover', '#ffffff', '#F5A623' ),
		) as $key => $tab ) {
			$this->start_controls_tab( 'pn_tab_' . $key, array( 'label' => $tab[0] ) );
			$this->add_control( 'pn_color_' . $key, array( 'label' => 'Text color', 'type' => Controls_Manager::COLOR, 'default' => $tab[2], 'selectors' => array( $tab[1] => 'color: {{VALUE}};' ) ) );
			$this->add_control( 'pn_bg_' . $key, array( 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'default' => $tab[3], 'selectors' => array( $tab[1] => 'background-color: {{VALUE}};' ) ) );
			$this->add_control( 'pn_bc_' . $key, array( 'label' => 'Border color', 'type' => Controls_Manager::COLOR, 'selectors' => array( $tab[1] => 'border-color: {{VALUE}};' ) ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pn_icon_size',
			array(
				'label'      => 'Arrow size',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 30 ) ),
				'default'    => array( 'size' => 14, 'unit' => 'px' ),
				'separator'  => 'before',
				'selectors'  => array( '{{WRAPPER}} .rnp__arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'disabled_opacity',
			array(
				'label'     => 'Disabled opacity',
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ) ),
				'default'   => array( 'size' => 0.4 ),
				'selectors' => array( '{{WRAPPER}} .rnp__pn.is-disabled' => 'opacity: {{SIZE}};' ),
			)
		);

		$this->end_controls_section();

		/* ---------------- Style: Info ---------------- */
		$this->start_controls_section(
			'style_info',
			array(
				'label'     => '"Page X of Y" text',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_info' => 'yes' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'info_typo', 'selector' => '{{WRAPPER}} .rnp__info' ) );
		$this->add_control( 'info_color', array( 'label' => 'Color', 'type' => Controls_Manager::COLOR, 'default' => '#6b7280', 'selectors' => array( '{{WRAPPER}} .rnp__info' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                             */
	/* ------------------------------------------------------------------ */

	/** Main-query paging data, or null when this page has no paginated main query. */
	private function main_query_data() {
		global $wp_query;
		if ( ! $wp_query instanceof WP_Query || is_singular() ) {
			return null;
		}
		$total = (int) $wp_query->max_num_pages;
		return array(
			'current' => max( 1, (int) get_query_var( 'paged' ) ),
			'total'   => max( 1, $total ),
		);
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$source  = $s['source'];
		$target  = trim( (string) ( $s['target_id'] ?? '' ), "# \t" );
		$is_edit = \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode();

		// Auto: a target ID or no archive query → let JS find the grid.
		$main = null;
		if ( 'main' === $source || ( 'auto' === $source && '' === $target ) ) {
			$main = $this->main_query_data();
		}
		$mode = $main && ( 'main' === $source || $main['total'] > 1 ) ? 'main' : ( 'auto' === $source ? 'auto' : $source );

		$config = array(
			'mode'         => $mode,
			'target'       => $target,
			'type'         => $s['type'],
			'prev'         => (string) $s['prev_label'],
			'next'         => (string) $s['next_label'],
			'arrows'       => 'yes' === $s['show_arrows'],
			'disabled'     => $s['disabled_mode'],
			'endSize'      => max( 1, (int) $s['end_size'] ),
			'midSize'      => max( 0, (int) $s['mid_size'] ),
			'info'         => 'yes' === $s['show_info'] ? (string) $s['info_text'] : '',
			'hideSingle'   => 'yes' === $s['hide_single'],
			'scroll'       => 'yes' === $s['scroll_top'],
			'scrollOffset' => (int) $s['scroll_offset'],
			'urlSync'      => 'yes' === $s['url_sync'],
			'ajax'         => 'main' === $mode && 'yes' === $s['main_ajax'] && ! empty( $s['content_selector'] ),
			'content'      => (string) ( $s['content_selector'] ?? '' ),
			'edit'         => $is_edit,
		);

		printf(
			'<nav class="rnp" aria-label="%s" data-rnp="%s">',
			esc_attr__( 'Pagination', 'rn-pagination' ),
			esc_attr( wp_json_encode( $config ) )
		);

		if ( 'main' === $mode ) {
			$this->render_items( $config, $main['current'], $main['total'], 'get_pagenum_link' );
		} elseif ( $is_edit ) {
			// Editor preview with sample numbers; the live page renders the real ones.
			$this->render_items( $config, 1, 10, function () {
				return '#';
			} );
		}

		echo '</nav>';
	}

	/** PHP twin of the JS renderer (see assets/rn-pagination.js → build()). */
	private function render_items( $c, $current, $total, $link ) {
		if ( $total <= 1 && $c['hideSingle'] ) {
			return;
		}

		$arrow = function ( $dir ) use ( $c ) {
			if ( ! $c['arrows'] ) {
				return '';
			}
			$d = 'prev' === $dir ? 'M15 18l-6-6 6-6' : 'M9 18l6-6-6-6';
			return '<svg class="rnp__arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		};

		$pn = function ( $dir, $page, $enabled ) use ( $c, $link, $arrow ) {
			if ( ! $enabled && 'hide' === $c['disabled'] ) {
				return '';
			}
			$label = esc_html( 'prev' === $dir ? $c['prev'] : $c['next'] );
			$inner = 'prev' === $dir ? $arrow( 'prev' ) . '<span>' . $label . '</span>' : '<span>' . $label . '</span>' . $arrow( 'next' );
			$cls   = 'rnp__item rnp__pn rnp__' . $dir;
			if ( ! $enabled ) {
				return '<li><span class="' . $cls . ' is-disabled" aria-disabled="true">' . $inner . '</span></li>';
			}
			return sprintf( '<li><a class="%s" href="%s" data-page="%d" rel="%s">%s</a></li>', $cls, esc_url( $link( $page ) ), $page, 'prev' === $dir ? 'prev' : 'next', $inner );
		};

		echo '<ul class="rnp__list">';
		if ( 'numbers' !== $c['type'] ) {
			echo $pn( 'prev', $current - 1, $current > 1 ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( 'prev_next' !== $c['type'] ) {
			foreach ( RNP_Plugin::window( $current, $total, $c['endSize'], $c['midSize'] ) as $n ) {
				if ( 'dots' === $n ) {
					echo '<li><span class="rnp__item rnp__dots">&hellip;</span></li>';
				} elseif ( $n === $current ) {
					printf( '<li><span class="rnp__item rnp__num is-current" aria-current="page">%d</span></li>', $n );
				} else {
					printf( '<li><a class="rnp__item rnp__num" href="%s" data-page="%d">%d</a></li>', esc_url( $link( $n ) ), $n, $n );
				}
			}
		}
		if ( 'numbers' !== $c['type'] ) {
			echo $pn( 'next', $current + 1, $current < $total ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</ul>';

		if ( $c['info'] ) {
			echo '<div class="rnp__info">' . esc_html( strtr( $c['info'], array( '{current}' => $current, '{total}' => $total ) ) ) . '</div>';
		}
	}
}
