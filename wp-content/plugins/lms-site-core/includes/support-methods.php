<?php
/**
 * Support page methods and their WordPress admin settings.
 *
 * The site only displays bank details or an external payment link. It never
 * processes a payment, stores payment credentials, or verifies a transaction.
 */

defined( 'ABSPATH' ) || exit;

const LMS_SITE_CORE_SUPPORT_METHODS_OPTION = 'lms_site_core_support_methods';

/**
 * Return the published support page URL, with a predictable fallback before
 * the administrator creates the page manually.
 */
function lms_site_core_support_page_url(): string {
	$page = get_page_by_path( 'ung-ho' );

	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return get_permalink( $page );
	}

	return home_url( '/ung-ho/' );
}

/**
 * Recognize both the historic modal item and the new support page menu item.
 */
function lms_site_core_is_support_menu_item( $item ): bool {
	$url = isset( $item->url ) ? trim( (string) $item->url ) : '';

	if ( '#lms-support-modal' === $url || 'lms-support-modal' === lms_site_core_menu_item_fragment( $item ) ) {
		return true;
	}

	$path = (string) wp_parse_url( $url, PHP_URL_PATH );

	return '/ung-ho' === untrailingslashit( $path );
}

/**
 * Convert the single legacy modal configuration to one displayable bank method.
 */
function lms_site_core_legacy_support_method(): array {
	$legacy = get_option( 'lms_site_core_donation_settings', null );

	if ( ! is_array( $legacy ) || empty( $legacy ) ) {
		return array();
	}

	$settings = lms_site_core_get_donation_settings();
	$has_data = ! empty( $legacy['bank_name'] ) || ! empty( $legacy['account'] ) || ! empty( $legacy['holder'] ) || ! empty( $legacy['qr_image_id'] );

	if ( ! $has_data ) {
		return array();
	}

	return array(
		'type'         => 'bank_transfer',
		'title'        => ! empty( $settings['title'] ) ? (string) $settings['title'] : 'Chuyển khoản ngân hàng',
		'description'  => (string) ( $settings['description'] ?? '' ),
		'bank_name'    => (string) ( $settings['bank_name'] ?? '' ),
		'account'      => (string) ( $settings['account'] ?? '' ),
		'holder'       => (string) ( $settings['holder'] ?? '' ),
		'image_id'     => absint( $settings['qr_image_id'] ?? 0 ),
		'action_label' => '',
		'action_url'   => '',
	);
}

/**
 * Read configured support methods. Legacy bank data is shown until an admin
 * saves the new settings for the first time.
 */
function lms_site_core_get_support_methods(): array {
	$methods = get_option( LMS_SITE_CORE_SUPPORT_METHODS_OPTION, null );

	if ( ! is_array( $methods ) ) {
		$legacy = lms_site_core_legacy_support_method();

		return $legacy ? array( $legacy ) : array();
	}

	return lms_site_core_sanitize_support_methods( $methods );
}

/**
 * Keep only safe, public display values in the WordPress option.
 */
function lms_site_core_sanitize_support_methods( $input ): array {
	$input   = is_array( $input ) ? $input : array();
	$methods = array();

	foreach ( $input as $method ) {
		if ( ! is_array( $method ) ) {
			continue;
		}

		$type = isset( $method['type'] ) && 'payment_link' === $method['type'] ? 'payment_link' : 'bank_transfer';
		$item = array(
			'type'         => $type,
			'title'        => isset( $method['title'] ) ? sanitize_text_field( $method['title'] ) : '',
			'description'  => isset( $method['description'] ) ? sanitize_textarea_field( $method['description'] ) : '',
			'bank_name'    => isset( $method['bank_name'] ) ? sanitize_text_field( $method['bank_name'] ) : '',
			'account'      => isset( $method['account'] ) ? sanitize_text_field( $method['account'] ) : '',
			'holder'       => isset( $method['holder'] ) ? sanitize_text_field( $method['holder'] ) : '',
			'image_id'     => isset( $method['image_id'] ) ? absint( $method['image_id'] ) : 0,
			'action_label' => isset( $method['action_label'] ) ? sanitize_text_field( $method['action_label'] ) : '',
			'action_url'   => isset( $method['action_url'] ) ? esc_url_raw( $method['action_url'], array( 'http', 'https' ) ) : '',
		);

		if ( '' === $item['title'] && '' === $item['bank_name'] && '' === $item['action_url'] ) {
			continue;
		}

		$methods[] = $item;
	}

	return array_values( $methods );
}

