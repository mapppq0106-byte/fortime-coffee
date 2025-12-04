<?php
/*
 * ATTENDANCE MODEL
 * Vai trò: Ghi nhận lịch sử Vào/Ra (Log) của nhân viên.

Nhiệm vụ chính:

Check-in: Ghi giờ bắt đầu làm.

Check-out: Ghi giờ kết thúc (Cập nhật vào dòng đã Check-in hoặc tạo mới nếu quên).

Lịch sử: Lấy danh sách chấm công để Admin xem báo cáo.
 */
class AttendanceModel {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Kết nối DB
    }

    // =========================================================================
    // 1. CHẤM CÔNG VÀO (CHECK-IN)
    // =========================================================================
    public function checkIn($name) {
        $today = date("Y-m-d"); // Lấy ngày hôm nay
        $now = date("H:i:s");   // Lấy giờ hiện tại

        // [QUERY] Tạo một dòng chấm công mới
        // Chỉ điền giờ vào (check_in_time), giờ ra để trống
        $this->db->query("INSERT INTO staff_attendance (staff_name, check_in_time, created_at) 
                          VALUES (:name, :time, :date)");
        
        // [BIND] Điền dữ liệu
        $this->db->bind(':name', $name);
        $this->db->bind(':time', $now);
        $this->db->bind(':date', $today);
        
        // [EXECUTE]
        return $this->db->execute();
    }

    // =========================================================================
    // 2. CHẤM CÔNG RA (CHECK-OUT)
    // =========================================================================
    public function checkOut($name) {
        $today = date("Y-m-d");
        $now = date("H:i:s");

        // BƯỚC 1: Tìm xem hôm nay nhân viên này đã Check-in chưa?
        // Điều kiện: Cùng tên + Cùng ngày + Giờ ra còn trống (chưa out)
        // Lấy dòng mới nhất (ORDER BY id DESC) đề phòng check-in nhiều lần
        $this->db->query("SELECT id FROM staff_attendance 
                          WHERE staff_name = :name AND created_at = :date AND check_out_time IS NULL 
                          ORDER BY id DESC LIMIT 1");
        
        $this->db->bind(':name', $name);
        $this->db->bind(':date', $today);
        $row = $this->db->single();

        // BƯỚC 2: Xử lý Logic
        if ($row) {
            // TRƯỜNG HỢP A: Tìm thấy dòng Check-in hợp lệ -> CẬP NHẬT giờ ra
            // [QUERY] Update giờ check_out_time vào đúng dòng có ID đó
            $this->db->query("UPDATE staff_attendance SET check_out_time = :time WHERE id = :id");
            $this->db->bind(':time', $now);
            $this->db->bind(':id', $row->id);
            return $this->db->execute();
        } else {
            // TRƯỜNG HỢP B: Không thấy Check-in (Quên Check-in) -> TẠO MỚI dòng Check-out
            // [QUERY] Insert dòng mới, nhưng lần này điền vào cột check_out_time
            $this->db->query("INSERT INTO staff_attendance (staff_name, check_out_time, created_at) 
                              VALUES (:name, :time, :date)");
            $this->db->bind(':name', $name);
            $this->db->bind(':time', $now);
            $this->db->bind(':date', $today);
            return $this->db->execute();
        }
    }

    // =========================================================================
    // 3. XEM LỊCH SỬ (HISTORY - REPORT)
    // =========================================================================
    public function getHistory($date = null, $keyword = '') {
        // [QUERY] Kỹ thuật "WHERE 1=1" giúp nối chuỗi điều kiện dễ dàng hơn
        $sql = "SELECT * FROM staff_attendance WHERE 1=1";
        
        // Nếu có lọc theo ngày -> Thêm điều kiện ngày
        if ($date) {
            $sql .= " AND created_at = :date";
        }
        // Nếu có tìm kiếm tên -> Thêm điều kiện LIKE
        if (!empty($keyword)) {
            $sql .= " AND staff_name LIKE :keyword";
        }
        
        // Sắp xếp: Mới nhất lên đầu
        $sql .= " ORDER BY created_at DESC, check_in_time DESC"; 
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền các tham số lọc (nếu có)
        if ($date) $this->db->bind(':date', $date);
        if (!empty($keyword)) $this->db->bind(':keyword', "%$keyword%");
        
        // [LẤY KẾT QUẢ]
        return $this->db->resultSet();
    }
}