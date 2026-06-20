# Homi - Kế hoạch Agile Scrum quản lý 1 khách sạn

> Tài liệu kế hoạch nội bộ nhóm - Thay BE1/BE2/BE3/BE4 bằng tên thật khi nộp báo cáo.
>
> **Lưu ý kiến trúc:** Homi là **Laravel Blade monolith** — controller trả thẳng về view Blade (server-side rendering), **không xây REST API/JSON riêng**, không dùng Postman. Mọi route đều là web route (`web.php`), không có tầng `/api`.

# KẾ HOẠCH AGILE SCRUM DỰ ÁN HOMI

**Website đặt phòng và quản lý 1 khách sạn duy nhất**

Thời gian thực hiện: 11/05/2026 - 30/08/2026 | 16 tuần | **8 Sprint** (mỗi Sprint 2 tuần)

**Định hướng sản phẩm**

- Hệ thống Homi chỉ quản lý 1 khách sạn, không xây dựng nền tảng nhiều khách sạn.
- Tách rõ 2 khu vực sử dụng: `/customer` cho khách hàng và `/admin` cho admin/staff.
- Luồng lõi bắt buộc: xem phòng → kiểm tra phòng trống → đặt phòng → khách xem/hủy đơn → admin xác nhận → thanh toán mô phỏng → thống kê.
- Các module phụ như dịch vụ, ưu đãi, đánh giá chỉ làm mức vừa đủ, không được làm ảnh hưởng availability và booking.

## 1. Thông tin chung dự án

| Mục | Nội dung |
|---|---|
| Tên dự án | Homi - Website đặt phòng và quản lý 1 khách sạn |
| Mô hình quản lý | 1 khách sạn duy nhất, hotel_info là dữ liệu singleton, không có CRUD nhiều khách sạn |
| Công nghệ đề xuất | Laravel 13, MySQL, **Blade (server-side rendering, không xây REST API/JSON riêng)**, PHPUnit/Pest, GitHub/GitLab, staging/local demo |
| Thời gian | 11/05/2026 - 30/08/2026, chia thành **8 Sprint, mỗi Sprint 2 tuần** |
| Đầu ra cuối | Source code, database/seed, README, tài khoản demo, link demo hoặc hướng dẫn chạy local, test report, release note, demo script |

## 2. Mục tiêu sản phẩm và phạm vi chức năng

### 2.1. Mục tiêu sản phẩm

- Xây dựng website cho khách hàng xem thông tin khách sạn, xem phòng, kiểm tra phòng trống và đặt phòng.
- Xây dựng trang quản trị để admin/staff quản lý thông tin khách sạn, loại phòng, khách hàng, đơn đặt phòng, thanh toán mô phỏng và thống kê.
- Đảm bảo có phân quyền rõ giữa customer, staff và admin.
- Đảm bảo nghiệp vụ kiểm tra phòng trống và tạo đơn đặt phòng hoạt động đúng, có test tự động cho các case quan trọng.

### 2.2. Phạm vi bắt buộc và phạm vi phụ

| Nhóm chức năng | Bắt buộc trong đồ án | Có thời gian thì mở rộng |
|---|---|---|
| Auth/RBAC | Register, login, logout, profile, role customer/staff/admin, redirect theo role, middleware bảo vệ route | Quên mật khẩu, đổi email, đăng nhập mạng xã hội |
| Customer | Xem khách sạn, xem/lọc phòng, chi tiết phòng, kiểm tra trống, đặt phòng, xem/hủy đơn | Đánh giá nâng cao, lịch sử thanh toán chi tiết |
| Admin | Dashboard, hotel_info singleton, room_types, amenities, bookings, payments, customers/users, reports cơ bản | Xuất Excel/PDF, phân quyền chi tiết theo permission |
| Booking | Availability, overlap date test, pricing, create booking bằng transaction, cancel booking | Tính giá theo mùa, phụ thu trẻ em, voucher phức tạp |
| Test/Deploy | PHPUnit/Pest, test report, smoke test, README, seed demo, link demo/local backup | CI/CD nâng cao, monitoring thật |

## 3. Mô hình Scrum áp dụng

### 3.1. Vai trò Scrum

| Vai trò Scrum | Người/nhóm phụ trách | Trách nhiệm chính | Minh chứng cần có |
|---|---|---|---|
| Product Owner | Nhóm trưởng hoặc người đại diện làm việc với thầy | Ưu tiên backlog, xác nhận yêu cầu, chốt tiêu chí nghiệm thu, nhận feedback của thầy | Product backlog, sprint goal, biên bản sprint review |
| Scrum Master | BE4 hoặc nhóm trưởng kỹ thuật | Tổ chức sprint planning, daily scrum, review, retrospective; gỡ vướng quy trình | Sprint board, impediment log, retrospective note |
| Development Team | BE1, BE2, BE3, BE4 | Phân tích, code, review, test, demo, tài liệu. Mỗi thành viên đều có task code hằng tuần | Commit, PR, test case, evidence sprint |
| Stakeholder | Thầy hướng dẫn/người chấm | Góp ý phạm vi, xem demo mốc, đánh giá tính hoàn chỉnh đồ án | Feedback sau demo, yêu cầu chỉnh sửa |

### 3.2. Nhịp Scrum trong mỗi sprint 2 tuần

