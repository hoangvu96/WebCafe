<?php
namespace CafeCore\Language;

use CafeCore\SettingsPage;

defined( 'ABSPATH' ) || exit;

/**
 * Chỗ nhập bản tiếng Anh trong wp-admin: hộp "Bản tiếng Anh" ở trang, ô tên tiếng Anh ở
 * danh mục/thuộc tính, ô nhãn tiếng Anh ở mục menu, bảng cụm từ ở WooCommerce → Cài đặt Cafe.
 */
final class ContentAdmin {
	private const CREATE_ACTION = 'cafe_create_en_page';

	public static function register(): void {
		add_action( 'add_meta_boxes_page', array( self::class, 'add_page_box' ) );
		add_action( 'admin_post_' . self::CREATE_ACTION, array( self::class, 'create_en_page' ) );

		add_action( 'admin_init', array( self::class, 'register_term_fields' ) );
		add_action( 'created_term', array( self::class, 'save_term' ), 10, 3 );
		add_action( 'edited_term', array( self::class, 'save_term' ), 10, 3 );

		add_action( 'wp_nav_menu_item_custom_fields', array( self::class, 'menu_item_field' ), 10, 2 );
		add_action( 'wp_update_nav_menu_item', array( self::class, 'save_menu_item' ), 10, 2 );

		add_action( 'admin_init', array( self::class, 'register_phrases_setting' ) );
		add_action( 'cafe_core_settings_after_fields', array( self::class, 'phrases_field' ) );
	}

	// ─── Trang ─────────────────────────────────────────────────────────────────

	public static function add_page_box( \WP_Post $post ): void {
		add_meta_box( 'cafe-en-page', __( 'Bản tiếng Anh', 'cafe-core' ), array( self::class, 'render_page_box' ), 'page', 'side' );
	}

