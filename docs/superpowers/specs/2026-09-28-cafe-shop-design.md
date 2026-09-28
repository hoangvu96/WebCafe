# Thiết kế: Website bán cà phê đóng gói (WordPress + WooCommerce)

- **Ngày:** 2026-09-28
- **Trạng thái:** Đã duyệt thiết kế, chờ review spec

## 1. Mục tiêu và phạm vi

Website bán **cà phê đóng gói** (hạt rang, bột xay, phin giấy, hoà tan) cho khách trong nước, giao qua đơn vị vận chuyển toàn quốc. Có trang bán hàng đẹp theo phong cách **mộc mạc Việt** và trang quản lý dễ dùng cho chủ shop và nhân viên.

### Trong phạm vi giai đoạn 1
- Trang bán hàng dựng từ mẫu Kadence "Coffee Shop", đã Việt hoá và đổi phong cách.
- Sản phẩm có biến thể theo **khối lượng × dạng xay**.
- Thanh toán **COD** và **chuyển khoản VietQR**.
- Phí ship **cố định**, miễn phí khi đơn đạt ngưỡng.
- Khách mua **không cần tài khoản**.
- Trang **Tổng quan** trong wp-admin, phân quyền nhân viên, làm gọn admin.
- Môi trường phát triển local bằng Docker.
- Dữ liệu mẫu: sản phẩm, ảnh, nội dung.

### Ngoài phạm vi (làm ở giai đoạn sau)
Đa ngôn ngữ (Việt/Anh/Pháp/Ý, cần plugin trả phí), bán và giao hàng quốc tế, đa tiền tệ, blog, tài khoản khách hàng, đánh giá sản phẩm, mã giảm giá, chat Zalo/Messenger, kết nối API hãng vận chuyển, cổng thanh toán online (VNPay/MoMo), deploy lên VPS.

### Ràng buộc để mở rộng sau này
- Toàn bộ chuỗi hiển thị trong child theme và plugin dùng hàm dịch của WordPress (`__()`, `_e()`, text domain riêng), để sau này thêm plugin đa ngôn ngữ mà không phải sửa code.
- Không hard-code tiền tệ VNĐ trong logic. Luôn dùng các hàm định dạng giá của WooCommerce.

## 2. Kiến trúc

### Môi trường
- **Local:** Docker Compose gồm `wordpress` (PHP 8.2), `mysql` (8.x), `phpmyadmin`, và WP-CLI để chạy script cài đặt.
- **Production:** VPS có quyền root/SSH. Việc deploy làm ở giai đoạn sau.
- Máy dev hiện chưa có Docker nên kế hoạch cần có bước cài Docker Desktop.

### Thành phần

| Thành phần | Loại | Vai trò |
|---|---|---|
| WordPress + WooCommerce | Core | Nền tảng bán hàng |
| Kadence | Theme cha (free) | Khung giao diện, header/footer builder, tuỳ biến WooCommerce |
| Kadence Blocks | Plugin (free) | Các block dựng trang |
| Kadence Starter Templates | Plugin (free) | Import mẫu "Coffee Shop" |
| `cafe-child` | Child theme (tự viết) | **Chỉ phần hình thức:** màu, font, CSS, override template khi cần |
| `cafe-core` | Plugin (tự viết) | **Toàn bộ logic nghiệp vụ:** thông tin hạt, checkout Việt Nam, VietQR, shipping, trang tổng quan, phân quyền, làm gọn admin |

Nguyên tắc: đổi theme thì không mất chức năng, vì mọi chức năng đều nằm trong `cafe-core`.

### Cấu trúc thư mục

```
web/
├── docker-compose.yml
├── .env.example
├── wp-content/
│   ├── themes/cafe-child/
│   └── plugins/cafe-core/
│       ├── cafe-core.php          # bootstrap, nạp các module
│       └── includes/
│           ├── bean-info/         # trường "Thông tin hạt"
│           ├── checkout-vn/       # form thanh toán Việt Nam + validate SĐT
│           ├── vietqr/            # cài đặt ngân hàng + render QR
│           ├── shipping/          # phí cố định + ngưỡng miễn phí
│           ├── dashboard/         # trang Tổng quan
│           └── admin-roles/       # vai trò nhân viên + làm gọn menu
├── scripts/                       # cài đặt, dữ liệu mẫu (WP-CLI)
├── tests/                         # PHPUnit + Playwright
└── docs/
```

Mỗi module trong `cafe-core` độc lập: tự đăng ký hook của mình và không gọi trực tiếp vào module khác.

## 3. Trang bán hàng

### Nền giao diện
Import mẫu **Kadence Starter Templates → "Coffee Shop" (eCommerce, free)**. Nếu lúc import mẫu này không còn miễn phí hoặc không phù hợp, báo chủ shop kèm 1–2 mẫu thay thế trước khi làm tiếp.

### Phong cách "mộc mạc Việt"
Chỉnh trong Kadence Global Palette và Typography:

| Vai trò | Màu |
|---|---|
| Chữ, nút chính | Nâu cà phê `#4A2C1D` |
| Điểm nhấn | Nâu đất `#8B5A3C` |
| Nền | Kem `#F5EDE0` |
| Khung, viền | Kraft `#D9C3A5` |
| Badge "còn hàng" | Xanh lá cà phê `#5B6B3A` |

- **Font** (Google Fonts, có subset tiếng Việt): tiêu đề dùng **Lora**, nội dung dùng **Be Vietnam Pro**.
- **Trang trí:** vân giấy nhẹ ở nền, đường kẻ nét đứt kiểu tem nhãn, icon nét mảnh (phin, hạt, lá), nút bo góc nhỏ.
- Thiết kế **ưu tiên điện thoại**.

### Các trang
1. **Trang chủ:** hero kèm nút "Mua ngay", 3 cam kết (rang mới mỗi tuần, nguyên chất 100%, giao toàn quốc), danh mục nổi bật, sản phẩm bán chạy, đoạn giới thiệu vùng trồng, footer.
2. **Cửa hàng / Danh mục:** lưới sản phẩm, lọc theo danh mục, sắp xếp theo giá.
3. **Chi tiết sản phẩm:** gallery, chọn biến thể, khung "Thông tin hạt", sản phẩm liên quan.
4. **Giỏ hàng.**
5. **Thanh toán:** khách không cần tài khoản.
6. **Cảm ơn:** hiện VietQR nếu khách chọn chuyển khoản.
7. **Liên hệ:** địa chỉ, số điện thoại, Google Maps nhúng.
8. **Chính sách:** giao hàng, đổi trả, bảo mật (trang chữ).

Xoá khỏi mẫu các trang và section không dùng: blog, tài khoản, đánh giá.

### Danh mục sản phẩm mẫu
Robusta, Arabica, Culi, Blend, Phin giấy, Hoà tan. Khoảng 10–12 sản phẩm mẫu kèm ảnh và mô tả tiếng Việt.

### Biến thể sản phẩm
- Thuộc tính toàn cục **Khối lượng:** 250g, 500g, 1kg. Mỗi mức có giá riêng.
- Thuộc tính toàn cục **Dạng xay:** Nguyên hạt, Xay phin, Xay espresso, Xay pour-over.
- Sản phẩm dạng biến thể (variable product), tồn kho quản lý theo từng biến thể.
- Sản phẩm không cần xay (phin giấy, hoà tan) chỉ dùng thuộc tính Khối lượng, hoặc là sản phẩm đơn (simple product).

### Module `bean-info`: khung "Thông tin hạt"
- Các trường trong trang sửa sản phẩm: **Nguồn gốc** (text), **Độ cao** (text, ví dụ "1.500m"), **Mức rang** (chọn một: Nhạt / Vừa / Đậm), **Hương vị** (text).
- Hiển thị thành một khung trên trang chi tiết sản phẩm. Trường nào để trống thì ẩn. Nếu cả 4 trường đều trống thì ẩn luôn cả khung.

### Module `checkout-vn`: form thanh toán
- Các trường: Họ tên, Số điện thoại, Tỉnh/Thành, Quận/Huyện, Phường/Xã, Địa chỉ cụ thể, Ghi chú.
- Bỏ các trường: Công ty, Mã bưu điện, Quốc gia (cố định Việt Nam), Email. Email **không bắt buộc**, nếu khách nhập thì WooCommerce gửi email xác nhận đơn.
- Số điện thoại phải khớp regex `^0\d{9}$` sau khi loại bỏ khoảng trắng và dấu chấm.
- Tỉnh/Quận/Phường nhập dạng text bắt buộc ở giai đoạn 1. Dropdown liên kết cấp hành chính để giai đoạn sau.

### Module `shipping`
- Phương thức "Phí cố định" với số tiền chỉnh được, mặc định 30.000đ.
- Miễn phí ship khi tổng giá trị hàng (chưa tính ship) **≥ ngưỡng**, mặc định 500.000đ, chỉnh được.
- Ngưỡng và phí cấu hình trong trang cài đặt của `cafe-core`.

## 4. Thanh toán và quy trình đơn

| Phương thức | Trạng thái ban đầu | Chuyển tiếp |
|---|---|---|
| COD | Đang xử lý | Nhân viên chuyển sang Hoàn thành khi giao xong |
| Chuyển khoản (VietQR) | Chờ thanh toán (on-hold) | Bấm "Đã nhận tiền" thì chuyển sang Đang xử lý, sau đó Hoàn thành |

### Module `vietqr`
- **Cài đặt:** mã ngân hàng (BIN hoặc mã viết tắt theo danh sách VietQR), số tài khoản, tên chủ tài khoản.
- Mở rộng phương thức chuyển khoản (BACS) của WooCommerce: trang Cảm ơn (và email nếu có) hiện ảnh QR lấy từ
  `https://img.vietqr.io/image/{bank}-{account}-compact2.png?amount={tổng}&addInfo={nội dung}&accountName={tên}`.