| Thời điểm | Hoạt động Scrum | Cách thực hiện |
|---|---|---|
| Thứ 2 tuần đầu sprint | Sprint Planning | Chốt sprint goal, chọn backlog, chia task theo thành viên, ước lượng story point/độ khó, xác định tiêu chí hoàn thành. |
| Mỗi ngày 10-15 phút | Daily Scrum | Mỗi người trả lời: hôm qua làm gì, hôm nay làm gì, đang vướng gì. Cập nhật board ngay sau daily. |
| Thứ 4 hoặc Thứ 5 | Backlog Refinement | Làm rõ user story tuần sau, cập nhật acceptance criteria, tách task quá lớn. |
| Cuối mỗi tuần | Internal Checkpoint | Demo nội bộ phần đã xong, chạy test, cập nhật bug list, tránh dồn lỗi sang cuối sprint. |
| Chủ nhật tuần chẵn | Sprint Review | Demo increment cho nhóm/thầy nếu có; ghi feedback vào product backlog. |
| Sau Sprint Review | Sprint Retrospective | Nêu điều làm tốt, chưa tốt, hành động cải tiến cho sprint sau. |

### 3.3. Definition of Ready và Definition of Done

| Definition of Ready - một task được đưa vào sprint khi | Definition of Done - một task được tính là hoàn thành khi |
|---|---|
| Có mô tả user story/task rõ ràng | Code đã hoàn thành và được commit/PR |
| Có người phụ trách chính và người review | Đã chạy test liên quan và không lỗi critical |
| Có dữ liệu đầu vào/đầu ra hoặc route/màn hình cần làm | Đã review chéo ít nhất bởi 1 thành viên |
| Có acceptance criteria tối thiểu | Tài liệu route/màn hình và test case được cập nhật |
| Không phụ thuộc vào task chưa rõ yêu cầu | Demo được trên local/staging và có evidence |

## 4. Phân vai thành viên

| Thành viên | Vai trò chính | Phạm vi code chính | Phạm vi phối hợp/test |
|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Laravel setup, auth, middleware, policy, chuẩn view/layout, error handling, users, security, release source | Review code chung, route & màn hình, kiến trúc backend, hướng dẫn cài đặt |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | hotel_info singleton, room_types, images, amenities, price/inventory data, admin room UI, customer room UI | Seed demo, data dictionary, search/list/detail, dữ liệu cho pricing/availability |
| BE3 | Booking, Availability, Payment, Customer flow | AvailabilityService, PricingService, BookingService, booking, cancellation, payment status, customer booking UI | Overlap test, transaction logic, demo script nghiệp vụ |
| BE4 | QA, DevOps, Admin UI, Scrum Master support | Test suite, CI/staging, admin dashboard, reports, test report, bug report, release note | Sprint board, regression test, smoke test, polish UI, minh chứng nghiệm thu |

## 5. Product Backlog tổng quát

| ID | Epic/User Story | Đối tượng | Ưu tiên | Sprint dự kiến | Acceptance Criteria tóm tắt |
|---|---|---|---|---|---|
| US01 | Đăng ký/đăng nhập/đăng xuất và phân quyền | Customer/Admin/Staff | Must | Sprint 2 | Đăng nhập đúng role, redirect đúng khu vực, sai quyền bị chặn. |
| US02 | Quản lý thông tin 1 khách sạn | Admin | Must | Sprint 3 | Admin cập nhật được hotel_info; public xem được thông tin công khai. |
| US03 | Quản lý loại phòng, giá, số lượng, ảnh, trạng thái | Admin | Must | Sprint 3 | CRUD/ẩn/hiện phòng, validation giá/số lượng, không hard delete khi có booking. |
| US04 | Khách xem danh sách, lọc và chi tiết phòng | Customer/Public | Must | Sprint 4 | Filter hoạt động; chỉ hiển thị phòng active; detail đủ ảnh/tiện ích/giá. |
| US05 | Kiểm tra phòng trống theo ngày | Customer/System | Must | Sprint 5 | Trả available_quantity/can_book đúng với overlap cases. |
| US06 | Tạo đơn đặt phòng và tính tiền | Customer | Must | Sprint 5 | Tạo booking bằng transaction; không đặt vượt số lượng; tạo payment pending. |
| US07 | Khách xem/hủy đơn của mình | Customer | Must | Sprint 6 | Customer chỉ xem đơn của mình; hủy hợp lệ và availability cập nhật đúng. |
| US08 | Admin quản lý đơn và thanh toán mô phỏng | Admin/Staff | Must | Sprint 6 | Admin xác nhận/hủy đơn, cập nhật paid/refunded, state transition hợp lệ. |
| US09 | Admin quản lý khách hàng/tài khoản | Admin | Must | Sprint 7 | Tìm kiếm, lọc role, khóa/mở, xem lịch sử đặt phòng. |
| US10 | Dashboard thống kê cơ bản | Admin | Must | Sprint 7 | Tổng đơn, đơn chờ, đã xác nhận, hủy, doanh thu mô phỏng, số khách. |
| US11 | Đánh giá, dịch vụ, ưu đãi mức đơn giản | Customer/Admin | Should | Sprint 7 | Đủ demo, không ảnh hưởng booking core. |
| US12 | Test report, deploy, release package | Nhóm | Must | Sprint 8 | Có test report, README, seed/DB, link demo/local backup, release note. |

## 6. Kế hoạch Sprint tổng quan

