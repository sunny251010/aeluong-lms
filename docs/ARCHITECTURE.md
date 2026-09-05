# Kiến trúc dự án

## Tổng quan

AELuong LMS là website học tập đơn giản, phát triển local bằng LocalWP, version-control bằng Git/GitHub và dự kiến deploy lên GoDaddy WordPress Hosting qua GitHub Actions.

Mục tiêu chính:

- Homepage tối giản, hiển thị danh sách khóa học.
- Người dùng xem thông tin khóa học khi chưa đăng nhập.
- Nút "Học ngay" xử lý theo trạng thái đăng nhập và quyền truy cập khóa học.
- Course archive hiển thị card grid responsive với CTA theo trạng thái tài khoản.
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

## Child theme

Child theme project:

wp-content/themes/lms-kadence-child/

Child theme chỉ kế thừa Kadence và enqueue stylesheet của parent trước stylesheet của child. Không đặt business logic LMS vào child theme. Kadence parent vẫn được giữ nguyên.

## Custom plugin

Plugin project-owned hiện tại:

`wp-content/plugins/lms-site-core/`

Plugin này chỉ chứa behavior riêng của website mà LearnPress hoặc theme không nên xử lý:

- Redirect homepage về course archive native của LearnPress.
- Ẩn các mục Checkout/Instructor khỏi navigation trong giai đoạn học viên.
- Ẩn Orders, Statistics, Add-ons, Themes, Tools và Help Center khỏi submenu LearnPress; giữ lại Settings để cấu hình hệ thống và currency; ẩn các page Checkout/Instructor khỏi danh sách Pages trong wp-admin.
- Giữ nguyên các page và route phụ để có thể bật lại sau này.

Không tự xây lại các phần LearnPress đã hỗ trợ như course, lesson, section, enrollment và progress.

## Homepage

Homepage cần giữ tối giản:

- Homepage / chuyển tới course archive native /courses/.
- Course list lấy từ dữ liệu LearnPress, không hardcode.
- Course card gồm tên khóa học, mô tả ngắn, thumbnail nếu có và nút "Học ngay".
- Zalo contact.
- Footer cơ bản.

Không cần hero lớn, animation phức tạp, marketing section dài, testimonial, pricing, blog feed hoặc newsletter trừ khi sau này có yêu cầu rõ.

## Login và access flow

Khi user click CTA course:

- Chưa đăng nhập: mở login modal bằng username/password.
- Đã đăng nhập và có quyền course: hiển thị Continue và chuyển tới lesson phù hợp.
- Đã đăng nhập nhưng chưa có quyền: mở modal thông báo và nút liên hệ Zalo.
- Header hiển thị Login khi guest và avatar khi đã đăng nhập.

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

- Kadence parent theme đã được cài và active ở local và production.
- Child theme lms-kadence-child đã được tạo trong repository; trạng thái active vẫn được quản lý riêng trên từng môi trường.
- LearnPress đã được cài và active ở local và production theo kiểm tra hiện tại.
- Custom plugin lms-site-core đã được tạo và active ở local; cần deploy và active riêng trên production.
- Git ignore third-party themes/plugins; chỉ whitelist child theme và custom plugin do project sở hữu.
- Git đang được cấu hình để track tài liệu và custom code, không track WordPress core, config local, uploads hoặc cache.


## Giao diện header và hỗ trợ dự án

- Header giữ logo/site title trong cùng container với navigation để các action nằm cân theo trục nội dung.
- Action Support mở modal thông tin chuyển khoản; ảnh ngân hàng hiện là placeholder nội bộ và phải được thay trước khi nhận ủng hộ thật.
- Login modal dùng chung cho action header và CTA khóa học; Google OAuth vẫn để phase sau.
- Khóa có meta _lms_contact_course = 1 sẽ mở modal liên hệ Zalo để admin cấp quyền, không đi qua checkout hoặc tự enroll.

- Plugin cung cấp checkbox Contact admin before enrollment cho các course cần admin cấp quyền trước khi học.


### Hiển thị danh sách khóa học

Trang archive khóa học mặc định dùng chế độ Grid/Card để người học dễ quét nội dung. Các nút chuyển layout của LearnPress vẫn được giữ lại để người dùng đổi sang List khi cần; CSS của child theme chỉ áp dụng bố cục tương ứng với data-layout hiện tại.


### Luồng checkout tạm thời

Trang checkout LearnPress vẫn được giữ để phục vụ payment về sau. Trong giai đoạn cấp quyền thủ công, user thường chưa có quyền học sẽ được chuyển về trang course kèm modal thông báo và nút liên hệ Zalo; admin vẫn có thể truy cập checkout.


### Header navigation

Courses và Contact được căn giữa vùng header. Donation và account action được neo về bên phải trên desktop; mobile giữ bố cục responsive. Donation mở modal thông tin ủng hộ ngân hàng.


### Cấp quyền học thủ công

Admin có thể mở LearnPress > Enroll student, chọn WordPress user và published course để tạo enrollment trực tiếp bằng API/model của LearnPress. Luồng này không đi qua checkout/payment và không tạo custom table.


### Quản lý Student trong wp-admin

Plugin tạo role Student với quyền cơ bản như Subscriber. Admin có thể mở LearnPress > Students & enrollment để tạo Student, tìm kiếm danh sách theo tên/username/email và chọn các course được cấp quyền. Bản ghi enrollment vẫn do LearnPress quản lý; plugin không tạo custom table.
