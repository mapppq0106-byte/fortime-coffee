<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/admin.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/sidebar.css"> 
</head>
<body>

<div class="wrapper">
    <?php require_once APPROOT . '/views/Layouts/sidebar.php'; ?>
    
    <div id="content">
        <nav class="navbar navbar-light bg-white shadow-sm px-3 mb-4 d-md-none">
            <button type="button" id="sidebarCollapse" class="btn btn-primary">
                <i class="fas fa-bars"></i>
            </button>
        </nav>

        <div class="container-fluid p-4">
            <h4 class="fw-bold text-primary mb-4">👤 HỒ SƠ CÁ NHÂN</h4>
            
            <div class="row g-4">
                <div class="col-md-5">
                    
                    <div class="card shadow-sm border-0 text-center p-4 mb-3">
                        <div class="mb-3">
                            <img src="https://ui-avatars.com/api/?name=<?php echo htmlspecialchars($data['user']->full_name); ?>&background=random&size=128" 
                                 class="rounded-circle shadow" width="100" height="100" alt="Avatar">
                        </div>
                        <h5 class="fw-bold text-dark">
                            <?php echo htmlspecialchars($data['user']->full_name); ?>
                        </h5>
                        <p class="badge bg-light text-dark border">
                            <?php echo ($data['user']->role_id == 1) ? 'Quản trị viên' : 'Nhân viên'; ?>
                        </p>
                        <hr>
                        <div class="d-flex justify-content-between text-start px-3">
                            <span class="text-muted"><i class="fas fa-user-tag me-2"></i> Username:</span>
                            <span class="fw-bold text-primary">
                                <?php echo htmlspecialchars($data['user']->username); ?>
                            </span>
                        </div>
                    </div>

                    </div>

                <div class="col-md-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white fw-bold py-3 text-danger">
                            <i class="fas fa-key me-1"></i> Đổi mật khẩu
                        </div>
                        <div class="card-body">
                            <form action="<?php echo URLROOT; ?>/profile/change_password" method="post">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mật khẩu hiện tại</label>
                                    <input type="password" name="old_pass" class="form-control" required placeholder="Nhập mật khẩu cũ...">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mật khẩu mới</label>
                                    <input type="password" name="new_pass" class="form-control" required placeholder="Nhập mật khẩu mới...">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Nhập lại mật khẩu mới</label>
                                    <input type="password" name="confirm_pass" class="form-control" required placeholder="Xác nhận mật khẩu mới...">
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-1"></i> Cập nhật
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo URLROOT; ?>/js/profile.js"></script>

</body>
</html>