	public static function render_page_box( \WP_Post $post ): void {
		$source_id = (int) get_post_meta( $post->ID, SOURCE_META, true );
		if ( $source_id ) {
			printf(
				'<p>%s</p>',
				sprintf(
					/* translators: %s: link tới trang tiếng Việt gốc */
					esc_html__( 'Đây là bản tiếng Anh của trang %s. Để trạng thái Riêng tư: trang này chỉ hiển thị thay cho trang gốc khi khách chọn English.', 'cafe-core' ),
					'<a href="' . esc_url( (string) get_edit_post_link( $source_id ) ) . '">' . esc_html( get_the_title( $source_id ) ) . '</a>'
				)
			);
			return;
		}

		$en_id = (int) get_post_meta( $post->ID, PAGE_META, true );
		if ( $en_id && get_post( $en_id ) ) {
			printf(
				'<p><a class="button" href="%s">%s</a></p><p class="description">%s</p>',
				esc_url( (string) get_edit_post_link( $en_id ) ),
				esc_html__( 'Sửa bản tiếng Anh', 'cafe-core' ),
				esc_html__( 'Bản tiếng Anh ở trạng thái Bản nháp sẽ chưa được hiển thị.', 'cafe-core' )
			);
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'Lưu trang trước khi tạo bản tiếng Anh.', 'cafe-core' ) . '</p>';
			return;
		}
		printf(
			'<p><a class="button" href="%s">%s</a></p><p class="description">%s</p>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::CREATE_ACTION . '&post=' . $post->ID ), self::CREATE_ACTION . '_' . $post->ID ) ),
			esc_html__( 'Tạo bản tiếng Anh', 'cafe-core' ),
			esc_html__( 'Tạo bản sao nội dung trang này để dịch sang tiếng Anh.', 'cafe-core' )
		);
	}

	public static function create_en_page(): void {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( self::CREATE_ACTION . '_' . $post_id );
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			wp_die( esc_html__( 'Bạn không có quyền sửa trang này.', 'cafe-core' ), 403 );
		}
		$en_id = self::ensure_en_page( $post_id );
		if ( ! $en_id ) {
			wp_die( esc_html__( 'Không tạo được bản tiếng Anh.', 'cafe-core' ) );
		}
		wp_safe_redirect( (string) get_edit_post_link( $en_id, 'raw' ) );
		exit;
	}

	/**
	 * Tạo (nếu chưa có) trang riêng tư chứa bản tiếng Anh của $post_id và trả về id của nó.
	 * Cũng dùng trong scripts/setup/translations-en.php.
	 */
	public static function ensure_en_page( int $post_id, string $title = '', string $content = '' ): int {
		$source = get_post( $post_id );
		if ( ! $source || 'page' !== $source->post_type ) {
			return 0;
		}
		$en_id = (int) get_post_meta( $post_id, PAGE_META, true );
		if ( $en_id && get_post( $en_id ) ) {
			return $en_id;
		}
		$en_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'page',
					'post_status'  => 'private',
					'post_title'   => '' !== $title ? $title : $source->post_title . ' (English)',
					'post_name'    => $source->post_name . '-en',
					'post_content' => '' !== $content ? $content : $source->post_content,
					'meta_input'   => array( SOURCE_META => $post_id ),
				)
			),
			true
		);
		if ( is_wp_error( $en_id ) ) {
			return 0;
		}
		update_post_meta( $post_id, PAGE_META, $en_id );
		return (int) $en_id;
	}

	// ─── Danh mục, giá trị thuộc tính ─────────────────────────────────────────

	public static function register_term_fields(): void {
		$taxonomies = array( 'product_cat' );
		if ( function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
			$taxonomies = array_merge( $taxonomies, wc_get_attribute_taxonomy_names() );
		}
		foreach ( $taxonomies as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", array( self::class, 'add_term_field' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( self::class, 'edit_term_field' ) );
		}
	}

	public static function add_term_field(): void {
		?>
		<div class="form-field">
			<label for="cafe-en-name"><?php esc_html_e( 'Tên tiếng Anh', 'cafe-core' ); ?></label>
			<input type="text" id="cafe-en-name" name="cafe_en_name" value="">
			<p><?php esc_html_e( 'Hiển thị khi khách chọn English. Để trống thì dùng tên tiếng Việt.', 'cafe-core' ); ?></p>
		</div>
		<?php
	}

	public static function edit_term_field( \WP_Term $term ): void {
		?>
		<tr class="form-field">
			<th scope="row"><label for="cafe-en-name"><?php esc_html_e( 'Tên tiếng Anh', 'cafe-core' ); ?></label></th>
			<td>
				<input type="text" id="cafe-en-name" name="cafe_en_name" value="<?php echo esc_attr( (string) get_term_meta( $term->term_id, TERM_NAME_META, true ) ); ?>">
				<p class="description"><?php esc_html_e( 'Hiển thị khi khách chọn English. Để trống thì dùng tên tiếng Việt.', 'cafe-core' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/** Nonce và quyền đã được WordPress kiểm tra trong form thêm/sửa term. */
	public static function save_term( int $term_id, int $tt_id, string $taxonomy ): void {
		if ( ! translatable_taxonomy( $taxonomy ) || ! isset( $_POST['cafe_en_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$name = sanitize_text_field( wp_unslash( $_POST['cafe_en_name'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'' === $name ? delete_term_meta( $term_id, TERM_NAME_META ) : update_term_meta( $term_id, TERM_NAME_META, $name );
	}

	// ─── Menu ──────────────────────────────────────────────────────────────────

	public static function menu_item_field( int $item_id, $item ): void {
		?>
		<p class="field-cafe-en-title description description-wide">
			<label for="edit-menu-item-cafe-en-title-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'Nhãn tiếng Anh', 'cafe-core' ); ?><br>
				<input type="text" id="edit-menu-item-cafe-en-title-<?php echo esc_attr( $item_id ); ?>" class="widefat" name="cafe-en-title[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( (string) get_post_meta( $item_id, MENU_META, true ) ); ?>">
			</label>
		</p>
		<?php
	}

	/** Chạy trong wp_update_nav_menu_item, sau khi WordPress đã kiểm tra nonce update-nav_menu. */
	public static function save_menu_item( int $menu_id, int $item_id ): void {
		if ( ! isset( $_POST['cafe-en-title'] ) || ! is_array( $_POST['cafe-en-title'] ) || ! array_key_exists( $item_id, $_POST['cafe-en-title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$title = sanitize_text_field( wp_unslash( $_POST['cafe-en-title'][ $item_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'' === $title ? delete_post_meta( $item_id, MENU_META ) : update_post_meta( $item_id, MENU_META, $title );
	}

	// ─── Bảng cụm từ ──────────────────────────────────────────────────────────

	public static function register_phrases_setting(): void {
		register_setting(
			SettingsPage::GROUP,
			Phrases::OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => static fn( $value ): string => sanitize_textarea_field( (string) $value ),
				'default'           => '',
			)
		);
	}

	public static function phrases_field(): void {
		?>
		<h2><?php esc_html_e( 'Bản dịch tiếng Anh', 'cafe-core' ); ?></h2>
		<p><?php esc_html_e( 'Cụm từ trong khẩu hiệu, footer, widget, phương thức thanh toán, câu chính sách ở trang thanh toán và tên thuộc tính sản phẩm. Mỗi dòng: Tiếng Việt = English. Dòng bắt đầu bằng # là ghi chú.', 'cafe-core' ); ?></p>
		<p><?php esc_html_e( 'Trang, danh mục và menu được dịch ngay trong màn hình sửa của chúng.', 'cafe-core' ); ?></p>
		<textarea name="<?php echo esc_attr( Phrases::OPTION ); ?>" rows="14" class="large-text code"><?php echo esc_textarea( (string) get_option( Phrases::OPTION, '' ) ); ?></textarea>
		<?php
	}
}
