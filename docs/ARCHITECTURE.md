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

Google OAuth không tự implement trong LMS Site Core; dùng Nextend Social Login để xử lý provider, callback và account linking. LMS Site Core chỉ render shortcode Google vào login modal.

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
- Login modal dùng chung cho action header và CTA khóa học; nếu Nextend Google provider đã enabled, modal hiển thị thêm nút Google.
- Khóa có meta _lms_contact_course = 1 sẽ mở modal liên hệ Zalo để admin cấp quyền, không đi qua checkout hoặc tự enroll.

- Plugin cung cấp checkbox Contact admin before enrollment cho các course cần admin cấp quyền trước khi học.


### Course assets và modal

Ba course hiện tại dùng ảnh raster trong custom plugin làm nguồn dự phòng và đồng thời được upload vào WordPress Media Library để gán Featured image. Media Library và featured_media là dữ liệu database/uploads, không được deploy bằng Git; production cần upload ảnh và gán lại Featured image. Login modal và Donation modal dùng chung panel, max-width hiện là 508px (tăng 48px so với trước).

### Hiển thị danh sách khóa học

Trang archive khóa học mặc định dùng chế độ Grid/Card để người học dễ quét nội dung. Các nút chuyển layout của LearnPress vẫn được giữ lại để người dùng đổi sang List khi cần; CSS của child theme chỉ áp dụng bố cục tương ứng với data-layout hiện tại.


### Luồng checkout tạm thời

Trang checkout LearnPress vẫn được giữ để phục vụ payment về sau. Trong giai đoạn cấp quyền thủ công, user thường chưa có quyền học sẽ được chuyển về trang course kèm modal thông báo và nút liên hệ Zalo; admin vẫn có thể truy cập checkout.


### Header navigation

Courses và Contact được căn giữa vùng header. Donation và account action được neo về bên phải trên desktop; mobile giữ bố cục responsive. Donation mở modal thông tin ủng hộ ngân hàng.


### Cấp quyền học thủ công

Admin có thể mở LearnPress > Enroll student, chọn WordPress user và published course để tạo enrollment trực tiếp bằng API/model của LearnPress. Luồng này không đi qua checkout/payment và không tạo custom table.


### Quản lý Student trong wp-admin

Plugin tạo role Student với quyền cơ bản như Subscriber. Admin có thể mở LearnPress > Students & enrollment để tạo Student, tìm kiếm danh sách theo tên/username/email và chọn các course được cấp quyền. Bản ghi enrollment vẫn do LearnPress quản lý; plugin không tạo custom table. Danh sách mặc định sắp xếp tài khoản mới đăng ký trước; form tạo Student đặt Full name và Username trên cùng một hàng, còn Password có nút Show/Hide.


### Trang Contact và feedback

Plugin cung cấp shortcode [lms_site_contact_form] cho trang Contact. Form hỗ trợ góp ý về khóa học, website, tài khoản hoặc nội dung khác; có thể chọn khóa học liên quan và tự điền tên/email khi người dùng đã đăng nhập. Dữ liệu được nonce bảo vệ, lọc đầu vào và gửi tới admin_email bằng wp_mail; plugin không tạo custom table và không lưu nội dung góp ý trong database. Zalo 0984 715 632 là kênh liên hệ nhanh dự phòng.

Contact dùng body class riêng để child theme ẩn hero/breadcrumb mặc định của Kadence và dựng lại nền page và panel form responsive, không thêm banner/hero riêng; plugin vẫn giữ toàn bộ logic form độc lập với theme.

Desktop dùng Primary Menu riêng cho Courses/Contact và một nhóm action riêng ở cột right của Kadence cho Donation/account; mobile vẫn giữ action trong mobile menu.

Secondary Navigation là menu WordPress do admin quản lý thủ công, gồm các custom link #lms-support-modal và #lms-login-modal. Plugin chỉ lọc location secondary để thêm class, modal trigger và chuyển item Login thành avatar/profile URL khi user đã đăng nhập; không tự chèn item vào Primary hoặc render nhóm header riêng.

- Contact page là dữ liệu database; plugin cung cấp shortcode lms_site_contact_form và tự render fallback khi page slug contact đang rỗng, nên deploy code không cần đồng bộ post_content từ local.

## Cập nhật local ngày 2026-09-07

