<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}?>

                </div><!-- end container-fluid -->
            </div><!-- end page-content -->

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <script>document.write(new Date().getFullYear())</script> &copy; Torymail
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                <?= __('footer_system'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>

        </div><!-- end main-content -->
    </div><!-- end layout-wrapper -->

    <!-- Toast container -->
    <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    <!-- Toast helper -->
    <script>
    function tmToast(type, message) {
        var icons = { success: 'ri-check-double-line', error: 'ri-close-circle-line', warning: 'ri-alert-line', info: 'ri-information-line' };
        var colors = { success: 'success', error: 'danger', warning: 'warning', info: 'info' };
        var color = colors[type] || 'primary';
        var icon = icons[type] || 'ri-notification-3-line';
        var id = 'toast-' + Date.now();
        var html = '<div id="' + id + '" class="toast align-items-center text-white bg-' + color + ' border-0" role="alert">' +
            '<div class="d-flex">' +
            '<div class="toast-body"><i class="' + icon + ' me-2"></i>' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div></div>';
        $('#toastContainer').append(html);
        var toast = new bootstrap.Toast(document.getElementById(id), { delay: 3000 });
        toast.show();
        document.getElementById(id).addEventListener('hidden.bs.toast', function() { this.remove(); });
    }

    function switchLang(lang) {
        $.get('<?= base_url("ajaxs/user/lang.php"); ?>?lang=' + lang, function() {
            location.reload();
        });
    }

    function tmConfirm(title, text, callback) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--vz-primary)',
            cancelButtonColor: 'var(--vz-secondary)',
            confirmButtonText: 'Yes, proceed'
        }).then(function(result) {
            if (result.isConfirmed) {
                callback();
            }
        });
    }
    </script>

    <!-- DataTable Init -->
    <script type="text/javascript">
    $(function() {
        if ($.fn.DataTable) {
            var dtConfig = {
                "lengthMenu": [[10, 50, 100, 500, -1], [10, 50, 100, 500, "All"]]
            };
            if ($('#datatable').length) $('#datatable').DataTable(dtConfig);
            if ($('#datatable1').length) $('#datatable1').DataTable(dtConfig);
        }
    });
    </script>

    <!-- Velzon Vendor Scripts -->
    <script src="<?= base_url('public/material/assets/libs/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?= base_url('public/material/assets/libs/simplebar/simplebar.min.js'); ?>"></script>
    <script src="<?= base_url('public/material/assets/libs/node-waves/waves.min.js'); ?>"></script>
    <script src="<?= base_url('public/material/assets/libs/feather-icons/feather.min.js'); ?>"></script>
    <script src="<?= base_url('public/material/assets/js/plugins.js'); ?>"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <!-- Page-specific scripts -->
    <?= $body['footer'] ?? ''; ?>

    <!-- Velzon App JS (must be last) -->
    <script src="<?= base_url('public/material/assets/js/app.js'); ?>"></script>
    <script>
    (function(){
        var el = document.getElementById('scrollbar');
        if (el && typeof SimpleBar !== 'undefined' && !el.SimpleBar) {
            el.classList.add('h-100');
            new SimpleBar(el);
        }
        var resetBtn = document.getElementById('reset-layout');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                sessionStorage.clear();
                window.location.reload();
            });
        }
    })();
    </script>
</body>
</html>
