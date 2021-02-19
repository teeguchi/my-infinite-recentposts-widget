<?php
/*
Plugin Name: My Infinite Recentposts Widget
Description: 最近の投稿を無限スクロールボタンで表示するウィジェット。
Version: 1.0
Author: TeeGuchi
Author URI: https://teeguchi.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: my-infinite-recentposts-widget
*/

/* Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Definition of constants.
 */
define( 'MIRCP_PLUGIN_URL', plugins_url( '/', __FILE__ ) );

/* Loading plugin scripts and styles.
 */
function my_infinite_recentposts_widget_scripts() {
	wp_register_script( 'my-infinite-recentposts-widget-script', MIRCP_PLUGIN_URL . 'mircp-widget.js', array( 'jquery' ), '1.0', true );
	wp_register_style( 'my-infinite-recentposts-widget-style', MIRCP_PLUGIN_URL . 'mircp-widget.css', array(), '1.0' );
	
	if ( is_active_widget( false, false, 'my_infinite_recentposts', true ) ) {
		wp_enqueue_script( 'my-infinite-recentposts-widget-script' ); 
		wp_enqueue_style( 'my-infinite-recentposts-widget-style' );
		wp_localize_script( 'my-infinite-recentposts-widget-script', 'mircp_widget_data', [
				'api' => admin_url( 'admin-ajax.php' ), // Ajax 送信先のパス
			]
		);
	}
}
add_action( 'wp_enqueue_scripts', 'my_infinite_recentposts_widget_scripts' );

/* HTML display of posts.
 */
function mircp_widget_render_html( $show_date ) {
	$render_html = '';
	ob_start(); ?>
<li>
	<a href="<?php the_permalink(); ?>" class="mircp-title"><?php the_title(); ?></a>
	<?php
		if ( $show_date ) : ?>
			<span class="post-date mircp-time"><?php echo get_post_time( 'd/M/Y' ); ?></span>
	<?php
		endif; ?>
</li>
<?php
	$render_html = ob_get_clean();
	echo $render_html;
}

/* Ajax handler settings.
 */
function view_my_recentposts() {
	$posts_num = absint( $_POST['number'] ); // 1ページに含める投稿数
	$page_count = absint( $_POST['count'] ); // ページ番号
	$show_date = absint( $_POST['showdate'] ); // 日付の表示
	
	$query_args = array(
		'posts_per_page'      => $posts_num, 
		'post_type'           => 'post',
		'paged'               => $page_count,
		'ignore_sticky_posts' => true,
		'post_status'         => array( 'publish' )
	);
	$vmr_query = new WP_Query( $query_args ); // view_my_recentposts のクエリ
	
	if ( $vmr_query->have_posts() ) {
		while ( $vmr_query->have_posts() ) {
			$vmr_query->the_post();
			mircp_widget_render_html( $show_date );
		}
		wp_reset_postdata();
	}
    exit();
}
add_action( 'wp_ajax_view_my_recentposts', 'view_my_recentposts' );
add_action( 'wp_ajax_nopriv_view_my_recentposts', 'view_my_recentposts' );

/* Widget settings.
 */
class My_Infinite_Recentposts_Widget extends WP_Widget {
	/* Sets up a new widget instance. */
	public function __construct() {
		parent::__construct(
			'my_infinite_recentposts', // IDのベース名
			'無限の最近の投稿', // ウィジェットの名前
			array(
				'classname'   => 'mircp', // ウィジェットのクラス名
				'description' => '投稿記事を無限ボタンで表示します。'
			)
		);
	}
	
	/* Widget display settings.
	 * 
	 * widget()
	 * @param array $args ウィジェットの引数
	 * @param array $instance データベースの保存値
	 */
	public function widget( $args, $instance ) {
		$default_title = '無限の投稿';
		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : $default_title;
		$posts_num = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 3; // 1ページに含める投稿数
		$show_date = isset( $instance['show_date'] ) ? (int) $instance['show_date'] : 0; // 日付の表示
		
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		
		$query_args = array(
			'posts_per_page'      => $posts_num, 
			'post_type'           => 'post',
			'paged'               => 1, // ページ番号
			'ignore_sticky_posts' => true // 先頭固定表示の投稿を無視
		);
		$mircp_query = new WP_Query( $query_args ); // クエリ
		$max_pages = $mircp_query->max_num_pages; // ページの合計数
			
		if ( $mircp_query->have_posts() ) : ?>
			<ul class="mircp-posts" data-mircp-val="<?php echo esc_attr( $posts_num ) . '_' . esc_attr( $max_pages ) . '_' . esc_attr( $show_date ) . '_1'; ?>">
		<?php 
			while ( $mircp_query->have_posts() ) {
				$mircp_query->the_post();
				mircp_widget_render_html( $show_date );
			}
			wp_reset_postdata(); ?>
			</ul>
			<p class="mircp-last">これ以上記事はありません。</p>
			<div class="mircp-btn">
				<div class="mircp-loading">
					<div class="spinner">
						<div class="bounce1"></div>
						<div class="bounce2"></div>
						<div class="bounce3"></div>
					</div>
				</div>
				<a><span>もっと &gt;</span></a>
			</div>
		<!-- / mircp-widget -->
	<?php
		else : ?>
			<p>投稿記事はありません。</p>
	<?php
		endif;
		echo $args['after_widget'];
	}
	
	/* Widget form settings.
	 *
	 * form()
	 * @param array $instance データベースからの前回保存された値
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$posts_num = isset( $instance['number'] ) ? absint( $instance['number'] ) : 3;
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false; ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">タイトル：</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>">表示する投稿数：</label>
			<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $posts_num ); ?>" size="3">
		</p>
		<p>
			<input class="checkbox" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" type="checkbox"<?php checked( $show_date ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_date' ); ?>">投稿日を表示しますか？</label>
		</p>
	<?php
	}
	
	/* Processing updating the widget options.
	 *
	 * update()
	 * @param array $new_instance 保存するために送信されたばかりの値
	 * @param array $old_instance 以前にデータベースから保存された値
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		
		return $instance;
	}
}

/* Register the widget.
 */
function my_infinite_recentposts_class() {
	register_widget( 'My_Infinite_Recentposts_Widget' ); // クラス名を指定
}
add_action( 'widgets_init', 'my_infinite_recentposts_class' );
