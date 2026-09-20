 <script src="{{ url('public/admin/dist/assets/static/js/components/dark.js')}}"></script>
<script src="{{ url('public/admin/dist/assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js')}}"></script>
<script src="{{ url('public/admin/dist/assets/compiled/js/app.js')}}"></script>

<!-- Need: Apexcharts -->
<script src="{{ url('public/admin/dist/assets/extensions/apexcharts/apexcharts.min.js')}}"></script>
<script src="{{ url('public/admin/dist/assets/static/js/pages/dashboard.js')}}"></script>

<!-- Datatatble Js -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js" type="text/javascript"></script>


<!-- Toaster Js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


<script src="{{ url('public/admin/dist/assets/extensions/summernote/summernote-lite.min.js')}}"></script>
<script src="{{ url('public/admin/dist/assets/static/js/pages/summernote.js')}}"></script>
<script src="{{ url('public/admin/dist/assets/extensions/choices.js/public/assets/scripts/choices.js')}}"></script>
<script src="{{ url('public/admin/dist/assets/static/js/pages/form-element-select.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    $(document).ready(function () {
        window.initSelect2 = function (context) {
            var $ctx = context ? $(context) : $(document);
            $ctx.find('select:not(.no-select2)').each(function () {
                if ($(this).data('select2')) return;
                var $placeholder = $(this).find('option[value=""]').first();
                var placeholderText = $(this).data('placeholder') || ($placeholder.length ? $placeholder.text() : '');
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: placeholderText,
                    allowClear: placeholderText.length > 0,
                });
            });
        };

        initSelect2();

        $(document).on('shown.bs.modal', '.modal', function () {
            initSelect2(this);
        });
    });
</script>

<script>
    @if(session('message'))
    toastr.info('{{ session('message') }}')
    @endif
    @if(session('warning'))
    toastr.warning('{{ session('warning') }}')
    @endif
    @if(session('success'))
    toastr.success('{{ session('success') }}')
    @endif
    @if(session('error'))
    toastr.error('{{ session('error') }}')
    @endif
</script>

<script>
    (function () {
        const storageKey = 'admin-sidebar-collapsed';
        const desktopBreakpoint = window.matchMedia('(min-width: 1200px)');

        function getApp() {
            return document.getElementById('app');
        }

        function getCollapseButtons() {
            return Array.from(document.querySelectorAll('[data-sidebar-collapse]'));
        }

        function getExpandButtons() {
            return Array.from(document.querySelectorAll('[data-sidebar-expand]'));
        }

        function updateSidebarButtons() {
            const app = getApp();
            const isCollapsed = app?.classList.contains('sidebar-collapsed') ?? false;

            getCollapseButtons().forEach(function (button) {
                button.disabled = isCollapsed;
            });

            getExpandButtons().forEach(function (button) {
                button.setAttribute('aria-hidden', isCollapsed ? 'false' : 'true');
            });
        }

        window.setSidebarCollapsed = function (collapsed, persist = true, source = 'manual') {
            const app = getApp();

            if (!app) {
                return;
            }

            if (!desktopBreakpoint.matches) {
                collapsed = false;
            }

            app.classList.toggle('sidebar-collapsed', Boolean(collapsed));

            if (persist) {
                localStorage.setItem(storageKey, collapsed ? '1' : '0');
            }

            updateSidebarButtons();
            window.dispatchEvent(new CustomEvent('layout:sidebar-toggle', {
                detail: {
                    collapsed: Boolean(collapsed),
                    persist: Boolean(persist),
                    source: source
                }
            }));
        };

        document.addEventListener('DOMContentLoaded', function () {
            const app = getApp();

            if (!app) {
                return;
            }

            if (desktopBreakpoint.matches && localStorage.getItem(storageKey) === '1') {
                window.setSidebarCollapsed(true, false, 'saved-state');
            } else {
                app.classList.remove('sidebar-collapsed');
                updateSidebarButtons();
            }

            getCollapseButtons().forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    window.setSidebarCollapsed(true, true, 'manual');
                });
            });

            getExpandButtons().forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    window.setSidebarCollapsed(false, true, 'manual');
                });
            });

            desktopBreakpoint.addEventListener('change', function (event) {
                if (event.matches) {
                    if (localStorage.getItem(storageKey) === '1') {
                        window.setSidebarCollapsed(true, false, 'breakpoint');
                    } else {
                        app.classList.remove('sidebar-collapsed');
                        updateSidebarButtons();
                    }
                } else {
                    app.classList.remove('sidebar-collapsed');
                    updateSidebarButtons();
                }
            });
        });
    })();
</script>

<script>
    (function () {
        function ensureResponsiveWrapper(table) {
            if (!table || table.closest('.dataTables_wrapper')) {
                return;
            }

            if (table.closest('.table-responsive')) {
                table.closest('.table-responsive').classList.add('modern-table-wrap');
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive modern-table-wrap';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }

        function applyTableClasses(table) {
            if (!table) {
                return;
            }

            table.classList.add('modern-data-table');

            if (!table.classList.contains('table')) {
                table.classList.add('table');
            }
        }

        function syncTableLabels(table) {
            if (!table) {
                return;
            }

            const headCells = Array.from(table.querySelectorAll('thead th')).map(function (th) {
                return th.textContent.replace(/\s+/g, ' ').trim();
            });

            if (!headCells.length) {
                return;
            }

            table.querySelectorAll('tbody tr').forEach(function (row) {
                Array.from(row.children).forEach(function (cell, index) {
                    const label = headCells[index] || '';

                    if (label) {
                        cell.setAttribute('data-label', label);
                    }
                });
            });
        }

        function enhanceTables(scope) {
            const root = scope || document;

            root.querySelectorAll('.page-shell table').forEach(function (table) {
                applyTableClasses(table);
                ensureResponsiveWrapper(table);
                syncTableLabels(table);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            enhanceTables(document);

            if (window.jQuery && $.fn.dataTable) {
                $.extend(true, $.fn.dataTable.defaults, {
                    responsive: true,
                    autoWidth: false
                });

                $(document).on('init.dt draw.dt', function (event) {
                    const table = event.target;

                    if (!(table instanceof HTMLTableElement)) {
                        return;
                    }

                    applyTableClasses(table);
                    syncTableLabels(table);

                    const wrapper = table.closest('.dataTables_wrapper');
                    if (wrapper) {
                        wrapper.classList.add('modern-datatable-shell');

                        const scrollHead = wrapper.querySelector('.dataTables_scrollHead');
                        const scrollBody = wrapper.querySelector('.dataTables_scrollBody');

                        if (scrollHead) {
                            scrollHead.classList.add('modern-table-scroll-head');
                        }

                        if (scrollBody) {
                            scrollBody.classList.add('modern-table-scroll-body');
                        }
                    }
                });
            }
        });
    })();
</script>

<script>
(function () {
    // Global Bootstrap Modal defaults:
    // 1. No backdrop (screen won't dim/freeze)
    // 2. Auto-center every modal dialog
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.Default.backdrop = false;
        }
    });

    document.addEventListener('show.bs.modal', function (e) {
        var dialog = e.target.querySelector('.modal-dialog');
        if (dialog && !dialog.classList.contains('modal-dialog-centered')) {
            dialog.classList.add('modal-dialog-centered');
        }
    });
})();
</script>

@yield('footer.js')
