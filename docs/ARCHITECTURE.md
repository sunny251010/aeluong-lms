# Kiến trúc dự án

## Tổng quan

AELuong LMS là website học tập đơn giản, phát triển local bằng LocalWP, version-control bằng Git/GitHub và dự kiến deploy lên GoDaddy WordPress Hosting qua GitHub Actions.

Mục tiêu chính:

- Homepage tối giản, hiển thị danh sách khóa học.
- Người dùng xem thông tin khóa học khi chưa đăng nhập.
- Nút "Học ngay" xử lý theo trạng thái đăng nhập và quyền truy cập khóa học.
- Admin cấp quyền học cho từng user theo từng course.
- LMS dùng LearnPress làm nền tảng chính.
- Login hỗ trợ WordPress username/password và Google OAuth thông qua plugin có sẵn.
- Có khu vực liên hệ Zalo và footer đơn giản.

Không làm ở giai đoạn này:

- Payment.
- Certificate.
- Gamification.
- Dashboard phức tạp.
- Forum.
- Affiliate.
- Social features.

## Nguyên tắc kiến trúc

- Không sửa WordPress core: `wp-admin/`, `wp-includes/`, các file `wp-*.php` ở root.
- Không sửa trực tiếp source của LearnPress hoặc plugin bên thứ ba.
- Không sửa Kadence parent theme nếu sau này dùng Kadence.
- Custom code chỉ nên nằm trong `wp-content`.
- Giao diện nên nằm trong custom theme hoặc child theme.
- Business logic LMS riêng nên nằm trong plugin riêng, không nhét vào theme.
- Ưu tiên tận dụng WordPress/LearnPress trước khi custom.
- Tránh over-engineering, ưu tiên code đơn giản, dễ debug, ít dependency.

## Custom plugin dự kiến

Nếu cần business logic riêng, plugin dự kiến:

`wp-content/plugins/aeluong-site-core/`

Plugin này chỉ nên chứa logic mà LearnPress hoặc theme không nên xử lý, ví dụ:

- Helper liên quan đến access control.
- Integration tối thiểu với LearnPress.
- Custom shortcode/block.
- Business rule riêng của website.

Không tự xây lại các phần LearnPress đã hỗ trợ như course, lesson, section, enrollment và progress.

## Homepage

Homepage cần giữ tối giản:

- Course list lấy từ dữ liệu LearnPress, không hardcode.
- Course card gồm tên khóa học, mô tả ngắn, thumbnail nếu có và nút "Học ngay".
- Zalo contact.
- Footer cơ bản.

Không cần hero lớn, animation phức tạp, marketing section dài, testimonial, pricing, blog feed hoặc newsletter trừ khi sau này có yêu cầu rõ.

## Login và access flow

Khi user click "Học ngay":

- Chưa đăng nhập: yêu cầu login.
- Đã đăng nhập và có quyền course: chuyển tới course/lesson phù hợp.
- Đã đăng nhập nhưng chưa có quyền: hiển thị thông báo đơn giản rằng tài khoản chưa được cấp quyền khóa học.

Google OAuth không tự implement từ đầu. Nếu cần Google login, cấu hình qua plugin OAuth phù hợp trong WordPress Admin.

## Zalo

URL Zalo không nên hardcode sâu trong nhiều template. Nên có một nơi cấu hình rõ ràng như Customizer, theme option đơn giản, site option hoặc constant/config hợp lý.

Không tạo admin framework nặng chỉ để lưu một URL.

## Lesson editor tương lai

Sau này admin sẽ chuyển nhiều nội dung từ Google Sites sang WordPress. Phase hiện tại chưa xây sâu phần này.

Định hướng:

- Ưu tiên Gutenberg/Block Editor trước khi nghĩ tới custom page builder.
- Copy/paste nội dung từ Google Sites sang lesson phải dễ.
- Hỗ trợ tốt heading, paragraph, image, YouTube, link, file, list, columns và separator.
- Không clone Google Sites.
- Không xây custom page builder trong Phase 1.

## Trạng thái hiện tại

- Chưa có LearnPress trong `wp-content/plugins`.
- Chưa có custom plugin/theme.
- `wp-content/themes` hiện chỉ có các theme mặc định `twentytwenty*`.
- Git đang được cấu hình để track tài liệu và custom code, không track WordPress core, config local, uploads hoặc cache.
