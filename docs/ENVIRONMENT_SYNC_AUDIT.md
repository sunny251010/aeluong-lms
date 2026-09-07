# Đối chiếu local và production — 2026-09-06

## Phạm vi và trạng thái

Đã đọc ARCHITECTURE, DATA_MODEL, ROADMAP, DEPLOYMENT, workflow và git diff; gửi HTTP GET tới homepage, /courses/ và /contact/ của hai môi trường. Chưa kiểm tra trực quan, mobile, JavaScript hoặc đăng nhập vì chưa có trình duyệt kết nối. Chưa thực hiện đồng bộ production. Người dùng xác nhận production hiện chỉ có dữ liệu thử nghiệm; chưa yêu cầu xóa hoặc ghi đè toàn bộ database.

## Kết quả xác nhận

| Hạng mục | Local | Production |
| --- | --- | --- |
| Homepage | HTTP 200 sau redirect /courses/ | HTTP 200 sau redirect /courses/ |
| Kadence CSS version | 1.5.2 | 1.5.2 |
| LearnPress asset version | 4.4.6 | 4.4.6 |
| LMS Site Core script version | 0.9.2 | 0.9.2 |
| LMS Kadence Child CSS version | 0.6.2 | 0.6.1 |
| Header background từ Kadence | #bbe1e1 | #c0d5e9 |
| Global palette 7 | #4178ad | #EDF2F7 |
| Global palette 8 | #d1d6d9 | #F7FAFC |
| Global palette 9 | #f6f6f6 | #ffffff |
| Content background từ Kadence | #ffffff | var(--global-palette9) |
| Menu header | Courses, Contact, Donation, Đăng nhập | Cùng các mục; Courses/Contact dùng URL tương đối hợp lệ |
| Contact | page ID 49; có markup form | page ID 15; có markup form |
| CSS Site Designer | Không thấy các style ID tương ứng | Có site-designer-shared-pattern-classes-inline-css, wp-site-designer-contrast-fallback, site-designer-logo-constraints |

Version trong HTML là version asset được trả về, không chứng minh toàn bộ source hoặc dependency giống nhau. CSS Site Designer là khác biệt đã xác nhận, chưa đủ bằng chứng để kết luận là nguyên nhân lỗi hoặc tự tắt plugin. Hai cách khai báo content background hiện đều ra màu trắng theo palette tương ứng.

## Nguyên nhân và giới hạn deploy

Workflow chỉ chạy thủ công bằng workflow_dispatch, checkout source trên GitHub rồi đồng bộ wp-content; cleanup_deleted_files đang là no. Git ignore database, uploads, WordPress core và third-party plugins/themes. Menu, Customizer, trang, khóa học và trạng thái active không được đưa lên cùng code.

Trước lượt kiểm tra đã có thay đổi chưa commit ở docs/DEPLOYMENT.md và wp-content/themes/lms-kadence-child/style.css. CSS tăng từ 0.6.1 lên 0.6.2 và bổ sung rule Contact. GitHub Actions chưa thể lấy phần sửa chưa commit/push này. Commit local được kiểm tra: c8fef61 — Thêm fallback render cho trang Contact. Chưa đối chiếu commit của lần Actions chạy gần nhất.

## Quy trình đồng bộ đề xuất

1. Kết nối trình duyệt, đăng nhập wp-admin production để kiểm kê dữ liệu hiện có (người dùng đã xác nhận chỉ có dữ liệu thử nghiệm).
2. Đối chiếu lần Actions chạy gần nhất với source mong muốn. Review phần CSS chưa commit; chủ dự án commit/push rồi chạy workflow khi sẵn sàng.
3. Trong Appearance > Themes và Plugins, xác nhận LMS Kadence Child, Kadence parent, LMS Site Core, LearnPress. HTML hiện cho thấy child theme và custom plugin đang tham gia render trên cả hai môi trường; không cần mặc định kích hoạt lại.
4. Trong Appearance > Customize, đối chiếu Global Palette và Header background theo bảng trên; kiểm tra header layout, typography, footer và menu ở desktop/mobile. Sao lưu giá trị production trước khi chỉnh.
5. Trong Plugins và mục Must-Use nếu có, xác định thành phần sinh Site Designer CSS và các chức năng nó đang phục vụ. Chỉ quyết định cấu hình hoặc vô hiệu hóa sau khi xác định tác động.
6. Kiểm kê course, curriculum, lesson, page và media. Nếu cần chuyển nội dung, dùng cơ chế tương thích LearnPress và ánh xạ ID tại môi trường đích; không chép ID local trực tiếp. Chưa xác nhận nội dung khóa học render bằng AJAX trong đợt này.
7. Xóa cache hosting qua công cụ sẵn có sau khi deploy/cập nhật cấu hình, rồi kiểm tra trang ở phiên guest mới.
8. Về lâu dài, tiếp tục dùng Actions cho code; quản lý thay đổi Customizer/menu bằng checklist hoặc gói cấu hình có phạm vi rõ ràng. Nội dung thật nên có nguồn quản lý thống nhất. Không thiết lập ghi đè database production từ local trong mỗi lần deploy.

## Smoke test sau khi đồng bộ

- Homepage chuyển tới /courses/; CSS child theme trả đúng version/source cần deploy.
- Desktop và mobile: màu sắc, header, footer, card grid và Contact; không tràn ngang.
- Menu Courses/Contact mở đúng trang; Donation và Login mở modal.
- Contact có input/select/textarea hiển thị đúng. Không gửi email thử nếu chưa được người dùng cho phép.
- Course tải đủ qua AJAX, thumbnail không lỗi và không chứa domain local trên production.
- Kiểm tra bằng tài khoản test: guest, chưa cấp quyền và đã cấp quyền; không tạo bản ghi enrollment trùng.

## Thay đổi của lượt audit

Chỉ thêm báo cáo này và liên kết trong DEPLOYMENT.md. Không sửa code, không thay đổi database, không thêm route, không commit/push hoặc deploy. Không cần active plugin cho tài liệu. Cần hoàn tất kiểm tra trình duyệt/wp-admin trước khi coi hai môi trường đã đồng bộ.
