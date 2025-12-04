/**
 * SHIFT REPORT JAVASCRIPT
 * File: public/js/shift.js
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initCloseShiftAction(); // [MỚI] Kích hoạt sự kiện chốt ca
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

/**
 * [MỚI] Xử lý nút Chốt ca với SweetAlert2
 */
function initCloseShiftAction() {
    const form = document.getElementById('closeShiftForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Ngăn form gửi đi ngay lập tức

            // Lấy số tiền thực tế để hiển thị trong thông báo (cho chắc chắn)
            const actualCashInput = form.querySelector('input[name="actual_cash"]');
            const actualMoney = actualCashInput ? actualCashInput.value : '0';

            // Kiểm tra nếu chưa nhập tiền
            if (!actualMoney || actualMoney < 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa nhập tiền!',
                    text: 'Vui lòng kiểm đếm và nhập số tiền thực tế trong két.',
                    confirmButtonColor: '#f6c23e'
                });
                return;
            }

            // Hiện hộp thoại xác nhận
            Swal.fire({
                title: 'CHỐT CA & ĐĂNG XUẤT?',
                html: `Bạn xác nhận trong két đang có: <b class="text-success fs-4">${parseInt(actualMoney).toLocaleString('vi-VN')}đ</b><br>Hành động này sẽ kết thúc phiên làm việc.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b', // Màu đỏ
                cancelButtonColor: '#858796',  // Màu xám
                confirmButtonText: 'Đồng ý chốt ca',
                cancelButtonText: 'Kiểm tra lại'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); // Gửi form đi nếu người dùng đồng ý
                }
            });
        });
    }
}

// ============================================================
// 2. HELPER FUNCTIONS
// ============================================================

const formatMoney = (amount) => parseInt(amount || 0).toLocaleString('vi-VN') + 'đ';

// ============================================================
// 3. GLOBAL FUNCTIONS (Gọi từ HTML onclick - Xem chi tiết)
// ============================================================

function viewSessionDetail(element) {
    const start = element.getAttribute('data-start');
    const end = element.getAttribute('data-end');
    const id = element.getAttribute('data-id');
    const note = element.getAttribute('data-note');

    const idEl = document.getElementById('modalSessionId');
    if (idEl) idEl.innerText = id;

    const noteContainer = document.getElementById('modalSessionNote');
    if (noteContainer) {
        if (note && note.trim() !== "") {
            noteContainer.innerHTML = `
                <div class="alert alert-warning small fst-italic mb-3 shadow-sm">
                    <i class="fas fa-comment-alt me-2"></i> <strong>Ghi chú chốt ca:</strong> ${note}
                </div>`;
            noteContainer.style.display = 'block';
        } else {
            noteContainer.innerHTML = '';
            noteContainer.style.display = 'none';
        }
    }

    const tbody = document.getElementById('modalItemsBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Đang tải dữ liệu...</td></tr>';
    }
    
    const modalEl = document.getElementById('sessionDetailModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    const formData = new FormData();
    formData.append('start', start);
    formData.append('end', end);

    fetch(`${URLROOT}/shift/get_session_details`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (!tbody) return;

        if(data.items && data.items.length > 0) {
            const rowsHtml = data.items.map(item => {
                let noteHtml = '';
                if (item.note && item.note.trim() !== '') {
                    noteHtml = `<div class="small text-muted fst-italic mt-1"><i class="fas fa-level-up-alt fa-rotate-90 me-1"></i> ${item.note}</div>`;
                }

                return `
                    <tr>
                        <td class="text-start ps-3 align-middle">
                            <div class="fw-bold text-primary">${item.product_name}</div>
                            ${noteHtml}
                        </td>
                        <td class="text-center fw-bold align-middle">${item.qty}</td>
                        <td class="text-end fw-bold text-danger pe-3 align-middle">${formatMoney(item.subtotal)}</td>
                    </tr>
                `;
            }).join('');
            
            tbody.innerHTML = rowsHtml;
        } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Không có món nào được bán trong ca này.</td></tr>';
        }
    })
    .catch(err => {
        console.error(err);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Lỗi tải dữ liệu.</td></tr>';
        }
    });
}