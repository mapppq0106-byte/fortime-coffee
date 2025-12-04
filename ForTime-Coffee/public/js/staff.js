/**
 * STAFF MANAGEMENT JAVASCRIPT
 * -------------------------------------------------------------------------
 * - Chức năng: Xử lý sự kiện cho trang Quản lý Nhân viên (Thêm, Sửa, Xóa mềm, Khôi phục, Chọn từ bảng)
 * - View sử dụng: app/views/admin/users/user_index.php
 * - Controller kết nối: StaffController.php
 * - Model liên quan: UserModel.php
 * -------------------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initFormActions();
    initRestoreAction(); // [MỚI] Kích hoạt chức năng khôi phục
});

// ============================================================
// 1. INITIALIZATION MODULES
// ============================================================

function initSidebar() {
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    if(sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
}

function initFormActions() {
    const btnEdit = document.getElementById('btnEdit');
    const btnDelete = document.getElementById('btnDelete');
    const userIdInput = document.getElementById('user_id');
    const form = document.getElementById('userForm');

    // 1. Xử lý nút SỬA
    if (btnEdit) {
        btnEdit.addEventListener('click', function() {
            const id = userIdInput.value;
            
            // Nếu chưa chọn user
            if(!id) { 
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa chọn nhân viên!',
                    text: 'Vui lòng click vào một dòng trong danh sách bên phải để sửa.',
                    confirmButtonColor: '#f6c23e', // Màu vàng
                    confirmButtonText: 'Đã hiểu'
                });
                return; 
            }
            
            // Nếu đã chọn -> Submit form
            form.action = `${URLROOT}/staff/edit/${id}`;
            form.submit();
        });
    }

    // 2. Xử lý nút XÓA
    if (btnDelete) {
        btnDelete.addEventListener('click', function() {
            const id = userIdInput.value;
            const username = document.getElementById('username').value; // Lấy tên để hiển thị

            // Nếu chưa chọn user
            if(!id) { 
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa chọn nhân viên!',
                    text: 'Vui lòng chọn nhân viên cần xóa!',
                    confirmButtonColor: '#e74a3b', // Màu đỏ
                    confirmButtonText: 'Đã hiểu'
                });
                return; 
            }

            // Hộp thoại xác nhận Xóa (Cập nhật thông báo Xóa Mềm)
            Swal.fire({
                title: 'XÓA TÀI KHOẢN?',
                html: `Bạn có chắc chắn muốn xóa nhân viên <b>${username}</b> không?<br>Tài khoản này sẽ bị vô hiệu hóa và chuyển vào thùng rác.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b', // Màu đỏ
                cancelButtonColor: '#858796',  // Màu xám
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `${URLROOT}/staff/delete/${id}`;
                }
            });
        });
    }
}

// ============================================================
// 2. GLOBAL FUNCTIONS (Gọi từ HTML onclick)
// ============================================================

/**
 * Chọn nhân viên từ bảng để đổ dữ liệu vào form (CHẾ ĐỘ SỬA)
 * @param {HTMLElement} row - Dòng <tr> được click
 * @param {Object} user - Dữ liệu user (JSON)
 */
function selectUser(row, user) {
    // 1. Highlight dòng được chọn
    document.querySelectorAll('.table-row').forEach(r => r.classList.remove('active'));
    row.classList.add('active');

    // 2. Đổ dữ liệu vào form
    document.getElementById('user_id').value = user.user_id;
    
    const usernameInput = document.getElementById('username');
    usernameInput.value = user.username;
    usernameInput.readOnly = true; // Không cho sửa username khi edit
    
    document.getElementById('full_name').value = user.full_name;
    document.getElementById('role_id').value = user.role_id;
    document.getElementById('is_active').value = user.is_active;

    // [MỚI] Ẩn ô mật khẩu khi đang ở chế độ Sửa
    // Admin không được quyền đổi pass của nhân viên tại đây
    const divPass = document.getElementById('div-password');
    if (divPass) {
        divPass.style.display = 'none';
        document.getElementById('password').value = ''; // Xóa giá trị để đảm bảo an toàn
    }
}

/**
 * Làm mới form (CHẾ ĐỘ THÊM MỚI)
 */
function resetForm() {
    // Reset toàn bộ form
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = '';
    
    // Cho phép nhập username mới
    document.getElementById('username').readOnly = false; 
    
    // Xóa highlight trên bảng
    document.querySelectorAll('.table-row').forEach(r => r.classList.remove('active'));
    
    // Mặc định trạng thái là Hoạt động (1)
    document.getElementById('is_active').value = 1;

    // [MỚI] Hiện lại ô mật khẩu khi ở chế độ Thêm mới
    const divPass = document.getElementById('div-password');
    if (divPass) {
        divPass.style.display = 'block';
    }
}

/**
 * [MỚI] Xử lý nút Khôi phục nhân viên
 */
function initRestoreAction() {
    const restoreBtns = document.querySelectorAll('.btn-restore');
    restoreBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Chặn chuyển trang ngay
            e.stopPropagation(); // Chặn click lan ra dòng (tránh mở form sửa)

            const url = this.getAttribute('href'); // Lấy link từ thẻ <a>

            Swal.fire({
                title: 'Khôi phục nhân viên?',
                text: "Tài khoản sẽ được kích hoạt và hoạt động trở lại ngay lập tức.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Khôi phục ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url; // Chuyển hướng để chạy lệnh PHP
                }
            });
        });
    });
}