<?php
/*
 * TABLE CONTROLLER
 * -------------------------------------------------------------------------
 * VAI TRÒ VÀ NHIỆM VỤ CHÍNH:
 * 1. Quản lý sơ đồ bàn: Đây là nơi xử lý mọi thao tác liên quan đến bàn ăn trong quán.
 * 2. Bảo mật: Chỉ Admin mới có quyền truy cập (thêm, sửa, xóa bàn).
 * 3. Hiển thị: Lấy danh sách bàn từ Database để hiển thị lên giao diện quản trị.
 * 4. Thêm mới: Tạo bàn mới (ví dụ: Bàn 1, Bàn 2...).
 * 5. Cập nhật: Đổi tên bàn (ví dụ: sửa "Bàn 1" thành "Bàn VIP 1").
 * 6. Xóa: Xóa bàn khỏi danh sách (thực chất là ẩn đi - Soft Delete).
 * -------------------------------------------------------------------------
 */

// Định nghĩa class Table.
// 'class': Từ khóa để tạo ra một khuôn mẫu đối tượng.
// 'extends Controller': Class này kế thừa từ class cha 'Controller'.
// Nhờ kế thừa, nó có thể dùng các "công cụ" của cha như: nạp Model ($this->model), gọi View ($this->view).
class Table extends Controller {
    
    // Khai báo thuộc tính (biến) $tableModel.
    // 'private': Biến này là tài sản riêng, chỉ dùng được bên trong class Table này thôi.
    // Nó sẽ chứa đối tượng Model để làm việc với Database (thêm/sửa/xóa dữ liệu bàn).
    private $tableModel;

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Hàm __construct() chạy tự động ngay lập tức khi class Table được gọi.
    public function __construct() {
        // 1. BẢO MẬT: Gọi hàm restrictToAdmin() từ class cha Controller.
        // Hàm này kiểm tra: Nếu người dùng không phải Admin -> Đuổi về trang Login.
        $this->restrictToAdmin();
        
        // 2. NẠP MODEL
        // Gọi hàm model() từ class cha để nạp file 'app/models/TableModel.php'.
        // Gán kết quả vào biến $this->tableModel để dùng cho các hàm bên dưới.
        $this->tableModel = $this->model('TableModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Hàm này chạy khi truy cập đường dẫn: /table
    // Nhiệm vụ: Lấy danh sách bàn và hiển thị ra màn hình.
    public function index() {
        // Gọi hàm getTables() trong Model để lấy tất cả các bàn từ Database.
        $tables = $this->tableModel->getTables();
        
        // Gọi hàm view() để hiển thị file giao diện 'admin/tables/index.php'.
        // Truyền biến $tables vào view (đặt tên là 'tables' trong mảng dữ liệu).
        $this->view('admin/tables/index', ['tables' => $tables]);
    }

    // --- HÀM THÊM BÀN MỚI ---
    // Hàm này chạy khi người dùng bấm nút "Lưu lại" trên form thêm bàn.
    public function add() {
        // Kiểm tra xem người dùng có gửi dữ liệu bằng phương thức POST không.
        // (POST là cách gửi dữ liệu ngầm, an toàn hơn GET).
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Lấy tên bàn từ form nhập liệu.
            // trim(): Cắt bỏ khoảng trắng thừa ở đầu và cuối (ví dụ: "  Bàn 1  " -> "Bàn 1").
            $name = trim($_POST['table_name']);
            
            // --- 1. KIỂM TRA RỖNG (Validation) ---
            // Nếu người dùng không nhập gì mà bấm Lưu.
            if (empty($name)) {
                // Lưu thông báo lỗi vào Session để hiển thị ra màn hình.
                $_SESSION['error_table_name'] = "Vui lòng nhập tên bàn!";
                // Chuyển hướng quay lại trang quản lý bàn.
                header('location: ' . URLROOT . '/table');
                return; // Dừng hàm tại đây.
            }

            // --- 2. KIỂM TRA TRÙNG TÊN ---
            // Gọi hàm checkTableNameExists() trong Model để xem tên này đã có chưa.
            if ($this->tableModel->checkTableNameExists($name)) {
                // Nếu trùng -> Báo lỗi.
                $_SESSION['error_table_name'] = "Tên bàn '$name' đã tồn tại!";
                header('location: ' . URLROOT . '/table');
                return;
            }
            
            // --- 3. THÊM VÀO DATABASE ---
            // Nếu mọi thứ ổn -> Gọi hàm addTable() để lưu.
            if ($this->tableModel->addTable($name)) {
                // Nếu thành công -> Xóa thông báo lỗi cũ (nếu có).
                unset($_SESSION['error_table_name']);
                // Quay lại trang danh sách.
                header('location: ' . URLROOT . '/table');
            } else {
                // Nếu lỗi hệ thống (DB chết...) -> Dừng chương trình và báo lỗi.
                die('Lỗi hệ thống khi thêm bàn');
            }
        } else {
            // Nếu không phải POST (truy cập trực tiếp link) -> Quay về trang danh sách.
            header('location: ' . URLROOT . '/table');
        }
    }

    // --- HÀM CẬP NHẬT TÊN BÀN ---
    // Nhận tham số $id từ URL (ví dụ: /table/edit/5 -> $id = 5).
    public function edit($id) {
        // Chỉ xử lý khi có request POST (bấm nút Cập nhật).
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Lấy tên mới từ form.
            $name = trim($_POST['table_name']);
            
            // --- 1. KIỂM TRA RỖNG ---
            if (empty($name)) {
                $_SESSION['error_table_name'] = "Vui lòng nhập tên bàn!";
                header('location: ' . URLROOT . '/table');
                return;
            }

            // --- 2. KIỂM TRA TRÙNG TÊN (Nâng cao) ---
            // Gọi hàm checkTableNameExists với tham số thứ 2 là $id.
            // Ý nghĩa: Kiểm tra trùng tên nhưng TRỪ CHÍNH BÀN ĐANG SỬA ra.
            // (Để tránh lỗi báo trùng với tên cũ của chính nó).
            if ($this->tableModel->checkTableNameExists($name, $id)) {
                $_SESSION['error_table_name'] = "Tên bàn '$name' đã tồn tại! Vui lòng chọn tên khác.";
                header('location: ' . URLROOT . '/table');
                return;
            }
            
            // --- 3. CẬP NHẬT VÀO DATABASE ---
            // Gọi hàm updateTable() để sửa tên.
            if ($this->tableModel->updateTable($id, $name)) {
                unset($_SESSION['error_table_name']); // Xóa lỗi cũ.
                header('location: ' . URLROOT . '/table');
            } else {
                die('Lỗi hệ thống khi cập nhật bàn');
            }
        } else {
             // Nếu không phải POST -> Quay về trang danh sách.
             header('location: ' . URLROOT . '/table');
        }
    }

    // --- HÀM XÓA BÀN ---
    // Nhận tham số $id từ URL.
    public function delete($id) {
        // Gọi hàm deleteTable() trong Model.
        // Lưu ý: Thường là Xóa mềm (Soft Delete) - chỉ ẩn đi, không mất vĩnh viễn để giữ lịch sử đơn hàng.
        $this->tableModel->deleteTable($id);
        
        // Hàm redirect() giúp chuyển hướng nhanh (được định nghĩa trong helpers/url_helper.php).
        redirect('table');
    }
}