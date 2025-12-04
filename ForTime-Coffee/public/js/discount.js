/**
 * DISCOUNT MANAGEMENT JAVASCRIPT
 * File: public/js/discount.js
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initDiscountTypeLogic();
    initConditionLogic();
    initDeleteAction(); // [MỚI] Gọi hàm xử lý xóa
    initRestoreAction(); // [MỚI] Gọi hàm
});

// ============================================================
// 1. INITIALIZATION MODULES
// ============================================================

function initSidebar() {
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
}

function initDiscountTypeLogic() {
    const typeSelect = document.querySelector('select[name="type"]');
    const unitSpan = document.getElementById('value-unit');
    if (typeSelect && unitSpan) {
        typeSelect.addEventListener('change', function() {
            unitSpan.innerText = (this.value === 'percentage') ? '%' : 'VNĐ';
        });
    }
}

function initConditionLogic() {
    const radioNone = document.getElementById('cond_none');
    const radioMin = document.getElementById('cond_min');
    const boxMin = document.getElementById('box-min-value');

    if(radioNone && radioMin && boxMin) {
        const toggleBox = () => {
            const input = boxMin.querySelector('input');
            if (radioMin.checked) {
                boxMin.style.display = 'block';
                if(input) input.setAttribute('required', 'required');
            } else {
                boxMin.style.display = 'none';
                if(input) {
                    input.removeAttribute('required');
                    input.value = ''; 
                }
            }
        };
        radioNone.addEventListener('change', toggleBox);
        radioMin.addEventListener('change', toggleBox);
        toggleBox();
    }
}
/**
 * [MỚI] Hàm đổ dữ liệu vào form để Sửa
 */
function editDiscount(data) {
    // 1. Đổ dữ liệu cơ bản
    document.getElementById('discount_id').value = data.discount_id;
    document.getElementById('code').value = data.code;
    document.getElementById('type').value = data.type;
    document.getElementById('value').value = data.value;
    document.getElementById('isActive').checked = (data.is_active == 1);

    // Trigger event change để cập nhật đơn vị tiền tệ (VNĐ/%)
    document.getElementById('type').dispatchEvent(new Event('change'));

    // 2. Xử lý điều kiện (Min order value)
    const minVal = parseFloat(data.min_order_value);
    if (minVal > 0) {
        document.getElementById('cond_min').checked = true;
        document.getElementById('min_order_value').value = minVal;
    } else {
        document.getElementById('cond_none').checked = true;
        document.getElementById('min_order_value').value = '';
    }
    // Trigger event change để hiện/ẩn ô nhập tiền
    document.getElementById('cond_min').dispatchEvent(new Event('change'));

    // 3. Đổi giao diện nút Lưu
    const btnSave = document.getElementById('btnSave');
    btnSave.innerHTML = '<i class="fas fa-sync-alt"></i> Cập nhật';
    btnSave.classList.replace('btn-primary', 'btn-warning');
    btnSave.classList.add('text-white');

    // 4. Đổi Action của form
    const form = document.getElementById('discountForm');
    form.action = `${URLROOT}/discount/edit/${data.discount_id}`;
    
    // Cuộn lên đầu trang để thấy form
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * [MỚI] Hàm Reset Form về trạng thái Thêm mới
 */
function resetDiscountForm() {
    // Reset inputs
    document.getElementById('discountForm').reset();
    document.getElementById('discount_id').value = '';
    
    // Reset giao diện điều kiện
    document.getElementById('cond_none').checked = true;
    document.getElementById('cond_none').dispatchEvent(new Event('change'));
    
    // Reset Action về Add
    document.getElementById('discountForm').action = `${URLROOT}/discount/add`;
    
    // Reset nút Lưu
    const btnSave = document.getElementById('btnSave');
    btnSave.innerHTML = 'Lưu mã giảm giá';
    btnSave.classList.replace('btn-warning', 'btn-primary');
    btnSave.classList.remove('text-white');
}

/**
 * [MỚI] Module: Xử lý nút Xóa mã giảm giá
 */
function initDeleteAction() {
    // Chọn tất cả các nút có class .btn-delete-discount
    const deleteBtns = document.querySelectorAll('.btn-delete-discount');

    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Ngăn chặn chuyển trang ngay lập tức
            
            const deleteUrl = this.getAttribute('href'); // Lấy link xóa

            Swal.fire({
                title: 'XÓA MÃ GIẢM GIÁ?',
                text: "Hành động này sẽ xóa vĩnh viễn mã này khỏi hệ thống!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b', // Màu đỏ
                cancelButtonColor: '#858796',  // Màu xám
                confirmButtonText: 'Xóa vĩnh viễn',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Nếu đồng ý -> Chuyển trang đến link xóa
                    window.location.href = deleteUrl;
                }
            });
        });
    });
}
/**
 * [MỚI] Xử lý nút Khôi phục mã giảm giá
 */
function initRestoreAction() {
    const restoreBtns = document.querySelectorAll('.btn-restore');
    restoreBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');

            Swal.fire({
                title: 'Khôi phục mã giảm giá?',
                text: "Mã này sẽ xuất hiện lại trong danh sách (Trạng thái: Tắt).",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Khôi phục',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
}