
<?php
/*
 * CASH MODEL
 * Class này chịu trách nhiệm làm việc với bảng 'cash_sessions' trong Database.
 * Chức năng: Quản lý toàn bộ quy trình của một Ca làm việc: Mở ca -> Bán hàng (tính tiền) -> Chốt ca.
 */
class CashModel {
    private $db; // Khai báo biến $db. Biến này sẽ chứa "công cụ" kết nối cơ sở dữ liệu.

    // Hàm khởi tạo (Constructor) - Chạy đầu tiên khi file này được gọi
    public function __construct() {
        $this->db = new Database(); // Khởi tạo class Database (core) và gán vào biến $db để dùng sau này.
    }

    // --- 1. LẤY CA ĐANG HOẠT ĐỘNG ---
    public function getCurrentSession() {
        // Soạn câu lệnh SQL (Chưa chạy ngay, chỉ là chuỗi văn bản):
        // "Lấy tất cả cột" (SELECT *)
        // "Từ bảng phiên làm việc" (FROM cash_sessions)
        // "Điều kiện: Giờ kết thúc còn Trống" (WHERE end_time IS NULL) -> Nghĩa là chưa chốt ca.
        // "Sắp xếp: Giờ bắt đầu giảm dần" (ORDER BY... DESC) -> Lấy cái mới nhất.
        // "Giới hạn: 1 dòng" (LIMIT 1).
        $sql = "SELECT * FROM cash_sessions WHERE end_time IS NULL ORDER BY start_time DESC LIMIT 1";
        
        $this->db->query($sql); // Đưa câu lệnh SQL vào Database để chuẩn bị.
        return $this->db->single(); // Thực thi lệnh và lấy về 1 dòng kết quả (Object).
    }

    // --- 2. MỞ CA MỚI ---
    public function startSession($userId, $openingCash) {
        // Soạn lệnh INSERT (Thêm mới):
        // Thêm vào bảng 'cash_sessions' các cột (user_id, opening_cash, start_time).
        // VALUES (:uid, :cash, NOW()): Các giá trị tương ứng. NOW() là hàm lấy giờ hiện tại của MySQL.
        $sql = "INSERT INTO cash_sessions (user_id, opening_cash, start_time) 
                VALUES (:uid, :cash, NOW())";
        
        $this->db->query($sql); // Chuẩn bị lệnh.
        $this->db->bind(':uid', $userId);      // Điền ID người dùng thật vào chỗ :uid.
        $this->db->bind(':cash', $openingCash); // Điền số tiền đầu ca thật vào chỗ :cash.
        
        return $this->db->execute(); // Bấm nút "Chạy" để lưu vào DB. Trả về True/False.
    }