| Sprint | Thời gian | Sprint Goal | Increment/Demo cuối sprint | Module ưu tiên |
|---|---|---|---|---|
| Sprint 1 | 11/05 - 24/05 | Khởi động dự án, chốt backlog, dựng nền tảng Laravel, DB, route & màn hình, test base. | Repo chạy, DB seed nền, chuẩn view/layout, test health-check. | Setup, DB, route & màn hình |
| Sprint 2 | 25/05 - 07/06 | Hoàn thiện auth, phân quyền, route /customer và /admin, core layout. | Customer/admin login đúng role; middleware bảo vệ route; layout skeleton. | Auth, RBAC, core UI |
| Sprint 3 | 08/06 - 21/06 | Admin quản lý thông tin khách sạn và loại phòng. | Admin cập nhật hotel_info; CRUD room_types; public xem thông tin/phòng. | Hotel, rooms, price |
| Sprint 4 | 22/06 - 05/07 | Khách hàng xem/lọc phòng, xem chi tiết và điền form đặt phòng. | Trang /customer/rooms, detail, booking form v1. | Search, detail, booking form |
| Sprint 5 | 06/07 - 19/07 | Kiểm tra phòng trống và tạo đơn đặt phòng bằng transaction. | Khách chọn ngày → kiểm tra trống → tạo booking → payment pending. | Availability, booking |
| Sprint 6 | 20/07 - 02/08 | Khách quản lý đơn; admin quản lý đơn và thanh toán mô phỏng. | Customer my bookings; admin confirm/cancel/payment. | Booking management, payment |
| Sprint 7 | 03/08 - 16/08 | Bổ sung quản lý khách hàng, dashboard, module phụ vừa đủ và tích hợp staging. | Admin dashboard, customers, review/service/promotion basic, RC1. | Customers, stats, integration |
| Sprint 8 | 17/08 - 30/08 | System test, sửa lỗi, hoàn thiện tài liệu, deploy và chuẩn bị bảo vệ. | Bản nộp cuối đủ source, DB, test report, release note, demo script. | UAT, release, defense |

## 7. Cấu trúc route/màn hình cần hoàn thành

| Khu vực | Route/màn hình | Mục đích |
|---|---|---|
| Public | `/` | Trang giới thiệu/tổng quan khách sạn |
| Public | `/rooms` hoặc `/customer/rooms` | Danh sách và lọc phòng active |
| Public | `/rooms/{id}` | Chi tiết phòng |
| Customer | `/customer/login`, `/customer/register` | Đăng nhập/đăng ký khách hàng |
| Customer | `/customer/dashboard` | Tổng quan tài khoản khách |
| Customer | `/customer/profile` | Thông tin cá nhân |
| Customer | `/customer/booking/create` | Form đặt phòng |
| Customer | `/customer/bookings`, `/customer/bookings/{id}` | Đơn đặt phòng của tôi |
| Admin | `/admin/login` | Đăng nhập admin/staff |
| Admin | `/admin/dashboard` | Thống kê tổng quan |
| Admin | `/admin/hotel-info` | Thông tin 1 khách sạn |
| Admin | `/admin/room-types` | Quản lý loại phòng/giá/số lượng |
| Admin | `/admin/bookings` | Quản lý đơn đặt phòng |
| Admin | `/admin/payments` | Thanh toán mô phỏng |
| Admin | `/admin/customers` | Quản lý khách hàng |
| Admin | `/admin/users` | Quản lý tài khoản admin/staff/customer |
| Admin | `/admin/reports` | Báo cáo/thống kê cơ bản |

## 8. Công việc chi tiết từng thành viên theo từng tuần

> Ghi chú: mỗi Sprint dài 2 tuần, bảng dưới đây chia nhỏ theo từng tuần để dễ theo dõi tiến độ, nhưng Sprint Planning/Review/Retrospective vẫn diễn ra theo nhịp 2 tuần ở mục 3.2.

### Tuần 1: 11/05 - 17/05 - Sprint 1 - Khởi động, backlog, nền tảng dự án

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Thiết lập Laravel project, cấu hình môi trường .env, Git flow, branch rule, cấu trúc thư mục app/Services, app/Http/Requests, app/Policies. Tạo route nhóm web (`web.php`), layout cơ bản và route/trang health-check `/health`. | Repo chạy local, README setup v1, route `/health`, quy ước commit/branch/PR. | Chạy được project sau khi clone; có ít nhất 1 PR mẫu; cả nhóm thống nhất coding convention. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Phân tích dữ liệu khách sạn 1 bản ghi, loại phòng, tiện ích, ảnh phòng. Vẽ nháp ERD cho hotel_info, hotel_images, amenities, room_types, room_type_images. | ERD nháp phần khách sạn/phòng, danh sách field cần có, dữ liệu seed dự kiến. | Không thiết kế multi-hotel; xác định rõ hotel_info là singleton. |
| BE3 | Booking, Availability, Payment, Customer flow | Phân tích nghiệp vụ đặt phòng: trạng thái booking, trạng thái thanh toán, điều kiện giữ phòng, điều kiện hủy. Tạo skeleton BookingService, AvailabilityService, PricingService. | Flow booking v1, bảng trạng thái booking/payment, service skeleton. | Flow thể hiện được tìm phòng → kiểm tra trống → tạo đơn → thanh toán mô phỏng. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Thiết lập quy trình Scrum: product backlog, sprint board, definition of done. Cấu hình PHPUnit/Pest, .env.testing, test health-check, template bug report/test case. | Scrum board, test plan sơ bộ, bug report template, test health-check pass. | Có board với các cột Backlog/To Do/In Progress/Review/Testing/Done; chạy được `php artisan test`. |

