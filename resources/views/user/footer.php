<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
?>

</div><!-- end tm-main -->

<!-- Footer -->
<footer class="tm-footer" style="margin-left:var(--tm-sidebar-width);padding:16px 24px;text-align:center;">
    <span style="font-size:12px;color:#9ca3af;">&copy; <?= date('Y'); ?> Torymail. All rights reserved.</span>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- Common utilities -->
<script>
// Toast helper
function tmToast(icon, title) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
    Toast.fire({ icon: icon, title: title });
}

// Confirm dialog helper
function tmConfirm(title, text, callback) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4F46E5',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, proceed'
    }).then(function(result) {
        if (result.isConfirmed) {
            callback();
        }
    });
}

// Mobile sidebar close on link click
$(document).on('click', '.tm-sidebar-link', function() {
    if (window.innerWidth < 992) {
        document.querySelector('.tm-sidebar').classList.remove('show');
    }
});
</script>

<?= $body['footer'] ?? ''; ?>

</body>
</html>
