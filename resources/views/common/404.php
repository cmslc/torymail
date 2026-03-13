<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Trang không tồn tại</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body { background: #f5f8fa; font-family: 'Segoe UI', sans-serif; }
        .error-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; }
        .error-code { font-size: 120px; font-weight: 700; color: #4F46E5; line-height: 1; }
        .error-icon { font-size: 64px; color: #d1d5db; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="error-page">
        <div>
            <div class="error-icon"><i class="ri-mail-close-line"></i></div>
            <div class="error-code">404</div>
            <h3 class="mt-3 mb-2">Trang không tồn tại</h3>
            <p class="text-muted mb-4">Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.</p>
            <a href="<?= base_url('inbox') ?>" class="btn btn-primary">
                <i class="ri-mail-line"></i> Về Hộp thư
            </a>
        </div>
    </div>
</body>
</html>