### Tuần 2: 18/05 - 24/05 - Sprint 1 - Thiết kế DB, route & màn hình, seed dữ liệu nền

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Xây layout Blade dùng chung, exception handler cho trang lỗi, FormRequest base, format lỗi validation tiếng Việt, pagination view, middleware role skeleton. | Chuẩn hiển thị lỗi/thông báo, base FormRequest, exception handler, tài liệu mã lỗi v1. | Thông báo thành công/thất bại thống nhất; lỗi validation không trả raw exception. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Code migration/model/factory/seeder cho hotel_info, hotel_images, amenities, room_types, room_type_images; seed đúng 1 khách sạn và 3-5 loại phòng mẫu. | Migration/model/seeder phần hotel/room, data dictionary, seed demo v1. | migrate:fresh --seed chạy sạch; chỉ có 1 hotel_info cố định. |
| BE3 | Booking, Availability, Payment, Customer flow | Code migration/model cho bookings, booking_items, payments, booking_status_logs. Chuẩn enum/status: pending, confirmed, canceled, completed; unpaid, paid, refunded. | Migration/model booking/payment, ma trận trạng thái đơn và thanh toán. | Không thiếu khóa ngoại cốt lõi; status nào giữ phòng và không giữ phòng được ghi rõ. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Viết test migration/seeder/health-check; thiết lập CI nếu dùng GitHub Actions. Tổng hợp route list (`route:list`) dự kiến của BE1-BE3 thành tài liệu route & màn hình. | Automated test nền tảng, tài liệu route & màn hình v1, CI checklist. | Test nền tảng pass; tài liệu route có nhóm Auth, Hotel, Room, Booking. |

### Tuần 3: 25/05 - 31/05 - Sprint 2 - Auth, RBAC, login customer/admin

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Code đăng ký khách hàng, đăng nhập, đăng xuất, profile, update profile, hash password, session auth. Tạo redirect theo role sau login. | Auth route hoàn chỉnh (Blade form), `/customer/login`, `/admin/login`, redirect theo role. | Customer vào `/customer/dashboard`; admin/staff vào `/admin/dashboard` hoặc `/admin/bookings`. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Code trang admin cơ bản quản lý user: danh sách user, xem chi tiết, lọc role, khóa/mở khóa. Tạo seed user demo admin/staff/customer. | Trang quản lý user (admin) v1, tài khoản demo, seed role. | User bị khóa không đăng nhập được; admin lọc được user theo role. |
| BE3 | Booking, Availability, Payment, Customer flow | Bảo vệ route booking customer bằng auth middleware; tạo route skeleton `/customer/bookings`, `/customer/booking/create`. Chuẩn bị mapping quyền booking. | Booking route skeleton có auth, checklist quyền customer. | Customer chưa login bị chuyển về login; customer chỉ truy cập khu vực của mình. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Viết automated test auth/RBAC: login đúng/sai, logout, update profile, role access. Dựng layout login cơ bản cho admin và customer. | Test auth/RBAC pass, bug list sprint auth, giao diện login cơ bản. | Customer không vào được `/admin`; admin không bị chuyển nhầm vào customer dashboard. |

### Tuần 4: 01/06 - 07/06 - Sprint 2 - Core backend, layout, chuẩn lỗi và logging

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Hoàn thiện middleware role admin/staff/customer, policy base, chuẩn trang lỗi 401/403/404/422/500. Review toàn bộ route auth/user. | Core backend ổn định, catalog thông báo lỗi, checklist bảo mật phiên đăng nhập. | Không lộ password/token; route sai quyền trả đúng 403. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Tạo UploadService/ImagePathService dùng cho hotel_info và room_types; chuẩn soft delete cho room_types; chuẩn relationship hotel_info-images-amenities-room_types. | Service upload/path, model relationship, soft delete chuẩn. | Upload lưu đúng thư mục; model trả dữ liệu ảnh/tiện ích ổn định. |
| BE3 | Booking, Availability, Payment, Customer flow | Tạo DateValidationService: check_in < check_out, không cho ngày quá khứ nếu nhóm chọn. Tạo constant/enum booking_status/payment_status dùng lại. | Date validation service, status constants, unit/feature test ngày. | Case thiếu ngày, cùng ngày, trả trước nhận đều bị chặn. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Thiết lập logging, coverage command, review checklist trước merge. Dựng layout khung `/admin` và `/customer`: sidebar, navbar, trang dashboard rỗng. | Review checklist, test report tuần 4, layout admin/customer skeleton. | Mỗi PR có reviewer; giao diện có khung để gắn module tuần sau. |

### Tuần 5: 08/06 - 14/06 - Sprint 3 - Quản lý thông tin 1 khách sạn

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Code policy/admin middleware cho hotel_info; chuẩn route singleton không create/list/delete nhiều khách sạn. Chuẩn validation rule cho thông tin khách sạn. | Route bảo vệ đúng quyền, validation hotel_info, hiển thị singleton. | Admin sửa được; staff/customer không được sửa nếu không có quyền. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Code route và trang `/admin/hotel-info`: xem/sửa tên, mô tả, địa chỉ, hotline, email, check-in/out, chính sách, tiện ích, ảnh. Code public `/customer/about` hoặc trang giới thiệu. | Module thông tin khách sạn hoàn chỉnh, seed dữ liệu khách sạn đẹp. | Chỉ có 1 bản ghi khách sạn; cập nhật không tạo thêm bản ghi mới. |
| BE3 | Booking, Availability, Payment, Customer flow | Code route/trang public lấy hotel_info cơ bản phục vụ booking và hiển thị; đảm bảo không trả dữ liệu nội bộ. Chuẩn bị relationship để lấy room_types ở tuần 6. | Trang public hotel-info v1, checklist public/private fields. | Khách chưa đăng nhập vẫn xem được thông tin khách sạn công khai. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Viết feature test cho hotel_info: public view, admin update, lỗi validation, lỗi quyền, upload ảnh. Kiểm thử giao diện `/admin/hotel-info`. | Test report module hotel_info, bug report nếu có, evidence pass/fail. | Module hotel_info đạt nghiệm thu sprint review; không còn lỗi quyền nghiêm trọng. |

