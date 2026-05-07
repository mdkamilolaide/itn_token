Vue.component('page-body',{
    data:function(){
        return{
            page:'home',   //  page by name home | result | ...
        }
    },
    mounted(){
        /*  Manages events Listening    */

    },
    methods:{

    },
    template:`
    <div>
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title float-left mb-0">DataTable Example</h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Home</a>
                                </li>
                                <li class="breadcrumb-item active">Data Table
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-header-right text-md-right col-md-3 col-12 d-md-block d-none">
                <div class="form-group breadcrumb-right">
                    <div class="dropdown">
                        <button class="btn-icon btn btn-primary btn-round btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i data-feather="grid"></i></button>
                        <div class="dropdown-menu dropdown-menu-right"><a class="dropdown-item" href="app-todo.html"><i class="mr-1" data-feather="check-square"></i><span class="align-middle">Todo</span></a><a class="dropdown-item" href="app-chat.html"><i class="mr-1" data-feather="message-square"></i><span class="align-middle">Chat</span></a><a class="dropdown-item" href="app-email.html"><i class="mr-1" data-feather="mail"></i><span class="align-middle">Email</span></a><a class="dropdown-item" href="app-calendar.html"><i class="mr-1" data-feather="calendar"></i><span class="align-middle">Calendar</span></a></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-body">
            <sample_table/>
        </div>
    </div>
    `
});
Vue.component('sample_table',{
    data:function(){
        return{
            "tableData":[],
            inTable:'',
            selectedData:[],    //  Key list data
            selectedID:[]
        }
    },
    mounted(){
        /*  Manages events Listening    */
        this.loadList();
    },
    methods:{
        loadTableData()
        {
            var self = this;
            var url = common.DataService;
            axios.get(url+"?qid=sam001")
            .then(function (response) {
                self.tableData = response.data.data;
            })
            .catch(function (error) {
                alert.Error("ERROR",error);
            });
        },
        getSelectedData(){
            var indexes =  this.inTable.rows( { selected: true } )[0];              //  Get indexes
            this.selectedData = this.inTable.rows( indexes ).data().toArray();      //  Get Selected
            //  get IDs
            var dd = this.inTable.rows( indexes ).data().toArray();
            var targetid = 0;                       //  ID location
            //  null the list and start over
            this.selectedID=[];
            for(var a = 0;a<dd.length;a++)
            {
                this.selectedID.push(dd[a][targetid]);                              //  Get Selected ID
            }

        },
        getTable(table,url)
        {
            var self = this;
            var ta = table.DataTable({
                "processing": true,
                "serverSide": true,
                "ajax":{
                    url: common.TableService+'?'+url,
                    type: "POST"
                },
                order: [[0, 'asc']],
                displayLength: 10,
                lengthMenu: [7, 10, 25, 50, 75, 100]
            });

            //
            return ta;
        },
        loadList(){
            //  check if fresh or relink
            if($.fn.dataTable.isDataTable('#basicTable'))
            {
              this.refreshList();
            }
            else
            {
              this.freshLoad();
            }
        },
        freshLoad()
        {
            this.inTable = this.getTable($('#basicTable'), 'qid=100');
        },
        refreshList()
        {
            $('#basicTable').DataTable().ajax.reload();
        },
        ReloadList()
        {
            //  Get fresh load of data
            $('#basicTable').DataTable().ajax.url(common.TableService+'?qid=100').load();
        },
    },
    template:`
    
        <div class="row">
            <div class="col-12 p-20">
                <div class="card  invoice-list-wrapper">
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
    `
});
var vm = new Vue({
    el:"#app",
    data:{},
    methods:{

    },
    template:`
        <div>
            <page-body/>
        </div>
    `
});
