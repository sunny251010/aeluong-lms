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

Workflow chỉ deploy phần wp-content do project quản lý, không deploy WordPress core hoặc docs ra root hosting. Cơ chế tự xóa file trên server được tắt vì repository không chứa toàn bộ plugin/theme/runtime của site. Child theme lms-kadence-child và custom plugin lms-site-core được track trong Git và sẽ được upload cùng wp-content, còn Kadence parent và LearnPress vẫn được quản lý như third-party code.

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

## Kích hoạt child theme và plugin

Sau khi workflow deploy thành công:

1. Trên local, kiểm tra LMS Kadence Child và LMS Site Core đang active.
2. Trên production, vào WordPress Admin -> Appearance -> Themes và active LMS Kadence Child nếu chưa active.
3. Vào Plugins và active LMS Site Core.
4. Kiểm tra homepage chuyển tới /courses/, course archive hiển thị card grid responsive; frontend menu có Login/avatar; guest thấy Xem chi tiết, học viên đã enroll thấy Tiếp tục học, tài khoản chưa có quyền thấy modal Zalo; frontend dùng Vietnamese còn wp-admin giữ English; LearnPress submenu không còn Orders, Statistics, Add-ons, Themes, Tools, Help Center; Settings vẫn hiển thị để cấu hình currency và các thiết lập khác; danh sách Pages không còn các page Checkout/Instructor.
5. Việc active theme/plugin là trạng thái database riêng của từng môi trường, không được đồng bộ bằng Git.

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
- Child theme lms-kadence-child và custom plugin lms-site-core đã có trong repository; sau khi deploy cần active thủ công trên từng môi trường.
- Homepage production chưa đổi cho tới khi plugin được active trên production.


### Smoke test bổ sung cho flow mới

- Mở /courses/ và xác nhận có ba card: cơ bản, nâng cao và miễn phí cho sinh viên.
- Click Support, xác nhận modal ngân hàng mở và đang hiển thị placeholder demo; chỉ thay ảnh/thông tin thật khi đã xác nhận dữ liệu nhận tiền.
- Logout rồi click CTA khóa học có phí: login modal mở.
- Với khóa miễn phí cho sinh viên: CTA mở modal liên hệ Zalo, không mở checkout.
- Đăng nhập tài khoản đã được cấp quyền và xác nhận CTA là Tiếp tục học.


### Dữ liệu LearnPress trên production

GitHub Actions hiện chỉ deploy source trong wp-content, không đồng bộ database local. Vì vậy ba course và lesson mẫu được tạo local chưa tự xuất hiện trên hosting.

Trên production, tạo hoặc cập nhật course bằng LearnPress > Courses. Với course miễn phí, tick checkbox Contact admin before enrollment trong hộp Course access; sau đó kiểm tra CTA mở modal Zalo.


- Kiểm tra trang Courses mở mặc định ở Grid/Card.
- Bấm nút List để xác nhận danh sách chuyển sang dạng dọc, sau đó bấm Grid để quay lại dạng card.


- Với tài khoản chưa được cấp quyền, mở trang /lp-checkout/ hoặc checkout có course_id phải chuyển về trang course và hiện modal quyền học.
- Xác nhận trang checkout vẫn tồn tại và admin không bị chặn.


- Kiểm tra header: Courses và Contact ở giữa, Donation và account ở bên phải; bấm Donation để mở bank modal.
- Kiểm tra ba course đã có ảnh card riêng, mô tả mới và khóa Cơ bản có thêm các bài học mới.
- Vào Media > Add New trên production, upload ba file trong wp-content/plugins/lms-site-core/assets/images/courses/, sau đó mở từng course và chọn ảnh tương ứng tại Featured image.
- Kiểm tra LearnPress REST/card đang trả URL /wp-content/uploads/ sau khi gán Featured image.
- Kiểm tra login modal và Donation modal rộng hơn trước 48px trên desktop, đồng thời không tràn màn hình trên mobile.


- Vào LearnPress > Enroll student, chọn học viên và khóa học, bấm Enroll student.
- Đăng nhập bằng tài khoản vừa cấp quyền và kiểm tra CTA chuyển thành Continue/ Tiếp tục học.
- Thử submit lại cùng cặp học viên/khóa học để xác nhận hệ thống báo đã có quyền, không tạo enrollment trùng.


- Vào LearnPress > Students & enrollment để tạo Student mới, tìm kiếm Student và cấp quyền theo course.
- Sau khi tạo, gửi username/password cho học viên qua kênh bảo mật; không đưa mật khẩu vào GitHub hoặc docs.
- Kiểm tra danh sách mặc định đã sắp xếp Student mới đăng ký gần nhất ở đầu bảng.
- Kiểm tra Full name và Username nằm cùng hàng trong form tạo Student; bấm Show/Hide để xác nhận mật khẩu đổi kiểu hiển thị.


### Trang Contact và form góp ý

Sau khi deploy plugin, kiểm tra trên production:

1. Vào Pages > Add New, tạo trang có title Contact, slug contact, nội dung [lms_site_contact_form], rồi Publish.
2. Vào Appearance > Menus hoặc phần quản lý menu đang dùng, đặt tab Contact trỏ tới /contact/. Nếu item hiện tại đang là taxonomy/menu archive và click bị quay về Courses, xóa item đó rồi tạo lại bằng Custom Link với URL /contact/.
3. Mở /contact/ ở chế độ logged out và logged in; kiểm tra form, danh sách khóa học và nút Zalo 0984 715 632.
4. Kiểm tra ở desktop và mobile: form chuyển thành một cột, không có horizontal overflow; page chỉ cuộn dọc khi nội dung form vượt chiều cao viewport.
5. Gửi một góp ý thử nghiệm. Xác nhận người gửi nhận thông báo thành công và email tới địa chỉ Settings > General > Administration Email. Nếu GoDaddy chưa gửi mail ra ngoài, vẫn dùng nút Zalo để liên hệ và cấu hình mail sau.

Form chỉ gửi email, không tạo bảng dữ liệu riêng. Không đưa email, mật khẩu hoặc nội dung góp ý thật vào Git.


### Cấu hình hai menu trên production

- Primary: chỉ gán menu có Courses và Contact.
- Secondary: gán menu có hai custom link Donation (#lms-support-modal) và Đăng nhập (#lms-login-modal).
- Không thêm Donation/Login thủ công vào Primary; plugin chỉ style và xử lý đúng menu location Secondary.
- Khi logged out, Login mở modal; khi logged in, item Login hiển thị avatar và display name, trỏ tới profile LearnPress nếu có.

- Với user đã đăng nhập, plugin ưu tiên learn_press_user_profile_url(); nếu LearnPress không expose function này thì dùng page có slug lp-profile, cuối cùng mới dùng #.

- Contact page production cần có slug contact; nếu nội dung page đang rỗng, plugin 0.9.2 trở lên tự render form Contact bằng fallback.
