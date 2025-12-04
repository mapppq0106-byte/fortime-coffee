<?php
/*
 * DISCOUNT MODEL
 * Vai trò: Quản lý Mã giảm giá (Thêm, Sửa, Xóa mềm, Kiểm tra mã).
 */
class DiscountModel {
    private $db; // Biến chứa đối tượng kết nối Database.

    // Hàm khởi tạo - Chạy đầu tiên khi class được gọi.
    public function __construct() {
        $this->db = new Database(); // Mở kết nối đến cơ sở dữ liệu.
    }

    // --- 1. TÌM MÃ GIẢM GIÁ (Để áp dụng) ---
    public function getDiscountByCode($code) {
        // [QUERY] Tìm dòng mã giảm giá trong bảng 'discounts' dựa vào code.
        // Điều kiện:
        // - code = :code: Mã phải khớp.
        // - is_active = 1: Trạng thái đang Bật (Hoạt động).
        // - is_deleted = 0: Chưa bị xóa vào thùng rác.
        $sql = "SELECT * FROM discounts 
                WHERE code = :code AND is_active = 1 AND is_deleted = 0";
        
        $this->db->query($sql); // Chuẩn bị câu lệnh.
        $this->db->bind(':code', $code); // Điền mã code thật vào chỗ trống :code.
        
        return $this->db->single(); // Thực thi và trả về 1 dòng kết quả (Object).
    }

    // Lấy thông tin chi tiết mã theo ID (Dùng khi bấm nút Sửa)
    public function getDiscountById($id) {
        // [QUERY] Lấy tất cả thông tin của mã có discount_id cụ thể.
        $sql = "SELECT * FROM discounts WHERE discount_id = :id";
        
        $this->db->query($sql); // Chuẩn bị.
        $this->db->bind(':id', $id); // Điền ID.
        return $this->db->single(); // Trả về kết quả.
    }

    // --- 2. LẤY DANH SÁCH (Hàm cũ - chỉ lấy cái chưa xóa) ---
    public function getAllDiscounts() {
        // [QUERY] Lấy tất cả mã chưa xóa (is_deleted = 0).
        // Sắp xếp ID giảm dần (Mới nhất lên đầu).
        $sql = "SELECT * FROM discounts WHERE is_deleted = 0 ORDER BY discount_id DESC";
        
        $this->db->query($sql); // Chuẩn bị.
        return $this->db->resultSet(); // Trả về danh sách nhiều dòng.
    }

    // --- 3. KIỂM TRA TRÙNG MÃ (VALIDATION) ---
    // Dùng khi Thêm mới hoặc Cập nhật để tránh tạo 2 mã giống hệt nhau.
    public function checkCodeExists($code, $excludeId = null) {
        // [QUERY] Tìm xem có ID nào đang dùng mã code này không.
        $sql = "SELECT discount_id FROM discounts WHERE code = :code";
        
        // Logic phụ: Nếu đang Sửa (có $excludeId), thì thêm điều kiện "trừ chính nó ra".
        // Để không báo lỗi trùng với mã cũ của chính mình.
        if ($excludeId) {
            $sql .= " AND discount_id != :id";
        }
        
        $this->db->query($sql); // Chuẩn bị.
        $this->db->bind(':code', $code); // Điền mã code cần kiểm tra.
        
        // Nếu có excludeId thì điền nốt vào.
        if ($excludeId) {
            $this->db->bind(':id', $excludeId);
        }
        
        $this->db->single(); // Thực thi lệnh.
        
        // Trả về True nếu tìm thấy dòng nào trùng (rowCount > 0).
        return $this->db->rowCount() > 0;
    }

    // --- 4. CẬP NHẬT MÃ GIẢM GIÁ (UPDATE) ---
    public function updateDiscount($data) {
        // [QUERY] Cập nhật các thông tin của mã giảm giá có ID tương ứng.
        $sql = "UPDATE discounts 
                SET code = :code,            -- Cập nhật mã code mới
                    type = :type,            -- Loại (tiền mặt / phần trăm)
                    value = :val,            -- Giá trị giảm
                    min_order_value = :min_order, -- Điều kiện đơn tối thiểu
                    is_active = :active      -- Trạng thái bật/tắt
                WHERE discount_id = :id";   // -- Điều kiện: Đúng dòng ID này
        
        $this->db->query($sql); // Chuẩn bị.
        
        // Điền dữ liệu thật vào các chỗ trống (:...)
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':code', $data['code']);
        $this->db->bind(':type', $data['type']);
        $this->db->bind(':val', $data['value']);
        $this->db->bind(':min_order', $data['min_order_value']);
        $this->db->bind(':active', $data['is_active']);
        
