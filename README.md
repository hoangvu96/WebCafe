# Cà Phê Mộc – website bán cà phê (WordPress + WooCommerce)

## Yêu cầu
- Docker Desktop, Node 18+ (chỉ để chạy E2E)

## Dựng site trên máy mới
```bash
cp .env.example .env         # rồi đổi mật khẩu
scripts/setup.sh             # cài WordPress, WooCommerce, Kadence, cafe-core
scripts/restore.sh           # nạp giao diện, trang, sản phẩm từ snapshot/
```
- Cửa hàng: http://localhost:8080
- Quản trị: http://localhost:8080/wp-admin (tài khoản `WP_ADMIN_USER`, nhân viên `STAFF_USER`)
- phpMyAdmin: http://localhost:8081

## Dựng từ đầu (không dùng snapshot)
`scripts/setup.sh` → import mẫu Kadence "Coffee Shop" (xem plan Task 14) → `scripts/seed.sh` → Việt hoá trang chủ (Task 16, xem `scripts/setup/theme-style.php` để áp lại bảng màu/typography "mộc mạc Việt" — trạng thái này đã có sẵn trong snapshot nên không cần chạy lại khi dùng `restore.sh`).

## Kiểm thử
```bash
scripts/test-unit.sh   # PHPUnit
scripts/test-e2e.sh    # Playwright (site phải đang chạy; cuối lượt tự huỷ + xoá đơn test để trả lại tồn kho)
```

## Snapshot / khôi phục
- `scripts/snapshot-export.sh` xuất DB (`snapshot/db.sql`, ~1,4 MB), thư mục uploads (`snapshot/uploads.tar.gz`, ~10 MB) và danh sách plugin đang active kèm phiên bản (`snapshot/plugins.txt`, mỗi dòng `tên,phiên bản`).
- Trước khi dump, script huỷ mọi phiên đăng nhập (session tokens) và xoá transient; bảng `wp_woocommerce_sessions` chỉ lấy cấu trúc, không lấy dữ liệu. Cột `user_pass` và `user_activation_key` trong `wp_users` được xoá trắng — **dump không chứa hash mật khẩu** (script dừng nếu còn sót).
- `uploads.tar.gz` bỏ qua `uploads/wc-logs`, file import mẫu Kadence (`uploads/kadence_starter_templates`, `*demo-*-import-file*`) và toàn bộ ảnh thumbnail đã sinh (`*-<rộng>x<cao>.*`).
- `scripts/restore.sh` nạp lại các file trên vào một site đã chạy `scripts/setup.sh`: cài plugin đúng phiên bản, import DB, giải nén uploads, chạy `wp media regenerate` để sinh lại thumbnail, rồi đặt mật khẩu cho `WP_ADMIN_USER`/`STAFF_USER` từ `.env` (tạo tài khoản nếu chưa có). Các tài khoản khác trong dump không có mật khẩu — dùng "Quên mật khẩu" hoặc `wp user update`.
- Do image `wpcli` (dựa trên mariadb-client) không đăng nhập được vào MySQL 8 (`caching_sha2_password`), hai script này dump/nạp DB trực tiếp qua container `db` (`mysqldump`/`mysql`) thay vì `wp db export`/`wp db import`. Script export cần `perl` trên máy chủ.

## Cấu trúc
- `wp-content/plugins/cafe-core/` – toàn bộ nghiệp vụ, mỗi module trong `includes/<module>/`
- `wp-content/themes/cafe-child/` – chỉ phần hình thức
- `scripts/` – cài đặt, dữ liệu mẫu, snapshot
- Cấu hình phí ship và số điện thoại hỗ trợ: WooCommerce → Cài đặt Cafe

## Lưu ý
`snapshot/db.sql` và `snapshot/uploads.tar.gz` không được commit (có trong `.gitignore`): dump không có mật khẩu nhưng vẫn chứa email và dữ liệu cấu hình của site local. Muốn dựng lại trên máy khác, chạy `scripts/snapshot-export.sh` trên máy nguồn rồi chép hai file này vào `snapshot/` của máy đích (qua kênh riêng tư) trước khi chạy `scripts/restore.sh`; `snapshot/plugins.txt` thì có trong repo.
