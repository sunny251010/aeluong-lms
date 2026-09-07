# Audit roadmap local — 2026-09-07

## Phạm vi

Đọc AGENTS.md, ARCHITECTURE, DATA_MODEL, ROADMAP và source custom plugin/LearnPress; kiểm tra PHP CLI, database, HTTP frontend và integration tests. Chỉ làm trên aeluong-lms.local. Không truy cập/chỉnh production trong lượt này. Không commit/push.

## Kết quả và việc cần làm

| Mục roadmap | Kết quả | Hành động |
| --- | --- | --- |
| Homepage/Courses/Contact | HTTP 200, homepage redirect /courses/ | Còn visual QA desktop/mobile |
| Dữ liệu LMS | 3 course published, 7 lesson published, featured images trong uploads | Giữ nguyên dữ liệu cũ |
| Cấp quyền thủ công | Trước sửa, CourseModel::can_enroll trả true cho Student chưa có quyền ở course 34 dù meta contact=1 | Đã chặn modern/legacy enrollment hooks trong custom plugin |
| Endpoint enroll | Route local đang bật là /learnpress/v1/courses/enroll; controller gọi model LearnPress | Integration test xác nhận bị từ chối, không tạo enrollment |
| Admin cấp quyền | Native enrollment và repeated grant | Đạt; không tạo bản ghi trùng |
| Continue từ archive | Trước sửa luôn trỏ course permalink | Đã dùng curriculum item do LearnPress chọn theo progress |
| Login | AJAX login bằng Student tạm | Đạt; click modal và keyboard chưa test trực quan |
| Lesson editor | Native Gutenberg trước sửa là no | Đã bật yes, thêm 3 patterns và metabox hướng dẫn |
| Nội dung Google Sites | Hai link editor riêng tư do người dùng cung cấp | Chưa đọc được vì Chrome chưa kết nối; chưa import nội dung |
| Contact gửi thư | Nonce/honeypot và wp_mail có trong code | Không gửi email trong audit; cần kiểm chứng riêng khi được yêu cầu |
| Donation | Ảnh và thông tin ngân hàng demo | Cần thay trước khi dùng thật |
| Students list | Query chỉ lấy 50 Student, chưa có pagination | Cải thiện khi cần quản lý nhiều hơn 50 tài khoản |
| Modal accessibility | Code chỉ xử lý Escape, chưa thấy focus trap/khôi phục focus | Cần kiểm thử bàn phím và cải thiện trong lượt UI |
| Môi trường PHP CLI | PHP 8.3.29, WordPress báo 7.1 | CLI có cảnh báo nạp php_imagick.dll; tests vẫn chạy. Chưa kết luận runtime web có cùng lỗi |

## Thiết kế đã áp dụng

- LearnPress tiếp tục là nguồn dữ liệu enrollment/progress. Không thêm custom table hay thay core/parent theme.
- Trong giai đoạn cấp quyền thủ công, guest/Student chưa có quyền bị chặn khi model kiểm tra self-enrollment; admin vẫn dùng màn hình Students & enrollment để cấp quyền. Native purchase dựa trên eligibility nên cũng không cho tài khoản chưa có quyền đi qua flow checkout.
- Nếu user chưa có quyền, Continue resolver chỉ trả course permalink. Có enrollment thì dùng get_item_continue và get_item_link. Admin không có enrollment và course không có item dùng course permalink làm fallback.
- Patterns dùng core heading, paragraph, separator, columns, column và image; giới hạn lp_lesson. Không tự chuyển nội dung bài cũ, không ép REST lesson thành public.

## Validation

- PHP lint: lms-site-core.php, includes/lesson-authoring.php và tests/local-roadmap-smoke.php.
- JavaScript syntax: node --check wp-content/plugins/lms-site-core/assets/js/lms-site-core.js.
- tests/local-roadmap-smoke.php: 30 checks đạt; tạo rồi dọn Student, lesson và enrollment tạm trong finally, chặn wp_mail. Gọi REST enroll trong WordPress runtime và AJAX login qua HTTP local; kiểm tra native REST controller lưu block content rồi đọc lại không đổi.
- API course trả 3 course ID 34, 33, 13.
- Guest GET /wp-json/wp/v2/lp_lesson trả 404 sau khi bật Gutenberg; GET /wp-json/learnpress/v1/lessons/16 trả 401. Lesson HTML trả 200 không tự chứng minh nội dung bị khóa đúng; còn kiểm tra hiển thị guest/student bằng trình duyệt.
- Chưa kiểm chứng hình ảnh, layout responsive, block validity ở client editor, clipboard Google Sites và frontend lesson Preview.
- Chạy git diff --check, git diff, git status trước khi bàn giao.

Lệnh chạy test từ thư mục public, thay đường dẫn PHP/php.ini nếu LocalWP đổi runtime:

```powershell
& 'C:/Users/Sunny/AppData/Roaming/Local/lightning-services/php-8.3.29+1/bin/win64/php.exe' -c 'C:/Users/Sunny/AppData/Roaming/Local/run/HL6cUsVDw/conf/php/php.ini' tests/local-roadmap-smoke.php
```

Script test chỉ cho phép hostname aeluong-lms.local, dựa vào account admin và course test 34 hiện có. Không đưa script này vào workflow deploy hoặc chạy production.

## File/DB/route

- Sửa lms-site-core.php; thêm includes/lesson-authoring.php và tests/local-roadmap-smoke.php.
- Cập nhật ARCHITECTURE.md, DATA_MODEL.md, ROADMAP.md, DEPLOYMENT.md; thêm LESSON_AUTHORING.md và báo cáo này.
- style.css và ENVIRONMENT_SYNC_AUDIT.md là thay đổi đã có trước lượt này, được giữ nguyên. DEPLOYMENT.md giữ cả thay đổi trước đó.
- DB local: learn_press_enable_gutenberg_lesson=yes và Draft lesson ID 57 (lms-editor-sample), chưa gắn curriculum. Fixture tạm đã dọn.
- Không thêm route custom; sử dụng hooks và editor/REST native đã có.
- Hướng dẫn active/deploy/smoke test hosting nằm trong LESSON_AUTHORING.md; hiện chưa deploy.

## Kiểm thử trực quan tiếp theo

1. Cài lại Browser plugin trong ứng dụng và kết nối Chrome đã đăng nhập Google Sites. Chẩn đoán hiện báo thiếu extension và native-host connection; không thay đổi quyền private của tài liệu.
2. Mở Draft ID 57, thử chèn 3 patterns, sửa/lưu/mở lại; kiểm tra List View và ảnh.
3. Copy từng đoạn của bài mẫu Google Sites, kiểm tra heading, list, link, table, ảnh và tài liệu riêng tư.
4. Preview ở desktop/mobile; thử Continue bằng Student có quyền, guest và Student chưa có quyền.

Gợi ý commit: Sửa luồng cấp quyền và bổ sung Block Editor cho bài học.
