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
- `remote_host`: `1263004.us28.ssh.myftpupload.com` (hostname trong mục SSH/SFTP login của GoDaddy).
- `ssh_user`: `git_deployer_d93c635f6a_1263004`.
- Secret đang dùng trong repo: `PRIVATE_KEY`.
- Workflow chỉ chạy thủ công bằng `workflow_dispatch`.
- `source_path`: `wp-content`.
- `deployment_dest`: `wp-content`.
- `cleanup_deleted_files`: `no`.

Workflow chỉ deploy phần `wp-content` do project quản lý, không deploy WordPress core hoặc docs ra root hosting. Cơ chế tự xóa file trên server được tắt vì repository không chứa toàn bộ plugin/theme/runtime của site. Child theme lms-kadence-child được track trong Git và sẽ được upload cùng wp-content, còn Kadence parent và LearnPress vẫn được quản lý như third-party code.

GoDaddy từng sinh sample với hostname website `1263004.us28.myftpupload.com`, nhưng hostname đó không phải SSH endpoint. Workflow phải dùng hostname hiển thị trong mục `SSH/SFTP login`, có segment `.ssh.`.

## Điều kiện SSH/SFTP trên GoDaddy

Trước khi chạy workflow:

1. Vào GoDaddy -> Managed WordPress -> Settings -> Production Site -> SSH/SFTP login.
2. Chọn `Tạo đăng nhập mới` và bật SSH.
3. Xác nhận hostname là `1263004.us28.ssh.myftpupload.com` và port là `22`.

Việc tạo login mới sẽ vô hiệu hóa thông tin SFTP cũ. Username/password SFTP mới không dùng trong workflow này; workflow vẫn xác thực bằng deploy user và `PRIVATE_KEY` của CI/CD integration.

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
5. Workflow tự dùng `wp-content` làm source và destination, không cần nhập target.
6. Xem log của step `Synchronize Files with GoDaddy WordPress Hosting Server`.

## Test CI/CD bằng marker file

File marker `wp-content/aeluong-deploy-test.txt` đã được dùng để xác nhận pipeline và đã xóa sau khi kiểm thử thành công. Khi cần kiểm tra lại, có thể tạo một marker tạm trong `wp-content`, deploy, kiểm tra URL rồi xóa marker sau đó.

Vì workflow hiện chỉ deploy `wp-content`, URL kiểm tra sẽ có dạng:

`https://1263004.us28.myftpupload.com/wp-content/<ten-file>`

## Kích hoạt child theme

Sau khi workflow deploy thành công:

1. Vào WordPress Admin của local, mở Appearance -> Themes.
2. Active LMS Kadence Child, rồi kiểm tra homepage và một course.
3. Thực hiện thao tác tương tự trên production khi sẵn sàng.
4. Việc active theme là trạng thái database riêng của từng môi trường, không được đồng bộ bằng Git.

## Nguyên tắc an toàn

- Không deploy database.
- Không deploy uploads.
- Không hardcode secret.
- Không tự chạy deployment production từ local.
- Không chỉnh WordPress core trong repo.

## Trạng thái hiện tại

- Đã đổi `remote_host` sang đúng SSH/SFTP hostname do GoDaddy hiển thị.
- Cần bật SSH trong GoDaddy trước khi chạy lại workflow.
- Repo local đã có remote GitHub: `https://github.com/sunny251010/aeluong-lms.git`.
- Child theme lms-kadence-child đã có trong repository; sau khi deploy cần active thủ công trên từng môi trường.
- Chưa có custom plugin/homepage để làm thay đổi giao diện hosting.
