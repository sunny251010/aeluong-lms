# Kien truc du an

## Tong quan

Day la du an WordPress local cho AELuong LMS. Repo duoc khoi tao theo huong chi track tai lieu va custom code cua du an, khong track WordPress core, file config local, uploads, cache hoac generated artifacts.

## Ranh gioi kien truc

- Khong sua WordPress core (`wp-admin`, `wp-includes`, cac file `wp-*.php` o root).
- Khong sua Kadence parent theme neu sau nay du an cai Kadence.
- Khong nhet LMS logic vao child theme. LMS logic nen nam trong plugin rieng cua du an.
- Custom plugin/theme sau nay se nam trong `wp-content/plugins/` hoac `wp-content/themes/` va duoc track co chon loc.
- File moi truong local nhu `wp-config.php`, `.htaccess`, uploads va cache khong dua len Git.

## Trang thai hien tai

- Chua co custom LMS plugin/theme trong source tree.
- `wp-content` hien chi co plugin index va cac theme mac dinh cua WordPress.
- Git duoc chuan bi de track phan custom thay vi track toan bo WordPress install.

## Ghi chu van hanh

- Khi can dua website sang moi truong khac, can backup/migrate database rieng.
- Credential, salts va cau hinh local phai nam ngoai Git.
- Moi thay doi kien truc lon can cap nhat lai file nay.