### Tuần 6: 15/06 - 21/06 - Sprint 3 - Quản lý loại phòng, giá, số lượng

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Code policy/validation chung cho room_types: giá >= 0, quantity >= 0, capacity > 0, status hợp lệ. Bảo vệ route admin/staff. | Route quản lý phòng bảo vệ đúng quyền, request validation room_types. | Customer không gọi được route quản trị phòng; lỗi dữ liệu hiển thị rõ trên form. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Code CRUD room_types: tên, mô tả, sức chứa, giường, diện tích, tiện ích, giá, số lượng, trạng thái, ảnh. Làm UI `/admin/room-types`. | Trang quản lý loại phòng (room_types) và admin UI hoàn chỉnh, upload ảnh phòng. | Thêm/sửa/ẩn phòng hoạt động; không hard delete phòng đã có booking sau này. |
| BE3 | Booking, Availability, Payment, Customer flow | Code InventoryReadService: đọc tổng số phòng active theo room_type, chuẩn bị data cho availability. Review field tính tiền: base_price, weekend_price nếu có. | Inventory read service v1, test inventory data. | Inactive/soft-deleted room_type không được tính vào inventory public. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Test CRUD room_types bằng PHPUnit/Pest và kiểm thử UI: thêm/sửa/xóa mềm/cập nhật giá/số lượng. Tổng hợp bug list sprint 3. | Test report room_types, sprint review evidence. | Room module pass các case cơ bản và case lỗi giá âm/số lượng âm. |

### Tuần 7: 22/06 - 28/06 - Sprint 4 - Public room list, search/filter

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Code FilterRoomRequest và chuẩn query params: keyword, min_price, max_price, amenities, capacity, check_in, check_out. Bỏ location/hotel_id vì chỉ 1 khách sạn. | Search/filter request, đặc tả route search/list. | Query sai định dạng hiển thị lỗi rõ trên trang; không có filter theo khách sạn. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Code trang danh sách loại phòng active, lọc theo giá, sức chứa, tiện ích; tối ưu eager loading tránh N+1 cơ bản. Làm UI `/customer/rooms`. | Trang tìm kiếm/danh sách room_types, giao diện danh sách phòng. | Khách xem được phòng active, filter có kết quả/không kết quả đúng. |
| BE3 | Booking, Availability, Payment, Customer flow | Gắn check_in/check_out vào search ở mức truyền tham số và validate ngày; chưa kiểm tra phòng sâu cho đến tuần 9. | Search hỗ trợ ngày lưu trú cơ bản. | Ngày sai không làm vỡ danh sách; dữ liệu ngày được giữ để chuyển sang form booking. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Viết test search/list, tài liệu route mẫu cho cả nhóm tham chiếu, test hiệu năng cơ bản với seed demo. Kiểm thử UI list phòng trên customer. | Automated test search/list, tài liệu route public, test report tuần 7. | Trang tìm kiếm/danh sách đủ ổn định; hiển thị nhất quán. |

### Tuần 8: 29/06 - 05/07 - Sprint 4 - Room detail, form đặt phòng

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Chuẩn hóa Blade component/partial cho hotel-info detail và room detail: ảnh, tiện ích, chính sách, giá, sức chứa. Review giao diện nội bộ nhóm. | View/partial format ổn định, mẫu dữ liệu hiển thị. | View không cần xử lý dữ liệu vòng vo; field đặt tên thống nhất. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Code route/trang room detail `/customer/rooms/{id}`; chỉ trả phòng active; hiển thị ảnh, tiện ích, mô tả, giá. Bổ sung dữ liệu mô tả/ảnh mẫu. | Trang room detail hoàn chỉnh. | Phòng không tồn tại/phòng inactive trả 404 hoặc thông báo phù hợp. |
| BE3 | Booking, Availability, Payment, Customer flow | Thiết kế form đặt phòng `/customer/booking/create`: chọn room_type, check_in, check_out, quantity, thông tin liên hệ; hiển thị giá tạm tính đơn giản. | Booking form UI v1, dữ liệu bắt buộc trước khi booking. | Form chưa tạo đơn thật nhưng đã gom đủ dữ liệu cho availability/booking. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Test link list → detail → booking form; test dữ liệu thiếu ảnh/thiếu tiện ích; cập nhật tài liệu route và usability checklist. | Test report detail/booking form, bug list sprint 4. | Luồng xem phòng của khách hàng mượt để chuẩn bị kiểm tra trống. |

### Tuần 9: 06/07 - 12/07 - Sprint 5 - Availability, kiểm tra phòng trống

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Code form/route availability: room_type_id, check_in, check_out, quantity. Validate ngày, số lượng, room_type active. Không dùng hotel_id. | Route/form availability chuẩn đầu vào, đặc tả route availability. | Request sai bị validate rõ trên form; kết quả trả về có available_quantity và can_book. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Cung cấp query tổng số phòng active, seed dữ liệu nhiều loại phòng phục vụ overlap test. Đảm bảo inactive/soft-deleted không tính vào availability. | Data test availability, seed overlap cases. | Dữ liệu đủ để test: còn phòng, hết phòng, inactive, nhiều booking giao nhau. |
| BE3 | Booking, Availability, Payment, Customer flow | Code AvailabilityService: tính booked quantity theo khoảng ngày giao nhau. Xử lý overlap: trùng hoàn toàn, giao đầu, giao cuối, nằm trong, bao ngoài. | Availability logic chính xác, bảng expected result overlap. | Tất cả ca giao nhau ngày đều đúng; pending/confirmed giữ phòng, canceled không giữ phòng. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Viết automated test cho tất cả ca overlap và regression availability. Tích hợp nút kiểm tra trống trên UI booking form. | Test report availability, evidence pass/fail, bug critical nếu có. | Không nghiệm thu nếu sai một case overlap lõi. |