- Phát triển local trước; việc đồng bộ production được hoãn theo quyết định của người dùng.
- LMS Site Core 0.10.0 bổ sung kiểm tra điều kiện enroll tại hooks model LearnPress, bao gồm modern và legacy; giao diện ẩn nút không đủ để bảo vệ cấp quyền thủ công. Admin tiếp tục dùng tool native hiện có.
- CTA Continue tại archive lấy item tiếp theo qua UserCourseModel::get_item_continue() và CourseModel::get_item_link(); không tạo hệ thống progress riêng.
- Lesson authoring dùng tùy chọn native learn_press_enable_gutenberg_lesson=yes. Plugin không ép show_in_rest cho khách hoặc thay permission của LearnPress. Course/Quiz editor giữ cấu hình hiện có.
- includes/lesson-authoring.php đăng ký 3 core block patterns giới hạn cho lp_lesson và metabox hướng dẫn copy nội dung. Không thêm dependency, page builder hay block custom.
- Đây là nền tảng soạn bài theo khối; chưa mô phỏng chính xác Google Sites private vì chưa kết nối được Chrome để đối chiếu.


### Paste Google Sites đã đối chiếu

Chrome đã kết nối và kiểm tra bảng IPA private. Lesson Classic dùng tiny_mce_before_init giới hạn lp_lesson để giữ typography khi paste; core Table mặc định vẫn chuẩn hóa nội dung. Không thêm builder, dependency hay route. Chi tiết và giới hạn trong LESSON_AUTHORING.md.


### Donation settings trong wp-admin

Donation modal đọc cấu hình từ option lms_site_core_donation_settings. Admin có thể thay tiêu đề, mô tả, thông báo ngân hàng, tên ngân hàng, số tài khoản, chủ tài khoản và ảnh QR trong Settings > LMS Site Core. Ảnh QR dùng Media Library; nếu chưa chọn ảnh riêng, plugin dùng placeholder nội bộ.

- Sticky Zalo dùng chung option zalo_phone trong lms_site_core_donation_settings; Contact, access modal, frontend localized URL và sticky đều lấy từ helper này.

### Google Login qua Nextend

- Nextend Social Login là plugin bên thứ ba, được cài và active riêng trên local/production; không commit source plugin vào Git.
- LMS Site Core kiểm tra shortcode nextend_social_login và chỉ render provider google khi Nextend đã cấu hình/enabled provider.
- OAuth credential, redirect URI, state và account linking do Nextend quản lý; LMS Site Core không lưu Client ID/Client Secret.

### Cập nhật CTA và giá liên hệ local ngày 2026-09-08

- LMS Site Core 0.13.0 luôn render CTA `Tiếp tục học` cho tài khoản đã có quyền, kể cả khi LearnPress chưa trả về lesson tiếp theo; URL fallback là trang course.
- Guest trên course archive luôn thấy `Xem chi tiết`; chỉ user đã đăng nhập mới đi vào nhánh liên hệ/cấp quyền.
- Course có meta `_lms_contact_course = 1` hiển thị giá `Liên hệ` ở archive và trang chi tiết. Giá số trong LearnPress vẫn giữ nguyên để không phá order/payment về sau.
### Cập nhật performance local ngày 2026-09-08

- LMS Site Core 0.14.0 cache kết quả `UserCourseModel::find()` theo cặp user/course trong phạm vi một request.
- Mục tiêu là tránh việc archive, course detail và lesson detail lặp lại cùng một query enrollment khi nhiều hook LearnPress cùng kiểm tra quyền.
- Không cache qua request và không thay đổi dữ liệu enrollment; LearnPress vẫn là source of truth.
### Cập nhật luồng archive và quyền học — 2026-09-10

- Card archive của LearnPress tiếp tục dùng permalink native: click ảnh/tên/card sẽ mở trực tiếp trang course.
- CTA Xem chi tiết của guest không đi thẳng vào course bằng click CTA; nó mở login modal và lưu course ID cùng URL trong sessionStorage.
- Sau khi đăng nhập username/password hoặc quay lại từ Google OAuth, trang archive chuyển người dùng về đúng course vừa chọn. Nếu user chưa có enrollment, access modal hiện nút đóng và nút liên hệ Zalo; nếu đã có quyền, course detail hiển thị Tiếp tục học.
- Course contact-only được truyền vào frontend state để modal hiển thị đúng nội dung liên hệ sau OAuth. Không tạo route hoặc bảng dữ liệu mới.

