# Roadmap

## Phase hiện tại: Hoàn thiện Phase 1 trên local và khởi đầu Lesson authoring

- Admin Enrollment đã có danh sách Student mới đăng ký hiển thị trước; form tạo Student hỗ trợ Full name/Username theo hàng ngang và Show/Hide Password.
- Đã bổ sung bộ ảnh course, mô tả nội dung và curriculum mở rộng cho khóa Cơ bản; modal frontend tăng 48px.

Mục tiêu:

- Khởi tạo Git repo local.
- Hoàn thiện `.gitignore` để tránh commit WordPress core, runtime data, secrets và uploads.
- Tạo bộ docs nền cho architecture, data model, roadmap và deployment.
- Ghi rõ scope sản phẩm trước khi triển khai code.

Trạng thái:

- Git repo local đã được khởi tạo.
- Initial commit đã có.
- Đã có remote `origin`: `https://github.com/sunny251010/aeluong-lms.git`.
- Branch `main` đang theo dõi `origin/main`.
- Đã tạo GitHub Actions deploy thủ công theo sample GoDaddy qua `.github/workflows/deploy.yml`.
- Đã xác định đúng SSH endpoint là `1263004.us28.ssh.myftpupload.com` và kiểm chứng deploy thành công.
- Đã khóa workflow chỉ deploy `wp-content` và tắt xóa file tự động trên hosting.
- Kadence parent theme đã được cài và active ở local và production.
- Local và production đã được đồng bộ cơ bản về WordPress, PHP, Kadence, LearnPress, permalink và site title.
- Đã tạo dữ liệu LMS test thật trên local gồm course, section, lesson và hai subscriber.
- Đã tạo child theme lms-kadence-child và active trên local; production cần active sau khi deploy.
- Đã tạo và active custom plugin lms-site-core trên local; production cần active sau khi deploy. Plugin đã ẩn các mục quản trị nâng cao không cần thiết của LearnPress trong wp-admin, giữ lại Settings để cấu hình, ẩn các page phụ khỏi danh sách Pages và ẩn chúng khỏi frontend navigation.
- Đã bổ sung giao diện course archive dạng card grid trong child theme: 3 cột desktop, 2 cột tablet và 1 cột mobile.
- Đã bổ sung frontend Vietnamese, login modal bằng WordPress account, avatar account menu, CTA theo enrollment và Zalo access modal; Google OAuth để phase sau.


- Đã bổ sung action Support cạnh account menu, modal ngân hàng placeholder và modal access riêng cho khóa miễn phí cần liên hệ admin.
- Đã tạo dữ liệu local gồm ba khóa giao tiếp tiếng Anh, section/lesson mẫu và giữ LearnPress làm source of truth.

## Phase 1 - Nền tảng WordPress/LearnPress tối giản

Thứ tự đề xuất:

1. Audit repository và đối chiếu WordPress/PHP/Kadence/plugin/permalink/site URL/timezone giữa local và production.
2. Kiểm tra/cài LearnPress thủ công trong WordPress Admin nếu chưa có.
3. Tạo dữ liệu test gồm course, section, lesson và user có/không có quyền học.
4. Deploy child theme lms-kadence-child, sau đó active thủ công trên local và production; không sửa Kadence parent.
5. Deploy và active plugin lms-site-core; chỉ bổ sung business logic khi có yêu cầu thật sự cần.
6. Kiểm tra homepage chuyển tới course archive và query course từ LearnPress.
7. Hiển thị course archive dạng card grid responsive, có thumbnail, mô tả ngắn, giá và nút “Học ngay”.
8. Xử lý nút "Học ngay" theo trạng thái logged out/logged in/access và login modal.
9. Thêm Zalo contact/access modal và footer cơ bản.
10. Deploy production từng thay đổi nhỏ bằng GitHub Actions và kiểm tra smoke test.
11. Cập nhật README/docs sau mỗi thay đổi.

## Phase 2 - Lesson authoring

Mục tiêu đang bắt đầu trên local theo yêu cầu ngày 2026-09-07:

- Tối ưu trải nghiệm nhập lesson từ nội dung Google Sites.
- Ưu tiên Gutenberg/Block Editor.
- Không xây custom page builder nếu chưa thật sự cần.
- Đảm bảo editor hỗ trợ heading, paragraph, image, YouTube, link, file, list, columns và separator.

## Phase sau này

Chỉ cân nhắc khi có nhu cầu rõ:

- Payment.
- Certificate.
- Gamification.
- Dashboard nâng cao.
- Forum.
- Affiliate.
- Social features.

## Nguyên tắc roadmap

- Theo yêu cầu ngày 2026-09-07, bắt đầu nền tảng Lesson authoring song song với audit Phase 1 trên local. Chưa đánh dấu Phase 1 hoàn tất; kiểm thử trình duyệt và đối chiếu Google Sites vẫn còn chờ.
- Không deploy production hoặc dùng destructive sync/delete nếu chưa được yêu cầu rõ.
- Không thêm dependency hoặc framework lớn nếu project chưa thật sự cần.

- [x] Archive khóa học mặc định Grid/Card và vẫn cho phép người dùng chuyển sang List.

- [x] Tạm chuyển user chưa có quyền khỏi LearnPress checkout về luồng course/access modal; giữ checkout cho payment sau này.

- [x] Admin cấp quyền trực tiếp cho học viên theo từng course, không cần checkout/payment.


- [x] Role Student và màn hình admin tạo/tìm kiếm học viên, cấp quyền theo course.


- [x] Tạo trang Contact có form góp ý cho khóa học, website và hỗ trợ tài khoản; có chọn khóa học liên quan, gửi email admin và liên hệ Zalo.

## Audit và cập nhật local ngày 2026-09-07

- Ưu tiên phát triển local; chuyển production để sau theo quyết định của người dùng.
- [x] Audit HTTP/source/database: 3 course published, 7 lesson published; homepage, Courses, Contact hoạt động ở mức HTTP.
- [x] Sửa lỗ hổng tự enroll ở backend: course miễn phí yêu cầu liên hệ admin trước đây vẫn được model LearnPress cho phép tự enroll. Bổ sung kiểm tra ở modern và legacy hooks.
- [x] CTA Tiếp tục học ở archive dùng curriculum item do LearnPress chọn theo tiến độ.
- [x] Bật tùy chọn Gutenberg native riêng cho Lesson trên local.
- [x] Thêm 3 core block patterns và hướng dẫn nhập nội dung trong lesson editor; giữ bài cũ nguyên nội dung.
- [x] Tạo bài mẫu Draft ID 57, chưa đưa vào curriculum.
- [x] 30 integration checks đạt, PHP lint và JavaScript syntax đạt.
- [ ] Kiểm thử trực quan desktop/mobile, modal keyboard/focus, editor kéo thả và copy từ hai Google Sites private. Chrome chưa có kết nối Browser hoạt động.
- [ ] Đối chiếu nguồn Google Sites rồi tinh chỉnh thêm mẫu bài theo nội dung thật; chưa thực hiện import.
- [ ] Donation vẫn là dữ liệu ngân hàng demo; thay trước khi nhận tiền thật.
- [ ] Cân nhắc phân trang Students khi vượt 50 tài khoản; hiện query giới hạn 50, chưa có pagination.

Chi tiết: [audit local](LOCAL_ROADMAP_AUDIT.md), [hướng dẫn soạn bài](LESSON_AUTHORING.md).
