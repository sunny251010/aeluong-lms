# Roadmap

## Phase hiện tại: Phase 0 - Chuẩn bị source control và tài liệu

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
- Chưa cấu hình GitHub Actions deploy.

## Phase 1 - Nền tảng WordPress/LearnPress tối giản

Thứ tự đề xuất:

1. Audit repository.
2. Kiểm tra/cài LearnPress thủ công trong WordPress Admin nếu chưa có.
3. Chọn hướng giao diện: custom theme hoặc child theme.
4. Chỉ tạo plugin `lms-site-core` khi có business logic thật sự cần.
5. Tạo homepage tối giản, query course từ LearnPress.
6. Xử lý nút "Học ngay" theo trạng thái logged out/logged in/access.
7. Thêm Zalo contact và footer cơ bản.
8. Tạo GitHub Actions deploy thủ công bằng `workflow_dispatch` sau khi xác nhận target path trên GoDaddy.
9. Cập nhật README/docs sau mỗi thay đổi.

## Phase 2 - Lesson authoring

Mục tiêu tương lai:

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

- Không triển khai Phase 2 khi chưa hoàn thành và kiểm chứng Phase 1.
- Không deploy production hoặc dùng destructive sync/delete nếu chưa được yêu cầu rõ.
- Không thêm dependency hoặc framework lớn nếu project chưa thật sự cần.
