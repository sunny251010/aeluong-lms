# Product scope

## Mục tiêu sản phẩm

AELuong LMS là website học tập cá nhân, đơn giản và dễ bảo trì.

Homepage:

- Landing page tối giản.
- Hiển thị danh sách khóa học.
- Có nút "Học ngay".
- Có liên hệ Zalo.
- Có footer cơ bản.

Người dùng:

- Xem thông tin khóa học khi chưa đăng nhập.
- Đăng nhập bằng WordPress username/password.
- Có thể dùng Google login thông qua plugin OAuth có sẵn.
- Chỉ học được course khi đã được cấp quyền.

Admin:

- Quản lý khóa học qua LearnPress.
- Cấp quyền cho từng user theo từng khóa học.

## Ngoài scope hiện tại

Không làm trong giai đoạn đầu:

- Payment.
- Certificate.
- Gamification.
- Dashboard phức tạp.
- Forum.
- Affiliate.
- Social features.
- Custom OAuth implementation.
- Custom LMS core.
- Custom page builder.

## Nguyên tắc sản phẩm

- Đơn giản trước.
- Dễ debug.
- Ít dependency.
- Tận dụng WordPress/LearnPress tối đa.
- Custom vừa đủ khi plugin/theme không xử lý sạch được.