### Tuần 10: 13/07 - 19/07 - Sprint 5 - Tạo booking, tính tiền, transaction

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Code BookingRequest: room_type_id, quantity, check_in, check_out, guest_name, phone, email, note. Chuẩn mã đơn booking và thông báo tạo đơn. | Booking form chuẩn, đặc tả route/form tạo booking. | Thiếu thông tin liên hệ/ngày/số lượng đều bị chặn rõ ràng. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Cung cấp dữ liệu giá cho PricingService, kiểm tra giá hợp lệ tại thời điểm đặt. Xử lý case phòng inactive hoặc giá thay đổi. | Pricing data ổn định, test case giá phòng. | Phòng inactive không được đặt; tổng tiền lấy theo giá hiện tại có kiểm soát. |
| BE3 | Booking, Availability, Payment, Customer flow | Code PricingService và BookingService: tính số đêm, tổng tiền, tạo booking, booking_items, payment pending. Kiểm tra lại availability trong DB transaction. | Route/trang tạo booking hoàn chỉnh, transaction chống đặt vượt số lượng. | Đặt 1/nhiều phòng được; hết phòng thì không tạo đơn; có payment pending. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Viết E2E test từ search → detail → availability → create booking. Kiểm thử UI tạo đơn và màn thông báo thành công. | E2E booking test report, evidence mã đơn, bug list sprint 5. | Booking flow backend và UI chạy trọn vẹn trong demo nội bộ. |

### Tuần 11: 20/07 - 26/07 - Sprint 6 - Customer quản lý booking

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Code authorization: customer chỉ xem đơn của mình; staff/admin không dùng nhầm route customer. Chuẩn policy booking detail/cancel. | Quyền My Booking an toàn, test case phân quyền. | Customer A không xem/hủy được đơn của customer B. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Bổ sung dữ liệu hiển thị booking: loại phòng, ảnh, giá, ngày, tổng tiền, trạng thái. Tối ưu relationship tránh dữ liệu null. | Dữ liệu booking hiển thị đầy đủ, ổn định. | Danh sách booking không thiếu tên phòng/ảnh/giá/trạng thái. |
| BE3 | Booking, Availability, Payment, Customer flow | Code `/customer/bookings`, `/customer/bookings/{id}`, cancel booking. Áp dụng điều kiện hủy: chỉ pending/confirmed và trước check-in nếu nhóm chọn. | Customer booking management hoàn chỉnh. | Hủy thành công cập nhật status; availability sau hủy được tính lại đúng. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Regression test availability sau hủy; test quyền dữ liệu; kiểm thử UI My Booking. Cập nhật bug report sprint 6. | Test report hủy đơn và quyền dữ liệu. | My Booking đạt nghiệm thu; không có lỗi lộ đơn của người khác. |

### Tuần 12: 27/07 - 02/08 - Sprint 6 - Admin quản lý booking/payment

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Code permission admin/staff cho `/admin/bookings`, `/admin/payments`. Chuẩn state transition hợp lệ: pending → confirmed/canceled, confirmed → completed/canceled. | State transition bảo vệ đúng, policy admin booking. | Không cho chuyển trạng thái sai logic; customer không vào được admin bookings. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Code danh sách admin booking và filter theo trạng thái, ngày đặt, ngày check-in, khách hàng, loại phòng. Làm UI `/admin/bookings`. | Trang danh sách booking (admin), filter admin booking. | Admin tìm/lọc đơn nhanh; bỏ filter theo khách sạn vì chỉ có 1 khách sạn. |
| BE3 | Booking, Availability, Payment, Customer flow | Code admin confirm booking, cancel booking, update payment: unpaid, paid, refunded. Ghi booking_status_logs/payment_logs nếu kịp. | Trang quản lý booking/payment (admin) hoàn chỉnh, UI `/admin/payments`. | Admin xác nhận đơn và cập nhật paid; hủy đơn không phá availability. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Test phân quyền admin/staff/customer; test transition; test payment status; cập nhật sprint review demo cho admin flow. | Test report admin booking/payment, bug list theo mức độ. | Admin flow đạt nghiệm thu: xem đơn → xác nhận → thanh toán. |

### Tuần 13: 03/08 - 09/08 - Sprint 7 - Khách hàng, dashboard, đánh giá cơ bản

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Nâng cấp quản lý user/customer: tìm kiếm, lọc role, khóa/mở; tách màn `/admin/customers` và `/admin/users`. Chuẩn quyền module phụ. | User/customer management đủ demo, quyền ổn định. | Admin xem được lịch sử đặt phòng của khách; khóa khách thì khách không đăng nhập được. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Code services/promotions mức đơn giản nếu còn thời gian: tên, mô tả, trạng thái, ngày hiệu lực. Ưu tiên hiển thị/quản trị, không làm giảm giá phức tạp. | Trang services/promotions đơn giản hoặc đánh dấu optional. | Không ảnh hưởng booking core; inactive/hết hạn không hiển thị public. |
| BE3 | Booking, Availability, Payment, Customer flow | Code review/rating: customer có booking confirmed/completed mới được đánh giá; một đơn một đánh giá nếu nhóm chọn. Tính rating trung bình nếu kịp. | Trang review đủ demo. | Khách chưa đặt không đánh giá được; review trùng bị chặn. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Code dashboard stats cơ bản: tổng đơn, đơn pending/confirmed/canceled, doanh thu mô phỏng, số khách, tỷ lệ hủy. Viết test đối chiếu DB. | Trang dashboard thống kê (admin), test dashboard. | Số liệu dashboard khớp DB; dashboard phục vụ demo nhanh. |

