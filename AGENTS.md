Trước khi code:
- Đọc docs/ARCHITECTURE.md
- Đọc docs/DATA_MODEL.md
- Đọc phase hiện tại trong docs/ROADMAP.md
- ko có thì các file bên trên thì hãy tạo ra dựa vào các promt của tôi nhé, mỗi lần làm thì update thêm
- Đọc rõ promt chỉ dẫn của tôi, nếu bạn thấy không ổn chỗ nào thì cứ hỏi lại tôi cho chắc, nhưng thứ trong promt tôi viết cũng chỉ là đề xuất nếu bạn tìm dc 1 cách khác tốt hơn
- Đọc rõ prompt và phân loại yêu cầu trước khi code. Nếu có từ hai hướng triển khai hợp lý, liên quan dependency, bảo mật, dữ liệu production hoặc dịch vụ bên thứ ba, phải hỏi lại trước khi sửa.
- Trước khi hỏi, trình bày ngắn gọn các phương án khả thi, ưu điểm, nhược điểm, ảnh hưởng đến DB/deploy và đề xuất phương án phù hợp.
- Với yêu cầu có thể dùng plugin có sẵn, phải kiểm tra khả năng dùng plugin trước; nêu rõ plugin nào, nguồn/độ tin cậy, phạm vi bản miễn phí, dependency và rủi ro. Không tự cài hoặc active plugin khi người dùng chưa chốt.
- Chỉ tiếp tục code sau khi người dùng đã chọn phương án hoặc xác nhận đề xuất. Nếu yêu cầu đã chỉ rõ phương án duy nhất và không có rủi ro đáng kể thì có thể thực hiện luôn.
- Khi người dùng đổi hướng, dừng phần đang làm, kiểm tra thay đổi chưa commit và hoàn tác riêng phần do Codex vừa tạo nếu phần đó không còn phù hợp; không đụng vào thay đổi có sẵn của người dùng.
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
