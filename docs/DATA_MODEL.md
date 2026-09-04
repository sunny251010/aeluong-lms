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
- User đã đăng nhập nhưng chưa có quyền thì thấy thông báo chưa được cấp quyền.

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
- Không tạo custom table và không commit database dump.