### Tuần 14: 10/08 - 16/08 - Sprint 7 - Tích hợp, staging, dữ liệu demo

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Dọn route, controller, request, view. Chuẩn hóa toàn bộ lỗi validation/quyền; kiểm tra không trả dữ liệu nhạy cảm. Review security checklist. | Release candidate backend v1, route/view consistency checklist. | Route đặt tên thống nhất; không lộ password/token; lỗi quyền rõ. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Dọn seed demo đầy đủ: thông tin khách sạn, phòng, giá, ảnh, tiện ích, dịch vụ, ưu đãi. Kiểm tra quan hệ DB và lỗi CRUD phát sinh khi rà soát toàn hệ thống. | Seed demo ổn định, data demo checklist. | migrate:fresh --seed tạo ra dữ liệu đẹp, đủ cho demo. |
| BE3 | Booking, Availability, Payment, Customer flow | Stress test booking mức cơ bản; sửa lỗi availability, cancellation, payment status; khóa logic nghiệp vụ lõi. Review race condition và status transition. | Booking core ổn định, regression checklist booking. | Không còn lỗi critical ở availability/booking/payment. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Cấu hình staging deploy/local demo; chạy full regression test; ghi bug list ưu tiên Critical/High/Medium/Low. Polish UI admin/customer. | RC1 có minh chứng test, staging checklist, bug list ưu tiên. | Có link staging hoặc phương án chạy local ổn định cho thầy kiểm tra. |

### Tuần 15: 17/08 - 23/08 - Sprint 8 - System test, sửa lỗi, tài liệu

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Sửa lỗi auth/RBAC/chuẩn hiển thị; viết hướng dẫn đăng nhập, tài khoản demo, cách cài đặt project. Hỗ trợ báo cáo phần kiến trúc backend. | Core backend ready, README/hướng dẫn đăng nhập. | Người chấm clone project có thể setup theo README. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Sửa lỗi hotel-info/rooms/search/detail; rà soát migration/seed. Chuẩn bị dữ liệu demo cuối: ảnh, mô tả, phòng, giá. | Data/domain backend ready, hướng dẫn dữ liệu demo. | Không thiếu ảnh/phòng/giá khi demo; seed chạy lại an toàn. |
| BE3 | Booking, Availability, Payment, Customer flow | Sửa lỗi booking/availability/payment; kiểm tra lại toàn bộ overlap và tạo đơn. Viết phần giải thích nghiệp vụ chống đặt trùng cho báo cáo. | Booking backend ready, bảng test overlap cuối. | Giải thích được tại sao không đặt trùng khi nhiều đơn cùng ngày. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Tổng hợp test report cuối: số test case, pass/fail, bug đã sửa, bug còn tồn. Chạy UAT/system test với kịch bản demo đầy đủ. | Test report v1, bug status, checklist nghiệm thu. | Tài liệu kiểm thử gần hoàn chỉnh; bug Critical/High đã đóng. |

### Tuần 16: 24/08 - 30/08 - Sprint 8 - Đóng gói, deploy, bảo vệ

| Thành viên | Trọng tâm tuần | Nhiệm vụ chi tiết | Sản phẩm bàn giao | Tiêu chí nghiệm thu |
|---|---|---|---|---|
| BE1 | Core Backend, Auth, RBAC, Chuẩn View/Layout, Scrum support | Khóa version source, kiểm tra README, .env.example, route list, tài khoản demo; chuẩn bị câu trả lời kiến trúc backend, auth, RBAC. | Source backend sẵn sàng nộp, release checklist backend. | Bản nộp có code, hướng dẫn chạy, tài khoản demo, danh sách route chính. |
| BE2 | Hotel Info, Room, Price, Inventory, UI domain phòng | Backup database, export schema hoặc SQL dump, kiểm tra seed chạy lại. Chuẩn bị dữ liệu demo cuối cho khách sạn/phòng/giá/ảnh. | Database/demo data sẵn sàng, DB checklist. | Có file SQL/seed dự phòng; demo không thiếu dữ liệu. |
| BE3 | Booking, Availability, Payment, Customer flow | Chuẩn bị demo script nghiệp vụ: khách tìm phòng, kiểm tra trống, đặt phòng, hủy đơn; admin xác nhận, thanh toán mô phỏng. Luyện trả lời logic overlap/transaction. | Luồng demo lõi mượt, kịch bản demo cuối. | Demo được liên tục từ customer sang admin trong 7-10 phút. |
| BE4 | QA, DevOps, Admin UI, Test report, Sprint evidence | Chạy final smoke test trên link demo/local backup; tổng hợp minh chứng test, bug report đã đóng, release note, known limitations. Chuẩn bị phương án backup nếu deploy lỗi. | Final test report, release note, known limitations, backup plan. | Bản nộp cuối đủ source, DB, link demo/local, test report, release note. |

## 9. Lịch họp và báo cáo trong Scrum

| Loại họp/báo cáo | Tần suất | Người tham gia | Nội dung bắt buộc | Đầu ra |
|---|---|---|---|---|
| Sprint Planning | 2 tuần/lần, đầu sprint | Cả nhóm | Chốt sprint goal, chọn user story, chia task, xác định rủi ro | Sprint backlog đã phân công |
| Daily Scrum | Mỗi ngày 10-15 phút | Cả nhóm | Hôm qua làm gì, hôm nay làm gì, vướng gì | Board cập nhật, impediment log |
| Backlog Refinement | 1 lần/tuần | PO/Scrum Master/dev liên quan | Làm rõ yêu cầu, acceptance criteria, tách task lớn | Backlog sẵn sàng cho sprint sau |
| Sprint Review | Cuối sprint | Cả nhóm + thầy nếu có | Demo increment, ghi feedback, xác nhận điều đã xong | Feedback + backlog điều chỉnh |
| Retrospective | Sau review | Cả nhóm | Điều tốt/chưa tốt/hành động cải tiến | Action item cho sprint sau |
| Test Report | Cuối mỗi sprint | BE4 tổng hợp, cả nhóm cung cấp | Test case, pass/fail, bug status, evidence | Sprint test report |

