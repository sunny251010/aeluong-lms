# Deployment

## Tổng quan

Dự án deploy từ GitHub lên GoDaddy WordPress Hosting bằng GitHub Actions và workflow sample do GoDaddy cung cấp.

Môi trường:

- Local development: LocalWP.
- Version control: GitHub.
- Hosting: GoDaddy WordPress Hosting.
- Deploy mechanism: GoDaddy GitHub Action qua SSH/rsync.

## Workflow hiện tại

File workflow:

`.github/workflows/deploy.yml`

Workflow đang bám sát sample GoDaddy cung cấp trong CI/CD panel:

- Action: `godaddy-wordpress/gd-wordpress-deployer@v1`.
- `remote_host`: `1263004.us28.myftpupload.com`.
- `ssh_user`: `git_deployer_9c8da1f525_1263004`.
- Secret đang dùng trong repo: `PRIVATE_KEY`.
- Workflow chỉ chạy thủ công bằng `workflow_dispatch`.
- Input `deployment_dest` mặc định để trống đúng theo sample GoDaddy.

Lưu ý: GoDaddy sample dùng `secrets.PRIVATE_KEY`. Repo này đang dùng tên rõ nghĩa hơn là `secrets.PRIVATE_KEY`, nên chỉ khác đúng tên secret.

## GitHub secret cần có

Trong GitHub repository, vào `Settings` -> `Secrets and variables` -> `Actions` -> `New repository secret`:

- Name: `PRIVATE_KEY`
- Value: nội dung private key tương ứng với public key đã add vào GoDaddy.

Không đưa private key vào file trong repo.

## Cách chạy deploy thủ công

1. Commit và push workflow lên GitHub branch `main`.
2. Vào tab `Actions`.
3. Chọn workflow `Deploy WordPress to GoDaddy via rsync`.
4. Bấm `Run workflow`.
5. Lần đầu để `deployment_dest` trống đúng theo sample GoDaddy.
6. Xem log của step `Synchronize Files with GoDaddy WordPress Hosting Server`.

## Test CI/CD bằng marker file

Repo có file marker:

`wp-content/aeluong-deploy-test.txt`

Sau khi deploy thành công, nếu GoDaddy action deploy root repo vào WordPress root, marker sẽ nằm ở:

`https://1263004.us28.myftpupload.com/wp-content/aeluong-deploy-test.txt`

Nếu không thấy file, cần đọc log deploy để xác định GoDaddy action đã sync source/destination nào.

## Nguyên tắc an toàn

- Không deploy database.
- Không deploy uploads.
- Không hardcode secret.
- Không tự chạy deployment production từ local.
- Không chỉnh WordPress core trong repo.

## Trạng thái hiện tại

- Đã quay lại workflow theo sample GoDaddy.
- Repo local đã có remote GitHub: `https://github.com/sunny251010/aeluong-lms.git`.
- Chưa có custom theme/plugin/homepage để làm thay đổi giao diện hosting.
