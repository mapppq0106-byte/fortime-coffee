<?php
//Vai trò: Điều khiển màn hình bán hàng (POS - Point of Sale).

//Nhiệm vụ chính:

//Hiển thị giao diện POS: Load danh sách Bàn, Danh mục, Sản phẩm, Mã giảm giá.

//Xử lý đơn hàng: Thêm món, sửa món, xóa món, tăng giảm số lượng.

//Quản lý bàn: Chọn bàn, xem trạng thái bàn, chuyển bàn.

//Thanh toán: Áp dụng mã giảm giá, tính tổng tiền, thực hiện thanh toán (Tiền mặt/CK).

//Kiểm tra Ca làm việc: Đảm bảo nhân viên đã mở ca trước khi bán hàng.
/*
 * POS CONTROLLER
 */
class PosController extends Controller {
    
    // Khai báo các thuộc tính (biến) để chứa các Model sẽ dùng.
    // 'private': Biến riêng tư, chỉ dùng nội bộ.
    private $tableModel;    // Model xử lý Bàn.
    private $categoryModel; // Model xử lý Danh mục.
    private $productModel;  // Model xử lý Sản phẩm.
    private $cashModel;     // Model xử lý Ca làm việc (Tiền nong).
    private $userModel;     // Model xử lý Tài khoản.
    private $discountModel; // Model xử lý Mã giảm giá (Thêm mới để dùng cho dropdown).

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Chạy tự động ngay khi vào trang POS.
    public function __construct() {
        
        // 1. KIỂM TRA ĐĂNG NHẬP
        // Nếu chưa có session user_id -> Chưa đăng nhập -> Đá về trang Login.
        if (!isset($_SESSION['user_id'])) {
            header('location: ' . URLROOT . '/auth/login');
            exit; // Dừng chương trình.
        }

        // 2. LOAD CÁC MODEL CẦN THIẾT
        // Gọi hàm model() từ class cha Controller để nạp file và khởi tạo đối tượng.
        $this->tableModel = $this->model('TableModel');
        $this->categoryModel = $this->model('CategoryModel');
        $this->productModel = $this->model('ProductModel');
        $this->userModel = $this->model('UserModel');
        
        // [MỚI] Luôn load CashModel để kiểm tra ca làm việc cho cả Admin lẫn Staff.
        $this->cashModel = $this->model('CashModel'); 

        // 3. LOGIC KIỂM TRA CA LÀM VIỆC
        // Nếu là Nhân viên (role_id != 1 - Admin là 1)
        if ($_SESSION['role_id'] != 1) { 
            // Kiểm tra xem có ca nào đang mở không (chưa có giờ kết thúc).
            $activeSession = $this->cashModel->getCurrentSession();
            
            // Nếu chưa mở ca -> Bắt buộc chuyển hướng sang trang Mở ca.
            if (!$activeSession) {
                header('location: ' . URLROOT . '/shift/open');
                exit;
            }
        }
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Hiển thị giao diện bán hàng chính.
    public function index() {
        // Lấy dữ liệu từ các Model để vẽ giao diện.
        $tables = $this->tableModel->getTables();       // Lấy danh sách bàn.
        $categories = $this->categoryModel->getCategories(); // Lấy danh mục món.
        $products = $this->productModel->getProducts(); // Lấy danh sách món ăn.
        $toppings = $this->productModel->getToppings(); // Lấy danh sách topping.

        // [MỚI] Lấy danh sách mã giảm giá khả dụng để hiển thị Dropdown chọn mã.
        // Cần khởi tạo model trước khi dùng.
        $this->discountModel = $this->model('DiscountModel'); 
        $discounts = $this->discountModel->getAvailableDiscounts();

        // Đóng gói tất cả dữ liệu vào mảng $data.
        $data = [
            'tables' => $tables,
            'categories' => $categories,
            'products' => $products,
            'toppings' => $toppings,
            'discounts' => $discounts
        ];

        // Gọi View để hiển thị giao diện POS với dữ liệu đã chuẩn bị.
        $this->view('pos/index', $data);
    }

    // ============================================================
    // CÁC API AJAX (Xử lý ngầm, không load lại trang)
    // ============================================================

    // --- API: LẤY THÔNG TIN ĐƠN HÀNG CỦA BÀN ---
    // Được gọi khi bấm chọn một bàn trên sơ đồ.
    public function getTableOrder($tableId) {
        $orderModel = $this->model('OrderModel');
        
        // Tìm đơn hàng chưa thanh toán của bàn này.
        $order = $orderModel->getUnpaidOrder($tableId); 
        
        // Nếu có đơn hàng...
        if ($order) {
            // Lấy chi tiết các món trong đơn.
            $items = $orderModel->getOrderDetails($order->order_id);
            $total = $order->total_amount; // Tổng tiền tạm tính.
            $discountAmount = 0; // Số tiền được giảm.
            
            // Tính toán giảm giá nếu có mã.
            if ($order->discount_id) {
                if ($order->discount_type == 'percentage') {
                    // Nếu là %: Giảm = Tổng * (Số % / 100).
                    $discountAmount = $total * ($order->discount_value / 100);
                } else {
                    // Nếu là tiền mặt: Giảm đúng số tiền đó.
                    $discountAmount = $order->discount_value;
                }
            }
            
            // Tính tổng tiền phải trả (Không được âm).
            $finalAmount = $total - $discountAmount;
            if ($finalAmount < 0) $finalAmount = 0;

            // Trả về dữ liệu dạng JSON cho JS xử lý hiển thị.
            echo json_encode([
                'status' => 'success', 
                'order_id' => $order->order_id,
                'items' => $items,
                'total' => $total,
                'discount_amount' => $discountAmount,
                'discount_code' => $order->discount_code,
                'final_amount' => $finalAmount
            ]);
        } else {
            // Nếu bàn trống -> Trả về status 'empty'.
            echo json_encode(['status' => 'empty']);
        }
    }

    // --- API: THÊM MÓN VÀO ĐƠN ---
    // Được gọi khi bấm vào hình món ăn.
    public function addToOrder() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // [MỚI] RÀNG BUỘC: Kiểm tra lại xem đã mở ca chưa (để chắc chắn).
            if (!$this->cashModel->getCurrentSession()) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Chưa mở ca làm việc! Vui lòng vào "Cài đặt -> Báo cáo kết ca" để khai báo tiền đầu ca trước khi bán hàng.'
                ]);
                return;
            }

            // Lấy dữ liệu từ JS gửi lên.
            $tableId = $_POST['table_id'];
            $productId = $_POST['product_id'];
            $price = $_POST['price']; 
            $userId = $_SESSION['user_id'];
            $defaultNote = 'Size M'; // Ghi chú mặc định.

            $orderModel = $this->model('OrderModel');
            
            // Kiểm tra xem bàn này đã có đơn chưa.
            $order = $orderModel->getUnpaidOrder($tableId);
            $isNewOrder = false; // Cờ đánh dấu đơn mới.

            // Nếu chưa có đơn -> Tạo đơn mới.
            if (!$order) {
                $orderId = $orderModel->createOrder($userId, $tableId);
                $isNewOrder = true;
            } else {
                // Nếu có rồi -> Lấy ID đơn cũ.
                $orderId = $order->order_id;
            }

            // Nếu có ID đơn hàng (tạo mới hoặc lấy cũ thành công).
            if ($orderId) {
                // Gọi hàm thêm món (hoặc cộng dồn số lượng) trong Model.
                $orderModel->addNumItem($orderId, $productId, $price, $defaultNote);
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Đã thêm món',
                    'is_new_order' => $isNewOrder // Trả về cờ này để JS biết có cần reload bàn không.
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi tạo đơn']);
            }
        }
    }

    // --- API: CẬP NHẬT MÓN (Size/Topping/Ghi chú) ---
    // Được gọi khi bấm nút Sửa (hình cây bút).
    public function updateOrderItem() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Kiểm tra ca làm việc.
            if (!$this->cashModel->getCurrentSession()) {
                echo json_encode(['status' => 'error', 'message' => 'Chưa mở ca làm việc!']);
                return;
            }

            // Lấy dữ liệu chi tiết món cần sửa.
            $detailId = $_POST['detail_id'];
            $orderId = $_POST['order_id'];
            $basePrice = (int)$_POST['base_price']; // Giá gốc.
            $extraPrice = (int)$_POST['extra_price']; // Giá phụ thu (Size L, Topping).
            
            // Lấy các tùy chọn.
            $size = $_POST['size'];
            $toppingList = $_POST['toppings']; 
            $customNote = isset($_POST['custom_note']) ? trim($_POST['custom_note']) : '';

            // Tính giá mới.
            $finalPrice = $basePrice + $extraPrice;
            
            // Tạo chuỗi ghi chú mới (Ví dụ: "Size L, Trân châu đen, Ít đá").
            $noteParts = [];
            if ($size === 'L') $noteParts[] = "Size L";
            else $noteParts[] = "Size M";

            if (!empty($toppingList)) $noteParts[] = $toppingList;
            if (!empty($customNote)) $noteParts[] = $customNote;
            
            $newNote = implode(', ', $noteParts); // Nối mảng thành chuỗi.

            // Gọi Model cập nhật.
            $orderModel = $this->model('OrderModel');
            if ($orderModel->updateOrderDetail($detailId, $orderId, $finalPrice, $newNote)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
    }

    // --- API: XÓA MÓN ---
    public function deleteItem() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $detailId = $_POST['detail_id'];
            $orderId = $_POST['order_id'];
            $orderModel = $this->model('OrderModel');
            
            // Gọi hàm xóa chi tiết đơn.
            if ($orderModel->deleteOrderDetail($detailId, $orderId)) {
                // Kiểm tra xem đơn hàng sau khi xóa có bị hủy luôn không (do hết món).
                $db = new Database();
                $db->query("SELECT status FROM ORDERS WHERE order_id = :oid");
                $db->bind(':oid', $orderId);
                $check = $db->single();
                
                // Nếu trạng thái đơn chuyển thành 'canceled' -> Đơn rỗng.
                $isEmpty = ($check->status == 'canceled');

                echo json_encode(['status' => 'success', 'is_empty' => $isEmpty]);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
    }

    // --- API: CHUYỂN BÀN / GỘP BÀN ---
    public function changeTable() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $fromTableId = $_POST['from_table_id']; // Bàn đi.
            $toTableId = $_POST['to_table_id'];     // Bàn đến.
            
            $orderModel = $this->model('OrderModel');
            
            // Gọi hàm xử lý chuyển bàn phức tạp trong Model.
            if ($orderModel->moveTable($fromTableId, $toTableId)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi xử lý DB']);
            }
        }
    }

    // --- API: ÁP DỤNG / HỦY MÃ GIẢM GIÁ ---
    public function applyDiscount() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tableId = $_POST['table_id'];
            $code = trim($_POST['code']); // Mã code nhập vào.
            
            $orderModel = $this->model('OrderModel');
            $this->discountModel = $this->model('DiscountModel'); 
            
            // Lấy đơn hàng hiện tại.
            $order = $orderModel->getUnpaidOrder($tableId);
            if (!$order) {
                echo json_encode(['status' => 'error', 'message' => 'Bàn trống!']);
                return;
            }

            // Nếu mã rỗng -> Nghĩa là người dùng muốn HỦY mã đang dùng.
            if (empty($code)) {
                $orderModel->removeDiscount($order->order_id);
                echo json_encode(['status' => 'success', 'message' => 'Đã hủy mã giảm giá']);
                return;
            }

            // Nếu có mã -> Kiểm tra tính hợp lệ.
            $discount = $this->discountModel->getDiscountByCode($code);
            
            if ($discount) {
                // Kiểm tra điều kiện đơn tối thiểu (nếu có).
                if (isset($discount->min_order_value) && $discount->min_order_value > 0) {
                    $currentTotal = $order->total_amount;
                    if ($currentTotal < $discount->min_order_value) {
                        echo json_encode([
                            'status' => 'error', 
                            'message' => 'Mã này chỉ áp dụng cho đơn từ ' . number_format($discount->min_order_value) . 'đ trở lên!'
                        ]);
                        return; // Dừng nếu không đủ điều kiện.
                    }
                }
                
                // Áp dụng mã thành công.
                $orderModel->applyDiscount($order->order_id, $discount->discount_id);
                echo json_encode(['status' => 'success', 'message' => 'Áp dụng thành công!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Mã không tồn tại hoặc hết hạn!']);
            }
        }
    }

    // --- API: THANH TOÁN (CHECKOUT) ---
    public function checkout() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // [MỚI] RÀNG BUỘC: Kiểm tra ca làm việc.
            if (!$this->cashModel->getCurrentSession()) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Chưa mở ca làm việc! Không thể thực hiện thanh toán.'
                ]);
                return;
            }

            $tableId = $_POST['table_id'];
            // [MỚI] Lấy phương thức thanh toán ('cash' hoặc 'transfer').
            $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';

            $orderModel = $this->model('OrderModel');
            $order = $orderModel->getUnpaidOrder($tableId);
            
            if ($order) {
                // Tính toán lại số tiền cuối cùng.
                $total = $order->total_amount;
                $discountAmount = 0;
                
                if ($order->discount_id) {
                     if ($order->discount_type == 'percentage') {
                        $discountAmount = $total * ($order->discount_value / 100);
                    } else {
                        $discountAmount = $order->discount_value;
                    }
                }
                $finalAmount = $total - $discountAmount;
                if($finalAmount < 0) $finalAmount = 0;

                // Gọi Model để thực hiện thanh toán (Cập nhật trạng thái, lưu tiền, đóng bàn).
                if ($orderModel->checkoutOrder($order->order_id, $finalAmount, $paymentMethod)) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Lỗi DB']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn']);
            }
        }
    }

    // --- API: TĂNG GIẢM SỐ LƯỢNG MÓN ---
    public function updateQuantity() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $detailId = $_POST['detail_id'];
            $action = $_POST['action']; // 'inc' (tăng) hoặc 'dec' (giảm).
            
            $orderModel = $this->model('OrderModel');
            
            // Gọi hàm cập nhật số lượng trong Model.
            if ($orderModel->updateItemQuantity($detailId, $action)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
    }
}