- **Nội dung chuyển khoản:** `DH{mã đơn}`, ví dụ `DH1024`. Chỉ gồm chữ không dấu và số.
- Bên dưới QR luôn hiện đầy đủ thông tin dạng chữ: ngân hàng, số tài khoản, chủ tài khoản, số tiền, nội dung.
- Không tự động đối soát giao dịch. Nhân viên xác nhận thủ công.

## 5. Trang quản lý

### Module `dashboard`: trang Tổng quan
Là màn hình đầu tiên sau khi đăng nhập, dành cho Quản trị viên, Quản lý cửa hàng và Nhân viên bán hàng.

- **Thẻ số liệu:** doanh thu hôm nay, 7 ngày, tháng này; số đơn mới hôm nay. Doanh thu tính trên các đơn có trạng thái Đang xử lý và Hoàn thành.
- **Đơn chờ xác nhận chuyển khoản:** danh sách đơn on-hold thanh toán BACS, mỗi đơn có nút **"Đã nhận tiền"**. Nút này dùng AJAX, có kiểm tra nonce và quyền `edit_shop_orders`, và chuyển đơn sang Đang xử lý.
- **Sắp hết hàng:** các biến thể hoặc sản phẩm có tồn kho ≤ ngưỡng, dùng ngưỡng "low stock" của WooCommerce (mặc định 5).
- **Top 5 sản phẩm bán chạy** trong 30 ngày.
- **Biểu đồ cột doanh thu** 30 ngày, dùng Chart.js nạp cục bộ.
- Số liệu cache bằng transient trong 5 phút, và xoá cache khi đơn đổi trạng thái.
- Dùng API tương thích HPOS của WooCommerce (`wc_get_orders`), không truy vấn thẳng vào bảng `posts`.

### Module `admin-roles`: phân quyền và làm gọn admin

| Vai trò | Quyền |
|---|---|
| Quản trị viên | Toàn quyền |
| Quản lý cửa hàng (`shop_manager`, có sẵn) | Sản phẩm, đơn, báo cáo, cài đặt shop |
| **Nhân viên bán hàng** (`cafe_staff`, tạo mới) | Tổng quan, xem và sửa đơn hàng, xem sản phẩm và tồn kho. **Không** sửa giá, không xoá sản phẩm, không vào cài đặt |

- Ẩn các menu không dùng (Bài viết, Bình luận, Công cụ…) với `cafe_staff` và `shop_manager`.
- Việc ẩn menu chỉ để giao diện gọn. Quyền thật sự được chặn bằng capability.
- Trang đăng nhập và thanh admin dùng logo và màu thương hiệu tạm.

## 6. Xử lý lỗi

- **Hết hàng:** WooCommerce chặn chọn biến thể đã hết và chặn đặt quá số lượng tồn.
- **Form thanh toán:** báo lỗi rõ ràng bằng tiếng Việt cho từng trường sai.
- **VietQR không tải được:** ảnh có `alt` và thông tin chữ luôn hiển thị, nên khách vẫn chuyển khoản được.
- **Chưa cấu hình ngân hàng:** ẩn phương thức chuyển khoản ở trang thanh toán và hiện thông báo trong admin.
- **Nút "Đã nhận tiền" gọi lỗi** (hết phiên, thiếu quyền): hiện thông báo lỗi và không đổi trạng thái đơn.

## 7. Kiểm thử

- **PHPUnit** (chạy trong Docker) cho `cafe-core`: tạo nội dung chuyển khoản, dựng URL VietQR, validate số điện thoại, tính phí ship và ngưỡng miễn phí, tính số liệu tổng quan, capability của `cafe_staff`.
- **Playwright E2E:**
  1. Khách: trang chủ, chọn sản phẩm (khối lượng + dạng xay), giỏ hàng, thanh toán COD, trang cảm ơn.
  2. Khách: thanh toán chuyển khoản, trang cảm ơn có QR đúng số tiền và nội dung.
  3. Nhân viên: đăng nhập, thấy đơn chờ xác nhận, bấm "Đã nhận tiền", đơn chuyển sang Đang xử lý.
  4. Nhân viên: không vào được Cài đặt WooCommerce, không sửa được giá.
- **Kiểm tra thủ công:** hiển thị trên điện thoại (iOS/Android) và máy tính, tốc độ tải trang chủ và trang sản phẩm.

## 8. Tiêu chí hoàn thành

- `docker compose up` cùng script cài đặt dựng được site đầy đủ kèm dữ liệu mẫu trên máy mới.
- Khách đặt được đơn COD và đơn chuyển khoản trên điện thoại. Trang cảm ơn hiện QR đúng.
- Nhân viên xác nhận được đơn chuyển khoản từ trang Tổng quan, và bị giới hạn quyền đúng như bảng ở Mục 5.
- Toàn bộ test PHPUnit và Playwright đều pass.
