<?php
/*
 * TABLE MODEL
 * Vai trò: Quản lý sơ đồ bàn trong quán.

Nhiệm vụ chính:

Lấy danh sách bàn để vẽ sơ đồ POS.

Tạo bàn mới (mặc định là Trống).

Đổi tên bàn.

Xóa bàn (Xóa mềm).
 */
class TableModel {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Kết nối DB
    }

    // =========================================================================
    // 1. LẤY DANH SÁCH BÀN (READ)
    // =========================================================================
    public function getTables() {
        // [QUERY] Lấy tất cả bàn chưa xóa (is_deleted = 0).
        // Sắp xếp theo ID tăng dần (Bàn 1 -> Bàn 2 -> ...).
        $sql = "SELECT * FROM tables WHERE is_deleted = 0 ORDER BY table_id ASC";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [LẤY KẾT QUẢ]
        return $this->db->resultSet();
    }

    // =========================================================================
    // 2. THÊM BÀN MỚI (CREATE)
    // =========================================================================
    public function addTable($name) {
        // [QUERY] Thêm tên bàn.
        // Mặc định cột 'status' (trạng thái) sẽ là 'empty' (Trống).
        $sql = "INSERT INTO tables (table_name, status) VALUES (:name, 'empty')";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền tên bàn.
        $this->db->bind(':name', $name);
        
        // [EXECUTE] Chạy lệnh.
        return $this->db->execute();
    }

    // =========================================================================
    // 3. CẬP NHẬT TÊN BÀN (UPDATE)
    // =========================================================================
    public function updateTable($id, $name) {
        // [QUERY] Sửa tên bàn.
        $sql = "UPDATE tables SET table_name = :name WHERE table_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền dữ liệu.
        $this->db->bind(':id', $id);
        $this->db->bind(':name', $name);
        
        // [EXECUTE] Chạy lệnh.
        return $this->db->execute();
    }

    // =========================================================================
    // 4. XÓA BÀN (SOFT DELETE)
    // =========================================================================
    public function deleteTable($id) {
        // [QUERY] Xóa mềm (is_deleted = 1).
        // Đồng thời set status = 'empty' để lỡ bàn đang có khách ảo thì reset về trống luôn cho an toàn.
        $sql = "UPDATE tables SET is_deleted = 1, status = 'empty' WHERE table_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền ID bàn.
        $this->db->bind(':id', $id);
        
        // [EXECUTE] Chạy lệnh.
        return $this->db->execute();
    }

    // =========================================================================
    // 5. KIỂM TRA TRÙNG TÊN BÀN (VALIDATION)
    // =========================================================================
    public function checkTableNameExists($name, $excludeId = null) {
        // [QUERY] Tìm ID của bàn có tên giống và chưa xóa.
        $sql = "SELECT table_id FROM tables WHERE table_name = :name AND is_deleted = 0";
        
        // Nếu đang sửa (có $excludeId), trừ chính nó ra.
        if ($excludeId) {
            $sql .= " AND table_id != :id";
        }
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND]
        $this->db->bind(':name', $name);
        if ($excludeId) {
            $this->db->bind(':id', $excludeId);
        }
        
        // [THỰC THI]
        $this->db->single();
        
        // [KẾT QUẢ] Có > 0 dòng trùng thì trả về true.
        return $this->db->rowCount() > 0;
    }
}