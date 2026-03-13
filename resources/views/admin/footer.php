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

    <!-- DataTable Init -->
    <script type="text/javascript">
    $(function() {
        if ($.fn.DataTable) {
            var dtConfig = {
                "lengthMenu": [[10, 50, 100, 500, 1000, -1], [10, 50, 100, 500, 1000, "All"]],
                "pageLength": 25,
                "order": [[0, 'desc']]
            };
            if ($('#datatable').length && !$.fn.DataTable.isDataTable('#datatable')) {
                $('#datatable').DataTable(dtConfig);
            }
            if ($('#datatable1').length && !$.fn.DataTable.isDataTable('#datatable1')) {
                $('#datatable1').DataTable(dtConfig);
            }
            if ($('#datatable2').length && !$.fn.DataTable.isDataTable('#datatable2')) {
                $('#datatable2').DataTable(dtConfig);
            }
        }
    });
    </script>

    <!-- Helper JS functions -->
    <script type="text/javascript">
    function showToast(type, message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    function switchLang(lang) {
        $.get('<?= base_url("ajaxs/user/lang.php"); ?>?lang=' + lang, function() {
            location.reload();
        });
    }

    function confirmAction(title, text, callback) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#405189',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Confirm',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                callback();
            }
        });
    }
    </script>

    <!-- Velzon Vendor Scripts -->
    <script src="<?= asset_url('material/assets/libs/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?= asset_url('material/assets/libs/simplebar/simplebar.min.js'); ?>"></script>
    <script src="<?= asset_url('material/assets/libs/node-waves/waves.min.js'); ?>"></script>
    <script src="<?= asset_url('material/assets/libs/feather-icons/feather.min.js'); ?>"></script>
    <script src="<?= asset_url('material/assets/js/plugins.js'); ?>"></script>

    <!-- DataTables & Plugins -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>

    <!-- Page-specific scripts -->
    <?= $body['footer'] ?? ''; ?>

    <!-- Velzon App JS (must be last) -->
    <script src="<?= asset_url('material/assets/js/app.js'); ?>"></script>
</body>
</html>
