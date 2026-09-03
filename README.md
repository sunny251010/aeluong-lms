# AELuong LMS

Website học tập đơn giản dùng WordPress, phát triển local bằng LocalWP, version-control bằng Git/GitHub và dự kiến deploy lên GoDaddy WordPress Hosting bằng GitHub Actions.

## Kiến trúc

- WordPress core không được sửa trực tiếp.
- LMS dùng LearnPress làm nền tảng chính.
- Custom code chỉ nên nằm trong `wp-content`.
- Business logic riêng nên nằm trong custom plugin, ví dụ `wp-content/plugins/aeluong-site-core/`.
- Giao diện nên nằm trong custom theme hoặc child theme.
- Không nhét LMS business logic vào theme.

## Local setup

1. Mở project bằng LocalWP.
2. Đảm bảo WordPress chạy được ở local.
3. Cài/cấu hình plugin cần thiết trong WordPress Admin.
4. Không commit `wp-config.php`, `.htaccess`, uploads, cache hoặc database dump.

Tài khoản test local hiện có theo ghi chú dự án:

- Username: `admin`
- Password: `admin123`

## Plugin dependencies

Plugin bắt buộc/định hướng:

- LearnPress: quản lý courses, sections, lessons, enrollment/access và progress.
- Google login plugin: dùng plugin OAuth có sẵn, không tự implement OAuth.

Hiện repo chưa track LearnPress hoặc Google login plugin. Nếu cài qua WordPress Admin, cần ghi rõ trong docs trước khi deploy production.

## Deployment

Deployment dự kiến dùng GitHub Actions với GoDaddy action:

`godaddy-wordpress/gd-wordpress-deployer@v1`

Nguyên tắc:

- Deploy thủ công bằng `.github/workflows/deploy.yml` và `workflow_dispatch` trước.
- Dùng GitHub secret `PRIVATE_KEY`.
- Không deploy database.
- Không deploy uploads.
- Không hardcode private key hoặc secrets.
- Chưa bật destructive sync/delete. Workflow đang dùng `cleanup_deleted_files: no`.

Xem thêm: `docs/DEPLOYMENT.md`.

## Không được commit

- `wp-config.php`.
- `.env`, `.env.*`.
- SSH private key.
- Password/API secret/OAuth client secret.
- Database dump có dữ liệu thật.
- `wp-content/uploads/`.
- Cache, backup, log.
- WordPress core files.

## Tài liệu dự án

- `docs/ARCHITECTURE.md`
- `docs/DATA_MODEL.md`
- `docs/ROADMAP.md`
- `docs/DEPLOYMENT.md`
- `docs/PRODUCT_SCOPE.md`
