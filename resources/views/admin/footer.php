        </div><!-- /.admin-content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    // Default DataTables init
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('.datatable').each(function() {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable({
                        pageLength: 25,
                        responsive: true,
                        language: {
                            search: "Search:",
                            lengthMenu: "Show _MENU_ entries",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "<i class='ri-arrow-right-s-line'></i>",
                                previous: "<i class='ri-arrow-left-s-line'></i>"
                            },
                            emptyTable: "No data available",
                            zeroRecords: "No matching records found"
                        },
                        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip'
                    });
                }
            });
        }
    });

    // Helper: show toast notification
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

    // Helper: confirm action
    function confirmAction(title, text, callback) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4F46E5',
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
</body>
</html>
