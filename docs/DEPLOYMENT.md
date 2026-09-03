# Deployment

## Tổng quan

Dự án dự kiến deploy từ GitHub lên GoDaddy WordPress Hosting bằng GitHub Actions.

Môi trường:

- Local development: LocalWP.
- Version control: GitHub.
- Hosting: GoDaddy WordPress Hosting.
- Deploy mechanism: GitHub Actions qua SSH.

## GoDaddy deploy action

GoDaddy cung cấp action mẫu:

`godaddy-wordpress/gd-wordpress-deployer@v1`

Thông tin deploy hiện có:

- `remote_host`: `92c.53d.myftpupload.com`
- `ssh_user`: `git_deployer_c2090012c8_816125`
- GitHub Actions secret chứa private key: `PRIVATE_KEY`

Không hardcode private key vào repo.

## Nguyên tắc workflow

Giai đoạn đầu chỉ nên dùng:

- `workflow_dispatch` để deploy thủ công.
- Không tự deploy mỗi lần push cho tới khi pipeline được kiểm chứng.
- Không deploy database.
- Không deploy uploads.
- Không bật destructive sync/delete nếu chưa được yêu cầu.
- Không ghi đè file ngoài phạm vi custom code cần thiết.

## Target path cần xác nhận

Trước khi tạo `.github/workflows/deploy.yml`, cần kiểm tra đường dẫn thật trên GoDaddy.

Điểm cần tránh:

`wp-content/wp-content/`

Ví dụ, nếu repo chỉ chứa custom code nằm dưới `wp-content/plugins/aeluong-site-core/`, workflow nên deploy đúng folder plugin vào đúng thư mục plugin trên server, không copy cả root WordPress install nếu không cần.

## GitHub secret cần có

- `PRIVATE_KEY`: SSH private key do GoDaddy cung cấp.

Không commit:

- SSH private key.
- Password.
- API secret.
- OAuth client secret.
- Database dump có dữ liệu thật.

## Trạng thái hiện tại

- Chưa tạo workflow deploy.
- Repo local đã có remote GitHub: `https://github.com/sunny251010/aeluong-lms.git`.
- Cần xác nhận repository GitHub và target path trên GoDaddy trước khi deploy.
