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
                                Email Management System
                            </div>
                        </div>
                    </div>
                </div>
            </footer>

        </div><!-- end main-content -->
    </div><!-- end layout-wrapper -->

    <!-- Toast helper -->
    <script>
    function tmToast(icon, title) {
        var Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
        Toast.fire({ icon: icon, title: title });
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