/**
 * Register the support methods option separately from legacy modal settings.
 */
function lms_site_core_register_support_methods_settings(): void {
	register_setting(
		'lms_site_core_support_methods',
		LMS_SITE_CORE_SUPPORT_METHODS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'lms_site_core_sanitize_support_methods',
			'default'           => array(),
		)
	);

	// Keep the existing Zalo setting in the same form without changing its option.
	register_setting(
		'lms_site_core_support_methods',
		'lms_site_core_donation_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'lms_site_core_sanitize_donation_settings',
		)
	);
}
add_action( 'admin_init', 'lms_site_core_register_support_methods_settings' );

/**
 * Render one editable method row. The index may be __INDEX__ in the JS template.
 */
function lms_site_core_render_support_method_admin_row( array $method, $index ): void {
	$method = wp_parse_args(
		$method,
		array(
			'type'         => 'bank_transfer',
			'title'        => '',
			'description'  => '',
			'bank_name'    => '',
			'account'      => '',
			'holder'       => '',
			'image_id'     => 0,
			'action_label' => '',
			'action_url'   => '',
		)
	);
	$image_id  = absint( $method['image_id'] );
	$image_url = $image_id ? (string) wp_get_attachment_image_url( $image_id, 'medium' ) : '';
	$name      = LMS_SITE_CORE_SUPPORT_METHODS_OPTION . '[' . $index . ']';
	?>
	<fieldset class="lms-support-method-admin-row">
		<legend>Phương thức ủng hộ</legend>
		<p>
			<label>
				<span>Loại phương thức</span>
				<select name="<?php echo esc_attr( $name ); ?>[type]" data-lms-support-type>
					<option value="bank_transfer" <?php selected( 'bank_transfer', $method['type'] ); ?>>Chuyển khoản</option>
					<option value="payment_link" <?php selected( 'payment_link', $method['type'] ); ?>>Liên kết thanh toán</option>
				</select>
			</label>
		</p>
		<p>
			<label>
				<span>Tên hiển thị</span>
				<input class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $method['title'] ); ?>" placeholder="Ví dụ: Ủng hộ qua Vietcombank">
			</label>
		</p>
		<p>
			<label>
				<span>Mô tả ngắn</span>
				<textarea class="large-text" rows="3" name="<?php echo esc_attr( $name ); ?>[description]" placeholder="Nội dung tùy chọn hiển thị dưới tiêu đề."><?php echo esc_textarea( $method['description'] ); ?></textarea>
			</label>
		</p>
		<div data-lms-bank-fields>
			<p>
				<label><span>Ngân hàng</span><input class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[bank_name]" value="<?php echo esc_attr( $method['bank_name'] ); ?>"></label>
				<label><span>Số tài khoản</span><input class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[account]" value="<?php echo esc_attr( $method['account'] ); ?>"></label>
				<label><span>Chủ tài khoản</span><input class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[holder]" value="<?php echo esc_attr( $method['holder'] ); ?>"></label>
			</p>
		</div>
		<p>
			<label><span>Nhãn nút (tùy chọn)</span><input class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[action_label]" value="<?php echo esc_attr( $method['action_label'] ); ?>" placeholder="Ví dụ: Mở PayPal"></label>
			<label><span>Link nút (http/https)</span><input class="regular-text" type="url" name="<?php echo esc_attr( $name ); ?>[action_url]" value="<?php echo esc_url( $method['action_url'] ); ?>" placeholder="https://paypal.me/... hoặc trang thanh toán khác"></label>
		</p>
		<div class="lms-support-method-image">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[image_id]" value="<?php echo esc_attr( $image_id ); ?>" data-lms-support-image-id>
			<img src="<?php echo esc_url( $image_url ); ?>" alt="" data-lms-support-image-preview <?php echo $image_url ? '' : 'hidden'; ?>>
			<button type="button" class="button" data-lms-support-select-image>Chọn ảnh QR / logo</button>
			<button type="button" class="button-link-delete" data-lms-support-remove-image <?php disabled( ! $image_id ); ?>>Xóa ảnh</button>
		</div>
		<p><button type="button" class="button-link-delete" data-lms-support-remove-method>Xóa phương thức này</button></p>
	</fieldset>
	<?php
}