    // --- 3. TÍNH DOANH THU CHI TIẾT (Logic khó) ---
    public function getCurrentSessionSalesBreakdown($startTime) {
        // Soạn lệnh SQL tính toán:
        // COALESCE(..., 0): Nếu kết quả là Null (không bán được gì) thì trả về số 0.
        // SUM(final_amount): Cộng tổng tiền các đơn hàng.
        // CASE WHEN...: Lệnh "Nếu...thì...".
        // - total_cash: Chỉ cộng nếu phương thức là 'cash'.
        // - total_transfer: Chỉ cộng nếu phương thức là 'transfer'.
        $sql = "SELECT 
                    COALESCE(SUM(final_amount), 0) as total_all, 
                    COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN final_amount ELSE 0 END), 0) as total_cash,
                    COALESCE(SUM(CASE WHEN payment_method = 'transfer' THEN final_amount ELSE 0 END), 0) as total_transfer
                FROM orders 
                WHERE status = 'paid'          -- Chỉ tính đơn ĐÃ THANH TOÁN (quan trọng).
                AND payment_time >= :start_time";  // Chỉ tính các đơn bán SAU khi mở ca. 

        
        $this->db->query($sql); // Chuẩn bị.
        $this->db->bind(':start_time', $startTime); // Điền giờ mở ca vào điều kiện.
        return $this->db->single(); // Lấy về 1 dòng kết quả chứa 3 con số (Tổng, Tiền mặt, CK).
    }

    // --- 4. CHỐT CA ---
    public function closeSession($sessionId, $userId, $totalSales, $actualCash, $note) {
        // Soạn lệnh UPDATE (Cập nhật):
        // Cập nhật dòng có session_id = :sid.
        // Điền giờ kết thúc (end_time = NOW()), tổng doanh thu, người chốt, tiền thực tế, ghi chú.
        $sql = "UPDATE cash_sessions 
                SET end_time = NOW(), 
                    total_sales = :sales, 
                    close_user_id = :uid, 
                    actual_cash = :actual, 
                    note = :note 
                WHERE session_id = :sid";
        
        $this->db->query($sql); // Chuẩn bị.
        // Điền dữ liệu thật vào các chỗ trống (:...)
        $this->db->bind(':sales', $totalSales);
        $this->db->bind(':uid', $userId);
        $this->db->bind(':actual', $actualCash);
        $this->db->bind(':note', $note);
        $this->db->bind(':sid', $sessionId);
        
        return $this->db->execute(); // Chạy lệnh cập nhật.
    }

    // --- 5. LẤY LỊCH SỬ CÁC CA ĐÃ ĐÓNG ---
    public function getClosedSessions($limit = null) {
        // Soạn lệnh SELECT kết hợp JOIN bảng:
        // Lấy thông tin ca (cs.*) và Tên nhân viên (u.full_name).
        // JOIN bảng users (u) với bảng cash_sessions (cs) thông qua user_id.
        // Điều kiện: end_time IS NOT NULL (Nghĩa là ca đã đóng xong).
        $sql = "SELECT cs.*, u.full_name 
                FROM cash_sessions cs
                JOIN users u ON cs.user_id = u.user_id
                WHERE cs.end_time IS NOT NULL 
                ORDER BY cs.end_time DESC"; // Sắp xếp ngày gần nhất lên đầu.
        
        // Nếu biến $limit có giá trị (ví dụ lấy 5 dòng) -> Nối thêm chuỗi "LIMIT ..." vào câu SQL.
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }
        
        $this->db->query($sql); // Chuẩn bị.
        
        // Nếu có limit thì điền số limit vào.
        if ($limit !== null) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT); // PARAM_INT: Báo rằng đây là số nguyên.
        }
        
        return $this->db->resultSet(); // Lấy về danh sách nhiều dòng (mảng các Object).
    }

    // --- 6. XEM CHI TIẾT MÓN TRONG CA ---
    public function getItemsInSession($startTime, $endTime) {
        // Lệnh thống kê phức tạp:
        // - Lấy tên món, ghi chú.
        // - SUM(quantity): Cộng tổng số lượng bán được của món đó.
        // - SUM(... * unit_price): Cộng tổng tiền thu được từ món đó.
        // - JOIN 3 bảng: chi tiết đơn (od) -> đơn hàng (o) -> sản phẩm (p).
        // - GROUP BY: Gom nhóm lại (ví dụ bán 10 ly cafe thì gom lại thành 1 dòng "Cafe x 10").
        $sql = "SELECT p.product_name, 
                       od.note, 
                       SUM(od.quantity) as qty, 
                       SUM(od.quantity * od.unit_price) as subtotal
                FROM order_details od
                JOIN orders o ON od.order_id = o.order_id
                JOIN products p ON od.product_id = p.product_id
                WHERE o.status = 'paid' 
                AND o.payment_time >= :start_time 
                AND o.payment_time <= :end_time
                GROUP BY p.product_id, od.note
                ORDER BY qty DESC"; // Món nào bán nhiều nhất lên đầu.
        
        $this->db->query($sql); // Chuẩn bị.
        $this->db->bind(':start_time', $startTime); // Điền giờ bắt đầu.
        $this->db->bind(':end_time', $endTime);     // Điền giờ kết thúc.
        
        return $this->db->resultSet(); // Trả về danh sách kết quả.
    }
}