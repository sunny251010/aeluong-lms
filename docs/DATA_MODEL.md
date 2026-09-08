# Data model

## Tổng quan

Data model hiện tại vẫn là WordPress mặc định. Dự án chọn LearnPress làm source of truth cho LMS, vì vậy không tạo database schema custom nếu LearnPress đã xử lý được.

## WordPress data

- Local database không được track trong Git.
- Prefix trong local config hiện là `wp_`, nhưng `wp-config.php` bị ignore và không đưa lên repo.
- Chưa có migration script trong repo.
- Chưa có custom table.

## LearnPress data

Khi cài LearnPress, các dữ liệu sau nên do LearnPress quản lý:

- Courses.
- Sections/chapters.
- Lessons.
- Enrollment/access nếu LearnPress hỗ trợ đủ.
- Progress.

Không duplicate database schema của LearnPress.

## Entity định hướng

Các khái niệm nghiệp vụ của dự án:

- `course`: khóa học, lấy từ LearnPress.
- `lesson`: bài học, lấy từ LearnPress.
- `section`: nhóm/chapter trong course, lấy từ LearnPress.
- `enrollment`: quyền học của user với course, ưu tiên dùng LearnPress.
- `progress`: tiến độ học tập, ưu tiên dùng LearnPress.
- `zalo_contact_url`: URL liên hệ Zalo, nên lưu ở một nơi cấu hình rõ ràng.

## Access rule

Luồng quyền học dự kiến:

- User chưa đăng nhập thì được xem thông tin course nhưng khi bấm "Học ngay" sẽ được yêu cầu login.
- User đã đăng nhập và có quyền course thì được vào học.
- User đã đăng nhập nhưng chưa có quyền thì thấy thông báo chưa được cấp quyền và link Zalo https://zalo.me/0984715632.
- Guest login dùng WordPress authentication; không tạo custom user table.

Trước khi viết custom logic cấp quyền, cần kiểm tra API/hooks/functions/capabilities của LearnPress.

## Dữ liệu test local

Đã tạo trên local database để kiểm tra luồng LMS:

- Course: C++ Cơ bản - Nền tảng lập trình (post ID 13).
- Section: Làm quen với C++ (section ID 1).
- Lesson: Biến và kiểu dữ liệu trong C++ (post ID 16), đã gắn vào section.
- User lms_student_allowed (subscriber) đã được enroll vào course để kiểm tra luồng học.
- User lms_student_pending (subscriber) chưa được enroll để kiểm tra trạng thái chưa có quyền học.

Đây là dữ liệu local phục vụ kiểm thử, không được đưa vào Git hoặc deploy sang production.

## DB change gần nhất

- Local database đã thay đổi do tạo course, section, lesson, hai user test và một enrollment LearnPress.
- Custom plugin lms-site-core không tạo custom table hoặc migration.
- Không commit database dump.


## Dữ liệu khóa học hiện tại

- Course ID 13: Giao tiếp tiếng Anh cơ bản, giữ enrollment test local hiện có.
- Course ID 33: Giao tiếp tiếng Anh nâng cao, có section và lesson mẫu.
- Course ID 34: Giao tiếp tiếng Anh miễn phí cho sinh viên, có section và lesson mẫu; meta _lms_contact_course = 1 để yêu cầu liên hệ admin qua Zalo trước khi cấp quyền.
- Modal Support hiện dùng ảnh placeholder và thông tin ngân hàng demo; chưa phải thông tin nhận tiền thật.


## Cấp quyền học thủ công

Plugin không tạo bảng dữ liệu riêng. Khi admin cấp quyền trong LearnPress > Enroll student, LearnPress tạo user-course enrollment với status enrolled; nếu enrollment đang active thì không tạo bản ghi trùng.


## Quản lý Student

Student là WordPress role có slug student. Thông tin tài khoản lưu trong wp_users/wp_usermeta. Quyền học từng course lưu trong enrollment của LearnPress; giao diện quản trị chỉ gọi model/API LearnPress, không tạo bảng riêng.

## DB change ngày 2026-09-07 — chỉ local

- Option learn_press_enable_gutenberg_lesson: no → yes. Đây là tùy chọn LearnPress native, không phải migration schema.
- Tạo lp_lesson Draft ID 57, slug lms-editor-sample, title [Mẫu local] Soạn bài bằng các khối nội dung. Chưa gắn vào curriculum và chưa publish.
- Nội dung bài dạng core blocks vẫn nằm trong post_content, cùng revisions WordPress. 7 bài published cũ được giữ nguyên nội dung.
- Integration tests tạo Student, lesson draft và enrollment tạm, sau đó xóa bằng WordPress/LearnPress APIs trong finally. Không giữ các fixture này; không gửi email trong test. Auto-increment ID có thể tăng sau test.
- Không tạo custom table; không thay đổi production; không đưa DB dump, mật khẩu hoặc session vào Git.


### Kiểm thử paste Google Sites

Tạo và lưu Lesson draft ID 59 qua wp-admin, chứa bảng IPA để đối chiếu định dạng. Chưa publish hoặc gắn curriculum. Không đổi schema/options; nội dung và revisions dùng post_content native. Không đưa nội dung private vào repository.


## Donation settings

- Option WordPress: lms_site_core_donation_settings.
- Dữ liệu gồm text hiển thị và attachment ID của ảnh QR.
- Không tạo custom table; attachment vẫn do Media Library quản lý.

- Trường zalo_phone lưu số hiển thị; URL Zalo được chuẩn hóa từ số này khi render.

## Quy tắc hiển thị CTA và giá liên hệ

- `_lms_contact_course = 1` là cờ nghiệp vụ cho khóa cần admin cấp quyền thủ công.
- Guest thấy `Xem chi tiết` và có thể mở trang khóa; user đã đăng nhập nhưng chưa có quyền đi qua luồng liên hệ.
- User đã có quyền luôn có `Tiếp tục học`; nếu chưa có lesson tiếp theo, hệ thống dùng permalink khóa học làm fallback.
- `Liên hệ` chỉ là giá hiển thị. LearnPress vẫn lưu giá numeric/free gốc để giữ tương thích checkout và dữ liệu order về sau.