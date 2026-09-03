# Deployment

## Tổng quan

Dự án deploy từ GitHub lên GoDaddy WordPress Hosting bằng GitHub Actions và GoDaddy rsync deploy action.

Môi trường:

- Local development: LocalWP.
- Version control: GitHub.
- Hosting: GoDaddy WordPress Hosting.
- Deploy mechanism: GitHub Actions qua SSH.

## GoDaddy deploy action

Workflow dùng:

`godaddy-wordpress/gd-wordpress-deployer@v1`

Thông tin deploy hiện tại:

- `remote_host`: `1263004.us28.myftpupload.com`
- `ssh_user`: `git_deployer_9c8da1f525_1263004`
- GitHub Actions secret chứa private key: `PRIVATE_KEY`

Không hardcode private key vào repo. Public key đã được add vào GoDaddy. Private key phải nằm trong GitHub repository secret `PRIVATE_KEY`.

## Workflow hiện tại

File workflow:

`.github/workflows/deploy.yml`

Workflow chỉ chạy thủ công bằng `workflow_dispatch`, không tự deploy khi push.

Trước khi gọi GoDaddy deploy action, workflow có bước `Preflight GoDaddy SSH connection` để kiểm tra rõ từng điểm dễ lỗi:

- GitHub secret `PRIVATE_KEY` có tồn tại.
- DNS của GoDaddy host resolve được.
- Cổng SSH 22 mở.
- `ssh-keyscan` lấy được host key.
- SSH authentication hoạt động với deploy user.


Input mặc định:

- `source_path`: `wp-content`
- `deployment_dest`: `wp-content`
- `enable_health_check`: `yes`

Lý do dùng `source_path = wp-content` và `deployment_dest = wp-content`:

- Repo không track WordPress core.
- Repo có docs và README, không cần deploy lên web root.
- Nội dung bên trong local `wp-content/` sẽ được sync vào `/html/wp-content/` trên GoDaddy.
- Tránh lỗi lồng path kiểu `wp-content/wp-content/`.

## Nguyên tắc an toàn

- Deploy thủ công bằng GitHub Actions trước.
- Không deploy database.
- Không deploy uploads.
- Không hardcode secret.
- `cleanup_deleted_files` đang để `no`, không xóa file trên server khi file bị xóa khỏi repo.
- Không ghi đè WordPress core.

## Cách chạy deploy thủ công

1. Push code lên GitHub branch `main`.
2. Vào GitHub repository: `https://github.com/sunny251010/aeluong-lms`.
3. Vào tab `Actions`.
4. Chọn workflow `Deploy WordPress to GoDaddy via rsync`.
5. Bấm `Run workflow`.
6. Giữ input mặc định trong lần test đầu:
   - `source_path`: `wp-content`
   - `deployment_dest`: `wp-content`
   - `enable_health_check`: `yes`
7. Chạy workflow và xem log.

## GitHub secret cần có

Trong GitHub repository, vào `Settings` -> `Secrets and variables` -> `Actions` -> `New repository secret`:

- Name: `PRIVATE_KEY`
- Value: nội dung private key tương ứng với public key đã add vào GoDaddy.

Không đưa private key vào file trong repo.

## Khi nào đổi deployment_dest?

Chỉ đổi khi đã kiểm tra chắc path thật trên server.

GoDaddy action hiểu `deployment_dest` là đường dẫn tương đối dưới `/html`. Vì vậy:

- `deployment_dest = wp-content` nghĩa là deploy vào `/html/wp-content`.
- Không nhập `/html/wp-content`.
- Không nhập `wp-content/wp-content`.
- Không để trống nếu `source_path` vẫn là `wp-content`, vì như vậy có thể sync nội dung `wp-content` vào web root.

## Trạng thái hiện tại

- Đã tạo workflow deploy thủ công.
- Repo local đã có remote GitHub: `https://github.com/sunny251010/aeluong-lms.git`.
- Chưa chạy deployment production trong máy local.