/**
 * Render Settings > LMS Site Core. Existing Zalo config remains in the legacy
 * option to avoid breaking Contact and access support flows.
 */
function lms_site_core_render_support_methods_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_enqueue_media();
	$methods        = lms_site_core_get_support_methods();
	$legacy_settings = lms_site_core_get_donation_settings();
	?>
	<div class="wrap lms-support-methods-admin">
		<h1>Ủng hộ</h1>
		<p>Thêm nhiều phương thức như tài khoản ngân hàng, QR, PayPal hoặc bất kỳ liên kết thanh toán HTTPS nào. Website chỉ hiển thị thông tin và mở link; không xử lý giao dịch.</p>
		<p>Trang public dùng shortcode <code>[lms_site_support_methods]</code>. Hãy tạo Page thủ công với slug <code>ung-ho</code> và đặt shortcode này vào nội dung.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'lms_site_core_support_methods' ); ?>
			<section class="lms-support-zalo-admin">
				<h2>Liên hệ Zalo dùng chung</h2>
				<p>Số này được dùng cho trang Liên hệ, nút Zalo sticky và thông báo chưa được cấp quyền học.</p>
				<label><span>Số Zalo</span><input class="regular-text" type="text" name="lms_site_core_donation_settings[zalo_phone]" value="<?php echo esc_attr( $legacy_settings['zalo_phone'] ); ?>"></label>
				<?php foreach ( array( 'title', 'description', 'notice', 'bank_name', 'account', 'holder', 'qr_image_id' ) as $legacy_key ) : ?>
					<input type="hidden" name="lms_site_core_donation_settings[<?php echo esc_attr( $legacy_key ); ?>]" value="<?php echo esc_attr( $legacy_settings[ $legacy_key ] ); ?>">
				<?php endforeach; ?>
			</section>
			<h2>Phương thức ủng hộ</h2>
			<div data-lms-support-methods>
				<?php foreach ( $methods as $index => $method ) : ?>
					<?php lms_site_core_render_support_method_admin_row( $method, $index ); ?>
				<?php endforeach; ?>
			</div>
			<p><button type="button" class="button" data-lms-support-add-method>Thêm phương thức</button></p>
			<?php submit_button( 'Lưu phương thức ủng hộ' ); ?>
		</form>
		<script type="text/html" id="tmpl-lms-support-method-row"><?php lms_site_core_render_support_method_admin_row( array(), '__INDEX__' ); ?></script>
	</div>
	<style>
		.lms-support-methods-admin [data-lms-support-methods] { max-width: 980px; }
		.lms-support-zalo-admin { max-width: 980px; margin: 18px 0 28px; padding: 16px 18px; border-left: 4px solid #2271b1; background: #fff; }
		.lms-support-zalo-admin h2 { margin-top: 0; }
		.lms-support-zalo-admin label { display: inline-flex; flex-direction: column; gap: 5px; font-weight: 600; }
		.lms-support-method-admin-row { margin: 18px 0; padding: 18px; border: 1px solid #c3c4c7; background: #fff; }
		.lms-support-method-admin-row legend { padding: 0 4px; font-weight: 600; }
		.lms-support-method-admin-row label { display: inline-flex; min-width: 220px; flex-direction: column; gap: 5px; margin: 0 14px 12px 0; vertical-align: top; }
		.lms-support-method-admin-row label > span { font-weight: 600; }
		.lms-support-method-image img { display: block; max-width: 180px; max-height: 180px; margin: 0 0 10px; }
	</style>
	<script>
		jQuery(function ($) {
			var methods = $('[data-lms-support-methods]');
			var template = $('#tmpl-lms-support-method-row').html();

			function updateBankFields(row) {
				row.find('[data-lms-bank-fields]').toggle(row.find('[data-lms-support-type]').val() === 'bank_transfer');
			}
			methods.find('.lms-support-method-admin-row').each(function () { updateBankFields($(this)); });
			$(document).on('change', '[data-lms-support-type]', function () { updateBankFields($(this).closest('.lms-support-method-admin-row')); });
			$('[data-lms-support-add-method]').on('click', function () {
				var index = methods.children('.lms-support-method-admin-row').length;
				methods.append(template.replace(/__INDEX__/g, index));
				updateBankFields(methods.children('.lms-support-method-admin-row').last());
			});
			$(document).on('click', '[data-lms-support-remove-method]', function () {
				$(this).closest('.lms-support-method-admin-row').remove();
			});
			$(document).on('click', '[data-lms-support-select-image]', function (event) {
				event.preventDefault();
				var row = $(this).closest('.lms-support-method-admin-row');
				var frame = wp.media({ title: 'Chọn ảnh QR hoặc logo', button: { text: 'Dùng ảnh này' }, multiple: false, library: { type: 'image' } });
				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					row.find('[data-lms-support-image-id]').val(attachment.id);
					row.find('[data-lms-support-image-preview]').attr('src', attachment.url).prop('hidden', false);
					row.find('[data-lms-support-remove-image]').prop('disabled', false);
				});
				frame.open();
			});
			$(document).on('click', '[data-lms-support-remove-image]', function (event) {
				event.preventDefault();
				var row = $(this).closest('.lms-support-method-admin-row');
				row.find('[data-lms-support-image-id]').val('0');
				row.find('[data-lms-support-image-preview]').attr('src', '').prop('hidden', true);
				$(this).prop('disabled', true);
			});
		});
	</script>
	</div>
	<?php
}

