# Deployment

## Tổng quan

Dự án deploy từ GitHub lên GoDaddy WordPress Hosting bằng GitHub Actions qua SSH/rsync.

Môi trường:

- Local development: LocalWP.
- Version control: GitHub.
- Hosting: GoDaddy WordPress Hosting.
- Deploy mechanism: GitHub Actions qua SSH và rsync.

## Thông tin GoDaddy SSH

Thông tin deploy hiện tại:

- `remote_host`: `1263004.us28.myftpupload.com`
- `ssh_user`: `git_deployer_9c8da1f525_1263004`
- GitHub Actions secret chứa private key: `PRIVATE_KEY`

Không hardcode private key vào repo. Public key đã được add vào GoDaddy. Private key phải nằm trong GitHub repository secret `PRIVATE_KEY`.

## Workflow hiện tại

File workflow:

`.github/workflows/deploy.yml`

Workflow chỉ chạy thủ công bằng `workflow_dispatch`, không tự deploy khi push.

Input mặc định:

- `source_path`: `wp-content`
- `deployment_dest`: `/html/wp-content`

Workflow hiện dùng rsync trực tiếp thay vì `godaddy-wordpress/gd-wordpress-deployer@v1` vì GoDaddy composite action đang fail ở sub-step `Create Env variables` và không log rõ nguyên nhân. Cách rsync trực tiếp vẫn dùng SSH user/key do GoDaddy cấp, nhưng tách từng bước để debug rõ hơn.

Các bước chính:

- Checkout code từ GitHub.
- Cài `rsync` và `openssh-client`.
- Ghi private key từ GitHub secret vào file tạm trên runner.
- Lấy SSH host key bằng `ssh-keyscan`.
- Preflight DNS, port 22 và SSH authentication.
- Rsync nội dung `wp-content/` lên `/html/wp-content/`.
- Verify marker file sau deploy.

## Nguyên tắc an toàn

- Deploy thủ công bằng GitHub Actions trước.
- Không deploy database.
- Không deploy uploads.
- Không hardcode secret.
- Không dùng `--delete`, nên workflow không xóa file trên server khi file bị xóa khỏi repo.
- Không ghi đè WordPress core.

## Test CI/CD bằng marker file

Repo có file marker:

`wp-content/aeluong-deploy-test.txt`

Sau khi commit, push và chạy workflow, mở URL sau trên domain hosting:

`https://1263004.us28.myftpupload.com/wp-content/aeluong-deploy-test.txt`

Nếu thấy nội dung `AELuong deploy test` thì CI/CD đã deploy được file từ GitHub lên GoDaddy.

File này chỉ dùng để test pipeline ban đầu. Sau khi CI/CD ổn định, có thể xóa file marker này ở một commit riêng.

## GitHub secret cần có

Trong GitHub repository, vào `Settings` -> `Secrets and variables` -> `Actions` -> `New repository secret`:

- Name: `PRIVATE_KEY`
- Value: nội dung private key tương ứng với public key đã add vào GoDaddy.

Không đưa private key vào file trong repo.

## Khi nào đổi deployment_dest?

Chỉ đổi khi đã kiểm tra chắc path thật trên server.

Với workflow rsync trực tiếp hiện tại:

- `deployment_dest = /html/wp-content` nghĩa là deploy vào đúng thư mục WordPress content trên GoDaddy.
- Không nhập `wp-content/wp-content`.
- Không để trống nếu `source_path` vẫn là `wp-content`, vì như vậy có thể sync nội dung `wp-content` vào sai thư mục.

## Trạng thái hiện tại

- Đã tạo workflow deploy thủ công bằng rsync trực tiếp.
- Repo local đã có remote GitHub: `https://github.com/sunny251010/aeluong-lms.git`.
- Chưa deploy database hoặc uploads.