## 10. Checklist nghiệm thu theo mốc

| Mốc | Điều kiện nghiệm thu | Không đạt nếu |
|---|---|---|
| Sau Sprint 1 | Repo chạy được, DB migrate/seed được, chuẩn view/layout và test base có sẵn. | Không chạy được project hoặc không có seed cơ bản. |
| Sau Sprint 2 | Customer/admin login đúng role, middleware bảo vệ `/customer` và `/admin`, layout cơ bản có sẵn. | Customer vào được admin hoặc admin/customer redirect sai. |
| Sau Sprint 3 | Admin quản lý được thông tin khách sạn và loại phòng/giá/số lượng. | Còn thiết kế multi-hotel hoặc không cập nhật được room_types. |
| Sau Sprint 4 | Khách xem/lọc phòng, xem detail, điền form đặt phòng. | Danh sách phòng không filter được hoặc detail thiếu dữ liệu cốt lõi. |
| Sau Sprint 5 | Availability đúng overlap, booking tạo bằng transaction, tính tiền đúng. | Sai ca overlap hoặc đặt vượt số lượng phòng. |
| Sau Sprint 6 | Customer xem/hủy đơn; admin xác nhận/hủy đơn, cập nhật thanh toán. | Lộ đơn của khách khác hoặc chuyển trạng thái sai logic. |
| Sau Sprint 7 | Admin có dashboard, quản lý khách hàng, module phụ vừa đủ, staging/RC1. | Dashboard sai số liệu hoặc bug critical booking còn mở. |
| Sau Sprint 8 | Bản nộp cuối đủ source, DB/seed, README, test report, release note, demo script. | Không có hướng dẫn chạy, không có minh chứng test, deploy/local demo lỗi. |

## 11. Rủi ro và cách xử lý

| Rủi ro | Mức độ | Dấu hiệu nhận biết | Cách xử lý trong Scrum |
|---|---|---|---|
| Sai logic availability/overlap | Rất cao | Test overlap fail, đặt được khi hết phòng | Đưa availability vào Sprint 5 với test bắt buộc; không nghiệm thu nếu fail case lõi. |
| Dồn quá nhiều module phụ | Cao | Booking chưa xong nhưng làm promotion/review phức tạp | PO ưu tiên Must-have; cắt nâng cao nếu sprint burndown chậm. |
| Không rõ phân quyền `/customer` và `/admin` | Cao | Customer vào được admin hoặc admin bị chuyển sai | Auth/RBAC hoàn thành sớm Sprint 2; test quyền là bắt buộc. |
| Thiếu giao diện demo | Cao | Route/tính năng có nhưng giao diện chưa hoàn thiện để demo | Mỗi sprint có increment bấm được, không để UI đến cuối. |
| Thiếu tài liệu/test report | Cao | Cuối kỳ mới viết test case | BE4 cập nhật test report cuối mỗi sprint; DoD yêu cầu test/evidence. |
| Deploy lỗi sát ngày | Cao | Staging chưa có hoặc môi trường không giống local | Có staging/backup local từ Sprint 7; tuần 16 chỉ smoke test và đóng gói. |
| Một thành viên ít đóng góp | Trung bình | Ít commit/PR, task không rõ | Sprint planning chia task cá nhân; PR/test case là minh chứng đóng góp. |

## 12. Kịch bản demo cuối đồ án

1. Mở trang public, giới thiệu hệ thống chỉ quản lý 1 khách sạn Homi.
2. Khách xem danh sách phòng, lọc theo giá/sức chứa/tiện ích và xem chi tiết phòng.
3. Khách chọn ngày nhận/trả phòng và kiểm tra phòng trống.
4. Khách đăng ký/đăng nhập tại `/customer/login` hoặc `/customer/register`.
5. Khách tạo đơn đặt phòng, hệ thống tính số đêm, tổng tiền và tạo payment pending.
6. Khách vào `/customer/bookings` xem đơn của mình, xem chi tiết hoặc hủy nếu hợp lệ.
7. Admin đăng nhập `/admin/login`, vào dashboard xem số liệu tổng quan.
8. Admin vào `/admin/bookings` xác nhận đơn, cập nhật thanh toán paid/refunded.
9. Admin vào `/admin/customers` xem khách hàng và lịch sử đặt phòng.
10. Admin vào `/admin/room-types` cập nhật giá/số lượng phòng, chứng minh quản trị được dữ liệu khách sạn.
11. Chạy nhanh test report hoặc cho xem kết quả test tự động/coverage để chứng minh kiểm thử.

## Lưu ý khi bảo vệ

- Không nói hệ thống quản lý nhiều khách sạn. Luôn nhấn mạnh Homi là website đặt phòng cho 1 khách sạn duy nhất.
- Luồng quan trọng nhất cần giải thích chắc là availability overlap và transaction khi tạo booking.
- Nếu bị hỏi tại sao tách `/customer` và `/admin`: để phân quyền, bảo mật và trải nghiệm người dùng rõ ràng.
- Nếu thời gian gấp, ưu tiên hoàn thiện booking/payment/dashboard trước các module phụ.
- Nếu bị hỏi tại sao không có REST API riêng: vì hệ thống chỉ phục vụ 1 giao diện web duy nhất (Blade server-side rendering), không có app di động hay client ngoài cần tiêu thụ JSON, nên route trả thẳng view giúp đơn giản hóa kiến trúc và giảm code thừa.