/**
 * Render the support methods on the manually-created /ung-ho/ page.
 */
function lms_site_core_support_methods_shortcode(): string {
	$methods = lms_site_core_get_support_methods();

	ob_start();
	?>
	<section class="lms-support-methods" aria-labelledby="lms-support-methods-title">
		<header class="lms-support-methods-intro">
			<h2 id="lms-support-methods-title">Ủng hộ dự án</h2>
			<p>Nếu nội dung hữu ích, bạn có thể chọn phương thức phù hợp bên dưới.</p>
		</header>
		<?php if ( $methods ) : ?>
			<div class="lms-support-methods-grid">
				<?php foreach ( $methods as $method ) : ?>
					<?php
					$image_id  = absint( $method['image_id'] );
					$image_url = $image_id ? (string) wp_get_attachment_image_url( $image_id, 'medium' ) : '';
					?>
					<article class="lms-support-method lms-support-method--<?php echo esc_attr( $method['type'] ); ?><?php echo $image_url ? '' : ' lms-support-method--no-image'; ?>">
						<?php if ( $image_url ) : ?>
							<img class="lms-support-method-image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $method['title'] ); ?>">
						<?php endif; ?>
						<div class="lms-support-method-content">
							<?php if ( $method['title'] ) : ?><h3><?php echo esc_html( $method['title'] ); ?></h3><?php endif; ?>
							<?php if ( $method['description'] ) : ?><p><?php echo nl2br( esc_html( $method['description'] ) ); ?></p><?php endif; ?>
							<?php if ( 'bank_transfer' === $method['type'] && ( $method['bank_name'] || $method['account'] || $method['holder'] ) ) : ?>
								<dl class="lms-support-method-details">
									<?php if ( $method['bank_name'] ) : ?><div><dt>Ngân hàng</dt><dd><?php echo esc_html( $method['bank_name'] ); ?></dd></div><?php endif; ?>
									<?php if ( $method['account'] ) : ?><div><dt>Số tài khoản</dt><dd><?php echo esc_html( $method['account'] ); ?></dd></div><?php endif; ?>
									<?php if ( $method['holder'] ) : ?><div><dt>Chủ tài khoản</dt><dd><?php echo esc_html( $method['holder'] ); ?></dd></div><?php endif; ?>
								</dl>
							<?php endif; ?>
							<?php if ( $method['action_url'] ) : ?>
								<a class="lms-support-method-action" href="<?php echo esc_url( $method['action_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $method['action_label'] ?: 'Mở phương thức ủng hộ' ); ?></a>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="lms-support-methods-empty">Thông tin ủng hộ đang được cập nhật.</p>
		<?php endif; ?>
	</section>
	<?php

	return (string) ob_get_clean();
}
add_shortcode( 'lms_site_support_methods', 'lms_site_core_support_methods_shortcode' );