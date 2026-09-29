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

## Ngôn ngữ giao diện (Tiếng Việt / English)
- Nút chọn cờ trên header là shortcode `[cafe_language_switcher]` (module `cafe-core/includes/language/`), được đặt vào phần tử HTML của header Kadence bằng `wp eval-file /scripts/setup/language-switcher.php` (`restore.sh` tự chạy script này).
- Lựa chọn của khách được lưu trong cookie `cafe_lang`; chỉ áp dụng cho trang khách, wp-admin giữ ngôn ngữ của tài khoản.
- **Sản phẩm** (tên, mô tả, thông tin hạt) chỉ có tiếng Việt. Các nội dung khác trong DB có bản tiếng Anh:
  | Nội dung | Sửa bản tiếng Anh ở |
  |---|---|
  | Trang (trang chủ, Liên hệ, chính sách, tiêu đề Cửa hàng/Giỏ hàng/Thanh toán) | Hộp **Bản tiếng Anh** khi sửa trang → một trang *Riêng tư* liên kết với trang gốc; URL không đổi. Trang gốc đổi bố cục thì nhớ sửa cả bản tiếng Anh |
  | Danh mục, giá trị thuộc tính (Xay phin…) | Ô **Tên tiếng Anh** khi sửa danh mục/giá trị thuộc tính |
  | Mục menu | Ô **Nhãn tiếng Anh** trong Giao diện → Menu |
  | Khẩu hiệu, footer, widget, COD, câu chính sách ở trang thanh toán, tên thuộc tính | Bảng cụm từ trong WooCommerce → Cài đặt Cafe |
- Bản dịch ban đầu nạp bằng `wp eval-file /scripts/setup/translations-en.php` (`restore.sh` tự chạy; chỉ điền phần còn thiếu, không đè bản đã sửa).
- Thêm hoặc sửa chuỗi `__()` trong code thì cập nhật file dịch:
  ```bash
  wp() { docker compose run --rm -T wpcli wp "$@" </dev/null; }
  P=/var/www/html/wp-content
  wp i18n make-pot $P/plugins/cafe-core $P/plugins/cafe-core/languages/cafe-core.pot --domain=cafe-core --exclude=includes/dashboard/assets/vendor
  wp i18n make-pot $P/themes/cafe-child $P/themes/cafe-child/languages/cafe-child.pot --domain=cafe-child
  wp i18n update-po $P/plugins/cafe-core/languages/cafe-core.pot $P/plugins/cafe-core/languages/cafe-core-en_US.po
  wp i18n update-po $P/themes/cafe-child/languages/cafe-child.pot $P/themes/cafe-child/languages/en_US.po
  # dịch các msgstr còn trống (Poedit hoặc sửa tay), rồi:
  wp i18n make-mo $P/plugins/cafe-core/languages
  wp i18n make-mo $P/themes/cafe-child/languages
  ```
  File dịch của theme đặt tên theo locale (`en_US.po`), của plugin có tiền tố text domain (`cafe-core-en_US.po`).

## Cấu trúc
- `wp-content/plugins/cafe-core/` – toàn bộ nghiệp vụ, mỗi module trong `includes/<module>/`
- `wp-content/themes/cafe-child/` – chỉ phần hình thức
- `scripts/` – cài đặt, dữ liệu mẫu, snapshot
- Cấu hình phí ship và số điện thoại hỗ trợ: WooCommerce → Cài đặt Cafe

## Lưu ý
`snapshot/db.sql` và `snapshot/uploads.tar.gz` không được commit (có trong `.gitignore`): dump không có mật khẩu nhưng vẫn chứa email và dữ liệu cấu hình của site local. Muốn dựng lại trên máy khác, chạy `scripts/snapshot-export.sh` trên máy nguồn rồi chép hai file này vào `snapshot/` của máy đích (qua kênh riêng tư) trước khi chạy `scripts/restore.sh`; `snapshot/plugins.txt` thì có trong repo.
