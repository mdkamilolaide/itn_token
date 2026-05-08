/**
 * sample/datatable submodule — Vue 3 Composition API in place.
 * Demonstrates wrapping jQuery DataTables (server-side mode) in a Vue 3
 * component; init in onMounted, destroy in onBeforeUnmount.
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('home');
        return { page };
    },
    template: `
    <div>
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title float-left mb-0">DataTable Example</h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Data Table</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-header-right text-md-right col-md-3 col-12 d-md-block d-none">
                <div class="form-group breadcrumb-right">
                    <div class="dropdown">
                        <button class="btn-icon btn btn-primary btn-round btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i data-feather="grid"></i></button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="app-todo.html"><i class="mr-1" data-feather="check-square"></i><span class="align-middle">Todo</span></a>
                            <a class="dropdown-item" href="app-chat.html"><i class="mr-1" data-feather="message-square"></i><span class="align-middle">Chat</span></a>
                            <a class="dropdown-item" href="app-email.html"><i class="mr-1" data-feather="mail"></i><span class="align-middle">Email</span></a>
                            <a class="dropdown-item" href="app-calendar.html"><i class="mr-1" data-feather="calendar"></i><span class="align-middle">Calendar</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-body">
            <sample_table/>
        </div>
    </div>
    `,
};

const SampleTable = {
    setup() {
        const tableData = ref([]);
        // inTable holds the jQuery DataTables instance; not reactive
        // (Vue should not proxy a jQuery wrapper).
        let inTable = null;
        const selectedData = ref([]);
        const selectedID = ref([]);

        function getTable($table, queryString) {
            return $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: common.TableService + '?' + queryString,
                    type: 'POST',
                },
                order: [[0, 'asc']],
                displayLength: 10,
                lengthMenu: [7, 10, 25, 50, 75, 100],
            });
        }

        function freshLoad() {
            inTable = getTable($('#basicTable'), 'qid=100');
        }

        function refreshList() {
            $('#basicTable').DataTable().ajax.reload();
        }

        function loadList() {
            if ($.fn.dataTable.isDataTable('#basicTable')) refreshList();
            else freshLoad();
        }

        function ReloadList() {
            $('#basicTable').DataTable().ajax.url(common.TableService + '?qid=100').load();
        }

        function loadTableData() {
            // Kept for API parity with the v2 component — populates tableData
            // from qid=sam001. Not used by the DataTables flow above.
            axios.get(common.DataService + '?qid=sam001')
                .then(function (response) {
                    tableData.value = (response.data && response.data.data) || [];
                })
                .catch(function (error) {
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function getSelectedData() {
            if (!inTable) return;
            var indexes = inTable.rows({ selected: true })[0];
            selectedData.value = inTable.rows(indexes).data().toArray();
            var dd = inTable.rows(indexes).data().toArray();
            var targetid = 0;
            selectedID.value = [];
            for (var a = 0; a < dd.length; a++) {
                selectedID.value.push(dd[a][targetid]);
            }
        }

        onMounted(function () {
            loadList();
        });

        onBeforeUnmount(function () {
            // Destroy the DataTable on unmount so we don't leak on hot reload
            // or page transitions that re-create the same #basicTable id.
            if (inTable) {
                try { inTable.destroy(true); } catch (e) { /* swallow */ }
                inTable = null;
            }
        });

        return {
            tableData,
            selectedData,
            selectedID,
            loadTableData,
            getSelectedData,
            loadList,
            freshLoad,
            refreshList,
            ReloadList,
        };
    },
    template: `
        <div class="row">
            <div class="col-12 p-20">
                <div class="card invoice-list-wrapper">
                    <div class="card-datatable table-responsive">
                        <table id="basicTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>DP ID</th>
                                    <th>DP</th>
                                    <th>WARD</th>
                                    <th>LGA</th>
                                    <th>STATE</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('sample_table', SampleTable)
    .mount('#app');
