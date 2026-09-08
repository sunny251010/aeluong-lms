# Soạn bài học và chuyển nội dung từ Google Sites

## Cách dùng trên local

1. Vào LearnPress > Lessons > Add New hoặc mở bài Draft mẫu: http://aeluong-lms.local/wp-admin/post.php?post=57&action=edit .
2. Local đã bật LearnPress > Settings > Advanced > Other > Enable gutenberg > Lesson. Nếu vẫn thấy editor cũ, tải lại trang và kiểm tra checkbox này.
3. Trong Block Editor chọn + > Patterns > Bố cục bài học. Có mẫu bài đầy đủ, hai cột nội dung/ảnh và hai cột kiến thức/ví dụ.
4. Dán từng phần nội dung vào paragraph/heading tương ứng. Dùng List View để chọn, di chuyển hoặc nhân bản cả cụm Columns.
5. Thêm Image, Gallery, Table, List, YouTube, File, Columns và Separator bằng bộ core blocks. Không cần plugin builder.
6. Save draft, xem Preview. Sau khi kiểm tra, Publish và gắn lesson vào Curriculum của course bằng LearnPress. Để dùng Block Editor đầy đủ, mở lesson tại LearnPress > Lessons; editor inline trong Course Builder có thể là giao diện riêng của LearnPress.

## Copy từ Google Sites

- Với văn bản: copy từng phần, giữ heading/list/link khi clipboard hỗ trợ; kiểm tra lại sau khi dán. Không cam kết dán một lần giữ nguyên toàn bộ layout Google Sites.
- Với bố cục hai cột: chèn pattern hai cột rồi dán từng cột; di chuyển cả Columns trong List View.
- Với ảnh: tải ảnh mà bạn được phép sử dụng xuống máy, upload vào Media Library rồi chèn. Tránh phụ thuộc vào URL ảnh Google riêng tư hoặc URL cần phiên đăng nhập.
- Với video: chèn URL vào khối YouTube. Với tài liệu: dùng File hoặc link Google Drive và kiểm tra quyền xem bằng tài khoản học viên.
- Không chuyển quyền truy cập Google Sites/Drive bằng thao tác copy. Nội dung private chưa được đọc/import trong lượt này.
- Bài cũ có thể hiện dưới dạng Classic block; lưu bản sao/bản nháp trước khi Convert to blocks và kiểm tra bố cục. Việc bật editor không tự chuyển hoặc ghi đè 7 bài cũ.

## Giới hạn hiện tại

Chưa xem được hai bài Google Sites private vì Chrome thiếu Browser extension/native connection. Chưa kiểm chứng clipboard thật, block validity trong giao diện editor, kéo thả, responsive và preview frontend của các mẫu. Bộ test đã kiểm tra đăng ký patterns, render core blocks và round-trip post_content qua REST controller; chưa thay thế visual QA.

## Khi đưa lên hosting sau này

- Deploy đầy đủ thư mục lms-site-core, bao gồm includes/lesson-authoring.php; version 0.10.0. Nếu plugin đã active thì không cần deactivate/reactivate. Nếu chưa active: Plugins > LMS Site Core > Activate, sau khi LearnPress đã được cài và active.
- Bật checkbox Gutenberg cho Lesson riêng trên môi trường đích; Git/SCP không mang option database này. Nếu migrate database có thể đã có option, vẫn cần kiểm tra.
- Patterns đi theo code. Bài mẫu và nội dung bài học đi theo database; ảnh đi theo uploads. Không import dữ liệu test vào production nếu không cần.
- Xóa cache; test editor lưu/mở lại bài, quyền học, nút Continue và mobile. Nếu rollback, khôi phục code phù hợp và tắt checkbox Gutenberg; dữ liệu core blocks đã lưu vẫn cần WordPress render.

## Tài liệu nền tảng

- LearnPress Gutenberg: https://docs.thimpress.com/learnpress/faqs/
- WordPress core block patterns: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-patterns/


## Copy giữ định dạng — kiểm thử ngày 2026-09-07

Đã đối chiếu Google Sites private qua Chrome và copy bảng IPA thật từ Preview. Paste mặc định vào Table/Classic làm mất font-size và màu. Plugin cấu hình tiny_mce_before_init riêng cho lp_lesson, giữ danh sách CSS trình bày qua paste_webkit_styles và paste_retain_style_properties; không tắt sanitizer native, không thêm dependency.

1. Google Sites: mở Preview, bôi đen phần nội dung rồi Ctrl+C. Bảng nhúng: click bên trong bảng, Ctrl+A rồi Ctrl+C chỉ trong vùng nhúng.
2. Lesson: + → tìm Classic → chọn Classic → Edit contents → click vùng soạn thảo → Ctrl+V. Không dùng Ctrl+Shift+V.
3. Chọn Save trong Classic, sau đó Save draft của Lesson. Không Convert to blocks nếu cần giữ định dạng nguồn.
4. Xem Preview và kiểm tra trên điện thoại trước khi publish.

Đã lưu và reload bản nháp ID 59: bảng 6 hàng/48 ô giữ màu đỏ, 24pt, Times New Roman/Lucida Sans Unicode, bold và căn giữa (358 phần tử có inline style). Chưa kiểm tra hiển thị frontend học viên/mobile; bố cục và khoảng cách còn phụ thuộc theme. Font-family được giữ nhưng thiết bị cần có font tương ứng; không tự tải font Google Sites.

Ảnh riêng tư nên tải về rồi Add Media để lưu vào Media Library; paste ảnh URL không tự chuyển file sang WordPress. Video/iframe copy riêng bằng link YouTube hoặc khối nhúng. Link bài Google Sites vẫn trỏ về Google Sites và vẫn chịu quyền truy cập gốc, không tự map sang Lesson. Không cam kết copy toàn bộ trang gồm các iframe chỉ bằng một Ctrl+A.