        return $this->db->execute(); // Chạy lệnh Update.
    }

    // --- 5. THÊM MÃ GIẢM GIÁ MỚI (CREATE) ---
    public function addDiscount($data) {
        // [QUERY] Thêm dòng mới vào bảng discounts.
        $sql = "INSERT INTO discounts (code, type, value, min_order_value, is_active) 
                VALUES (:code, :type, :val, :min_order, :active)";
        
        $this->db->query($sql); // Chuẩn bị.
        
        // Điền dữ liệu thật vào.
        $this->db->bind(':code', $data['code']);
        $this->db->bind(':type', $data['type']);
        $this->db->bind(':val', $data['value']);
        $this->db->bind(':min_order', $data['min_order_value']);
        $this->db->bind(':active', $data['is_active']);
        
        return $this->db->execute(); // Chạy lệnh Insert.
    }

    // --- 6. CẬP NHẬT TRẠNG THÁI (Bật/Tắt nhanh) ---
    public function updateStatus($id, $status) {
        // [QUERY] Chỉ cập nhật cột is_active.
        $sql = "UPDATE discounts SET is_active = :status WHERE discount_id = :id";
        
        $this->db->query($sql); // Chuẩn bị.
        $this->db->bind(':status', $status); // Điền trạng thái mới (0 hoặc 1).
        $this->db->bind(':id', $id);         // Điền ID mã.
        
        return $this->db->execute(); // Chạy lệnh.
    }

    // --- 7. XÓA MỀM (SOFT DELETE) ---
    public function deleteDiscount($id) {
        // [QUERY] Thay vì xóa hẳn, ta đánh dấu is_deleted = 1 (Đã xóa).
        // Đồng thời set is_active = 0 (Tắt hoạt động) để an toàn.
        $sql = "UPDATE discounts SET is_deleted = 1, is_active = 0 WHERE discount_id = :id";
        
        $this->db->query($sql); // Chuẩn bị.
        $this->db->bind(':id', $id); // Điền ID cần xóa.
        
        return $this->db->execute(); // Chạy lệnh.
    }

    // --- 8. LẤY TẤT CẢ BAO GỒM ĐÃ XÓA (ADMIN VIEW) ---
    public function getAllDiscountsIncludingDeleted() {
        // [QUERY] Lấy hết danh sách để Admin quản lý.
        // Sắp xếp: Mã chưa xóa lên trên (is_deleted ASC), Mã mới nhất lên trên (discount_id DESC).
        $sql = "SELECT * FROM discounts ORDER BY is_deleted ASC, discount_id DESC";
        
        $this->db->query($sql); // Chuẩn bị.
        return $this->db->resultSet(); // Trả về danh sách kết quả.
    }

    // --- 9. KHÔI PHỤC MÃ ĐÃ XÓA (RESTORE) ---
    public function restoreDiscount($id) {
        // [QUERY] Sửa lại is_deleted = 0 (Hồi sinh).
        // Vẫn giữ is_active = 0 (Tắt) để Admin tự bật lại sau khi kiểm tra.
        $sql = "UPDATE discounts SET is_deleted = 0, is_active = 0 WHERE discount_id = :id";
        
        $this->db->query($sql); // Chuẩn bị.
        $this->db->bind(':id', $id); // Điền ID.
        
        return $this->db->execute(); // Chạy lệnh.
    }

    // --- 10. LẤY DANH SÁCH MÃ KHẢ DỤNG (POS) ---
    // Hàm này dùng để hiển thị dropdown chọn mã trên màn hình bán hàng.
    public function getAvailableDiscounts() {
        // [QUERY] Chỉ lấy mã Đang bật (is_active=1) và Chưa xóa (is_deleted=0).
        $sql = "SELECT * FROM discounts 
                WHERE is_active = 1 AND is_deleted = 0 
                ORDER BY discount_id DESC";
        
        $this->db->query($sql); // Chuẩn bị.
        return $this->db->resultSet(); // Trả về danh sách.
    }
}