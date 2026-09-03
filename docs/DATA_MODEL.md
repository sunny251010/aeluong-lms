# Data model

## Tong quan

Du an hien dang dung data model mac dinh cua WordPress. Chua co bang custom, custom post type, taxonomy hay metadata rieng cho LMS trong source code.

## Bang du lieu hien tai

- Su dung cac bang WordPress mac dinh voi prefix hien tai trong local config la `wp_`.
- Database local khong duoc track trong Git.
- Chua co migration script trong repo.

## Dinh huong LMS sau nay

Khi bat dau xay LMS, nen ghi ro tai day cac thuc the chinh, vi du:

- `course`: khoa hoc.
- `lesson`: bai hoc.
- `enrollment`: quan he hoc vien - khoa hoc.
- `progress`: tien do hoc tap.
- `quiz` va `submission`: bai kiem tra va bai nop.

Moi lan them custom post type, taxonomy, meta field, custom table hoac migration thi cap nhat file nay.

## DB change trong lan khoi tao Git

- Khong co thay doi database.
