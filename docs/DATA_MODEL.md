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

## DB change gần nhất

- Không có thay đổi database.
