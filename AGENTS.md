Trước khi code:
- Đọc docs/ARCHITECTURE.md
- Đọc docs/DATA_MODEL.md
- Đọc phase hiện tại trong docs/ROADMAP.md
- ko có thì các file bên trên thì hãy tạo ra dựa vào các promt của tôi nhé, mỗi lần làm thì update thêm
- Đọc rõ promt chỉ dẫn của tôi, nếu bạn thấy không ổn chỗ nào thì cứ hỏi lại tôi cho chắc, nhưng thứ trong promt tôi viết cũng chỉ là đề xuất nếu bạn tìm dc 1 cách khác tốt hơn
- Cần thiết thì cứ tạo datatest rồi tự test như người thật được nhé, adm,acc admin: admin, pass: admin123

Không được:
- sửa WordPress core
- sửa Kadence parent theme
- nhét LMS logic vào child theme
- tự thêm dependency nếu chưa cần
- tự thay đổi kiến trúc đã chốt
- tự commit/push nếu prompt không yêu cầu


Khi code: 
- nhớ commit bằng tiếng việt nhé, từ chuyên ngành thì giữ nguyên 
- Giải thích lý do vì sao làm vậy?
- Hãy tự tạo data để tự test, làm như 1 người dùng thật vào wp-admin, hoặc truy cập local xem website, làm sao cho nó ổn nhất có thể

Sau khi code:
- báo cáo bằng tiếng việt nhé, các file trong folder docs hoặc file liên quan cũng trả lời bằng tiếng việt, đương nhiên dùng tiếng anh cho mấy từ khóa, từ chuyên ngành k thay thế dc hoặc cmt trong code cũng sẽ dùng tiếng anh.
- chạy validation
- git diff
- git status
- báo file thay đổi
- báo DB change
- báo route mới
- báo cách test
- Hướng dẫn tôi tự active (plugins hoặc cái khác)trên wp-admin nếu có
- Nếu thay đổi có liên quan đến deploy, theme, plugin hoặc cấu hình môi trường, sau khi hoàn tất phải hướng dẫn tôi thao tác tương ứng trên hosting/production, gồm deploy, active và smoke test nếu cần
- Tự động cập nhật thêm các file trong folder docs khi thay đổi
- gợi ý commit github
