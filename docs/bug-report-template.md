# BUG REPORT TEMPLATE – HOMI BACKEND

> Sao chép mẫu này cho mỗi bug mới, đặt trong `docs/bug-reports/`, đặt tên file
> theo định dạng: `BUG-<MODULE>-<SO_THU_TU>.md` (ví dụ: `BUG-AUTH-01.md`).
> Người gây lỗi **không tự đóng** bug do mình gây ra — BE4 hoặc người review
> xác nhận retest rồi mới đóng.

---

## Thông tin chung

| Trường | Nội dung |
|---|---|
| **Mã bug** | BUG-XXX-NN |
| **Tiêu đề** | Mô tả ngắn gọn vấn đề |
| **Module / API** | Ví dụ: Auth – `PUT /api/v1/profile` |
| **Người phát hiện** | BE4 |
| **Người sửa** | (điền tên) |
| **Ngày phát hiện** | yyyy-mm-dd |
| **Ngày sửa xong** | yyyy-mm-dd |
| **Mức độ** | Critical / High / Medium / Low |
| **Trạng thái** | Open / In Progress / Fixed / Retest / Closed / Won't fix |

### Mức độ (độ ưu tiên)

| Mức độ | Tiêu chí |
|---|---|
| **Critical** | Sập server, lộ dữ liệu nhạy cảm (password, token), sai logic lõi (overlap, tính tiền), chặn merge |
| **High** | Sai response/luồng chính nhưng có thể workaround tạm, chặn test các module khác |
| **Medium** | Sai dữ liệu phụ, message lỗi chưa chuẩn, thiếu validate ở field không quan trọng |
| **Low** | Lỗi chính tả, format, tài liệu, không ảnh hưởng chức năng |

---

## Mô tả lỗi

### Bước tái hiện (steps to reproduce)
1. ...
2. ...
3. ...

### Kết quả thực tế (actual result)
- HTTP status: ...
- Response body: ...

### Kết quả mong đợi (expected result)
- HTTP status: ...
- Response body: ...

### Dữ liệu test sử dụng
```json
{
  "field": "value"
}
```

### Môi trường
- Branch/commit: ...
- Database: SQLite in-memory (test) / SQLite file (local)
- Test bằng: Pest test `tests/...` hoặc Postman request `...`

### Ảnh / log minh chứng (evidence)
- Đường dẫn file log: `docs/test-evidence/...`
- (Có thể đính kèm ảnh chụp Postman nếu chạy trên Postman Desktop)

---

## Phân tích nguyên nhân (root cause)

(BE phụ trách module điền sau khi tìm ra nguyên nhân)

## Hướng sửa / đã sửa

(Code thay đổi ở file nào, dòng nào; link PR nếu có)

## Kết quả retest

| Người retest | Ngày | Kết quả | Ghi chú |
|---|---|---|---|
| | | Pass / Fail | |
