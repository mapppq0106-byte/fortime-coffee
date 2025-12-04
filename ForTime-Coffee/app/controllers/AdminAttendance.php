<?php
// Định nghĩa class AdminAttendance.
//Vai trò: Controller này dành riêng cho Admin để xem báo cáo chấm công.

//Nhiệm vụ chính:

//Kiểm tra quyền truy cập (Chỉ Admin mới được vào).

//Nhận yêu cầu lọc theo ngày hoặc tìm kiếm tên nhân viên từ URL.

//Gọi Model để lấy dữ liệu lịch sử chấm công.

//Gửi dữ liệu sang View để hiển thị bảng thống kê.//
// 'extends Controller': Kế thừa từ class cha 'Controller' (trong core/Controller.php).
// Điều này giúp class này có thể dùng các hàm có sẵn của cha như: model(), view(), restrictToAdmin().
class AdminAttendance extends Controller {
    
    // Khai báo thuộc tính (biến) $attendanceModel.
    // 'private': Biến này là riêng tư, chỉ được sử dụng trong nội bộ class này.
    // Nó sẽ dùng để chứa đối tượng Model chấm công sau khi khởi tạo.
    private $attendanceModel;

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Hàm __construct() là một "Magic Method" trong PHP.
    // Nó sẽ TỰ ĐỘNG CHẠY ngay khi class này được gọi (khởi tạo).
    public function __construct() {
        // Gọi hàm bảo vệ từ class cha (Controller).
        // Hàm này kiểm tra nếu không phải Admin thì đá về trang Login ngay lập tức.
        $this->restrictToAdmin(); 

        // Khởi tạo Model 'AttendanceModel' và gán vào biến $attendanceModel.
        // Hàm $this->model() cũng được kế thừa từ class cha Controller.
        $this->attendanceModel = $this->model('AttendanceModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Khi người dùng truy cập URL: /AdminAttendance, hàm index() sẽ được chạy.
    public function index() {
        
        // 1. XỬ LÝ NGÀY LỌC (DATE FILTER)
        // Sử dụng toán tử 3 ngôi (Ternary Operator): (Điều kiện ? Đúng : Sai)
        // Kiểm tra: Nếu trên URL có tham số 'date' (isset($_GET['date']))
        // -> Thì lấy giá trị đó.
        // -> Ngược lại (người dùng chưa chọn ngày) -> Lấy ngày hiện tại bằng hàm date('Y-m-d').
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        // 2. XỬ LÝ TỪ KHÓA TÌM KIẾM (SEARCH KEYWORD)
        // Kiểm tra: Nếu trên URL có tham số 'search'
        // -> Thì lấy giá trị đó và cắt khoảng trắng thừa (hàm trim).
        // -> Ngược lại -> Gán bằng chuỗi rỗng '' (không tìm kiếm).
        $keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

        // 3. GỌI MODEL ĐỂ LẤY DỮ LIỆU
        // Gọi hàm getHistory() trong AttendanceModel.
        // Truyền vào ngày ($date) và từ khóa ($keyword) để lọc dữ liệu từ Database.
        // Kết quả trả về (danh sách chấm công) được lưu vào biến $logs.
        $logs = $this->attendanceModel->getHistory($date, $keyword);

        // 4. ĐÓNG GÓI DỮ LIỆU (PACKING DATA)
        // Tạo một mảng liên hợp (Associative Array) tên là $data.
        // Mảng này chứa tất cả những gì cần gửi sang View để hiển thị.
        $data = [
            'logs' => $logs,                // Danh sách lịch sử chấm công
            'date_filter' => $date,         // Ngày đang chọn (để giữ lại trên ô input)
            'search_keyword' => $keyword    // Từ khóa đang tìm (để giữ lại trên ô input)
        ];

        // 5. HIỂN THỊ GIAO DIỆN (RENDER VIEW)
        // Gọi hàm view() từ class cha.
        // - Tham số 1: Đường dẫn tới file giao diện ('admin/attendance/list').
        // - Tham số 2: Mảng dữ liệu $data ở trên.
        // File view sẽ nhận được biến $data và dùng nó để vẽ bảng HTML.
        $this->view('admin/attendance/list', $data);
    }
}