### Cập nhật điều hướng card khóa học — 2026-09-10

- User đã đăng nhập và có quyền học: ảnh, tiêu đề và CTA của card dùng cùng URL bài học tiếp theo; nếu chưa có enrollment row riêng nhưng là admin thì dùng bài đầu tiên trong curriculum.
- Guest hoặc user chưa có quyền vẫn giữ course overview để đi qua login/access modal.
- Không tạo route hoặc bảng dữ liệu mới; LearnPress vẫn là source of truth cho curriculum và enrollment.

### Cập nhật admin bar, logout và footer — 2026-09-10

- WordPress Admin Bar chỉ hiển thị với user có capability manage_options; Student và tài khoản Google vẫn dùng frontend bình thường nhưng không thấy thanh quản trị.
- Logout redirect về course archive native của LearnPress.
- Footer credit được thay trong child theme; Kadence parent không bị sửa. Link Bang Nguyen trỏ tới Facebook cá nhân đã cấu hình.
- Hướng Google pre-approval: admin quản lý allowlist email exact theo course; sau hook login/register của Google, LMS Site Core tạo enrollment LearnPress idempotent. UI đã triển khai tại Settings > Google access; danh sách email/course cần nhập riêng trên từng môi trường.

### Cập nhật cấp quyền Google và thu hồi enrollment — 2026-09-10

- Admin cấu hình allowlist tại `Settings > Google access`: mỗi dòng gồm đúng Gmail Google và các course được cấp tự động.
- Option WordPress `lms_site_core_google_allowlist` lưu danh sách đã chuẩn hóa email và course ID; không tạo custom table và không lưu Client ID/Client Secret.
- LMS Site Core lắng nghe `wp_login`, `nsl_google_login` và `nsl_google_register_new_user`. Sau khi Nextend hoàn tất đăng nhập/đăng ký, hệ thống đối chiếu email chính xác rồi gọi LearnPress enrollment idempotent. Email ngoài allowlist không được cấp khóa trả phí.
- Mọi user đã đăng nhập đều được auto-enroll khóa miễn phí đang publish. Vì vậy khóa miễn phí không cần cấp thủ công từng tài khoản; cờ `_lms_contact_course` không còn chặn khóa miễn phí.
- Màn hình `Students & enrollment` hỗ trợ grant/revoke khóa trả phí. Bỏ tick rồi lưu sẽ đổi enrollment sang `cancel` và giữ lesson progress/metadata của LearnPress để có thể cấp lại sau.
- Xóa Gmail khỏi allowlist chỉ ngăn cấp quyền ở lần Google login sau, không tự động xóa enrollment hiện có. Muốn thu hồi ngay, admin dùng màn hình enrollment.

### Sửa lỗi cấp quyền Google và lesson miễn phí — 2026-09-10
- Frontend không còn gọi EnrollmentTools::enroll_student() vì API này yêu cầu capability quản trị và trả lp_mcp_forbidden khi chạy trong hook đăng nhập.
- LMS Site Core dùng các helper native learn_press_get_user_item() và learn_press_update_user_item_field() để đọc, tạo, reactivate và revoke user-course enrollment của LearnPress.
- Sau khi ghi enrollment, request cache được xóa để trạng thái enrolled/cancel phản ánh ngay trong cùng request.
- Filter learnpress/course/can-view-content đồng bộ policy access của LMS Site Core với lesson gate của LearnPress: user đăng nhập có quyền allowlist hoặc course miễn phí được xem nội dung lesson.

### Cập nhật giao diện Google access — 2026-09-10
- Trang Settings > Google access hiển thị allowlist dạng bảng để admin tìm theo Gmail, tên hoặc username.
- Mỗi dòng cho phép chỉnh email, chọn trực tiếp các course trả phí và xóa dòng khỏi allowlist trước khi lưu.
- Nếu Gmail đã có WordPress account, bảng hiển thị display name/username và liên kết mở Students & enrollment.
- Course miễn phí hiển thị Tự động và không cần cấp thủ công.
- Allowlist chỉ quyết định việc tự cấp ở lần Google login; enrollment LearnPress đã tồn tại vẫn phải thu hồi tại Students & enrollment.