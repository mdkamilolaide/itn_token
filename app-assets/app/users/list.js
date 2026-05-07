const EventBus = new Vue();
/*
 *      EVENT HANDLED BY EventBus
 *
 *      - g-event-change-page (Emit multiple - to change page)
 * 		EventBus.$emit('g-event-change-page', 2); 	- Event Fire
 *		EventBus.$on('g-event-change-page', this.gotoPageHandler)  - Event receiver
		gotoPageHandler(data){
            overlay.show();
            this.page = data;
            overlay.hide();
        },						-	Event Handler
 *  
 */

// g-event-goto-page
// g-event-update-user

Vue.component("page-body", {
  data: function () {
    return {
      page: "list", //  page by name home | result | ...
    };
  },
  mounted() {
    /*  Manages events Listening    */
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
  },
  methods: {
    gotoPageHandler(data) {
      this.page = data.page;
    },
  },
  template: `
        <div>

            <div class="content-body">

                <div v-show="page == 'list'">
                    <user_list/>
                </div>

                <div v-show="page == 'detail'">
                    <user_details/>
                </div>

            </div>
        </div>
    `,
});

// User List Page
Vue.component("user_list", {
  data: function () {
    return {
      url: common.BadgeService,
      tableData: [],
      defaultStateId: "",
      roleListData: [],
      userRole: {
        currentUserRole: "",
        currentUserid: "",
      },
      geoData: [],
      permission: getPermission(per, "users"),
      userGroup: [],
      checkToggle: false,
      filterState: false,
      filters: false,
      tableOptions: {
        total: 1, //Total record
        pageLength: 1, //Total
        perPage: 10,
        currentPage: 1,
        orderDir: "asc", // (asc|desc)
        orderField: 0, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100, 150, 200],
        filterParam: {
          user_status: "",
          loginid: "",
          fullname: "",
          user_group: "",
          phoneno: "",
          geo_level: "",
          geo_level_id: "",
          geo_string: "",
          bank_status: "",
          role_id: "",
          role: "",
        },
      },
      geoLevelForm: {
        geoLevel: "",
        geoLevelId: 0,
        currentUserLoginid: "",
        userid: "",
        isBulk: false,
      },
      geoIndicator: {
        state: 50,
        currentLevelId: 0,
        lga: "",
        cluster: "",
        ward: "",
      },
      geoLevelData: [],
      sysDefaultData: [],
      lgaLevelData: [],
      clusterLevelData: [],
      wardLevelData: [],
      dpLevelData: [],
      userPass: {
        pass: "",
        loginid: "",
        name: "",
        isBulk: false,
      },
      workHourExtensionForm: {
        extensionHour: "",
        extensionDate: "",
        authorizationUserId: "",
        affectedUserIds: [],
        isBulk: false,
      },
      isBulkRole: false,
    };
  },
  mounted() {
    /*  Manages events Listening    */

    this.getGeoLocation();
    var select = $(".select2");
    let self = this;
    select.each(function () {
      var $this = $(this);
      $this.wrap('<div class="position-relative"></div>');
      $this
        .select2({
          // the following code is used to disable x-scrollbar when click in select input and
          // take 100% width in responsive also
          dropdownAutoWidth: true,
          width: "100%",
          dropdownParent: $this.parent(),
        })
        .on("change", function () {
          self.setLocation(this.value);
        });
    });
    $(".select2-selection__arrow").html(
      '<i class="feather icon-chevron-down"></i>',
    );

    this.getAllUserGroup();
    this.loadTableData();
    this.getGeoLevel();
    this.getsysDefaultDataSettings();
    this.getRoleList();
    EventBus.$on("g-event-update-user", this.reloadUserListOnUpdate);

    $(".form-password-toggle1 .input-group-text").on("click", function (e) {
      e.preventDefault();
      var $this = $(this),
        inputGroupText = $this.closest(".form-password-toggle1"),
        formPasswordToggleIcon = $this,
        formPasswordToggleInput = inputGroupText.find("input");

      if (formPasswordToggleInput.attr("type") === "text") {
        formPasswordToggleInput.attr("type", "password");
        if (feather) {
          formPasswordToggleIcon
            .find("svg")
            .replaceWith(feather.icons["eye"].toSvg({ class: "font-small-4" }));
        }
      } else if (formPasswordToggleInput.attr("type") === "password") {
        formPasswordToggleInput.attr("type", "text");
        if (feather) {
          formPasswordToggleIcon
            .find("svg")
            .replaceWith(
              feather.icons["eye-off"].toSvg({ class: "font-small-4" }),
            );
        }
      }
    });

    const $form = $(".change-working-hour-form");
    if ($form.length) {
      $(".date").flatpickr({
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d",
        minDate: "today",
      });
      $form.validate({
        rules: {
          extensionHour: { required: true, number: true },
          extensionDate: { required: true },
        },
      });
    }
  },
  methods: {
    autocomplete(inp, arr) {
      var self = this;
      /*the autocomplete function takes two arguments,
            the text field element and an array of possible autocompleted values:*/
      var currentFocus;
      /*execute a function when someone writes in the text field:*/
      inp.addEventListener("input", function (e) {
        var a,
          b,
          i,
          val = this.value;
        /*close any already open lists of autocompleted values*/
        closeAllLists();
        if (!val) {
          return false;
        }
        currentFocus = -1;
        /*create a DIV element that will contain the items (values):*/
        a = document.createElement("DIV");
        a.setAttribute("id", this.id + "autocomplete-list");
        a.setAttribute("class", "autocomplete-items");
        /*append the DIV element as a child of the autocomplete container:*/
        this.parentNode.appendChild(a);
        /*for each item in the array...*/
        for (i = 0; i < arr.length; i++) {
          /*check if the item starts with the same letters as the text field value:*/
          if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
            /*create a DIV element for each matching element:*/
            b = document.createElement("DIV");
            /*make the matching letters bold:*/
            b.innerHTML =
              "<strong>" + arr[i].substr(0, val.length) + "</strong>";
            b.innerHTML += arr[i].substr(val.length);
            /*insert a input field that will hold the current array item's value:*/
            b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
            /*execute a function when someone clicks on the item value (DIV element):*/
            b.addEventListener("click", function (e) {
              /*insert the value for the autocomplete text field:*/
              inp.value = this.getElementsByTagName("input")[0].value;
              /*close the list of autocompleted values,
                            (or any other open lists of autocompleted values:*/
              closeAllLists();
            });
            a.appendChild(b);
          }
        }
      });
      /*execute a function presses a key on the keyboard:*/
      inp.addEventListener("keydown", function (e) {
        var x = document.getElementById(this.id + "autocomplete-list");
        if (x) x = x.getElementsByTagName("div");
        if (e.keyCode == 40) {
          /*If the arrow DOWN key is pressed,
                    increase the currentFocus variable:*/
          currentFocus++;
          /*and and make the current item more visible:*/
          addActive(x);
        } else if (e.keyCode == 38) {
          //up
          /*If the arrow UP key is pressed,
                    decrease the currentFocus variable:*/
          currentFocus--;
          /*and and make the current item more visible:*/
          addActive(x);
        } else if (e.keyCode == 13) {
          /*If the ENTER key is pressed, prevent the form from being submitted,*/
          e.preventDefault();
          if (currentFocus > -1) {
            /*and simulate a click on the "active" item:*/
            if (x) x[currentFocus].click();
          }
        }
      });

      function addActive(x) {
        /*a function to classify an item as "active":*/
        if (!x) return false;
        /*start by removing the "active" class on all items:*/
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = x.length - 1;
        /*add class "autocomplete-active":*/
        x[currentFocus].classList.add("autocomplete-active");
      }

      function removeActive(x) {
        /*a function to remove the "active" class from all autocomplete items:*/
        for (var i = 0; i < x.length; i++) {
          x[i].classList.remove("autocomplete-active");
        }
      }

      function closeAllLists(elmnt) {
        /*close all autocomplete lists in the document,
                except the one passed as an argument:*/
        var x = document.getElementsByClassName("autocomplete-items");
        for (var i = 0; i < x.length; i++) {
          if (elmnt != x[i] && elmnt != inp) {
            x[i].parentNode.removeChild(x[i]);
            self.tableOptions.filterParam.user_group = inp.value;
          }
        }
      }
      /*execute a function when someone clicks in the document:*/
      document.addEventListener("click", function (e) {
        closeAllLists(e.target);
      });
    },
    reloadUserListOnUpdate(data) {
      this.paginationDefault();
      this.loadTableData();
    },
    loadTableData() {
      /*  Manages the loading of table data */
      var self = this;
      var url = common.TableService;
      overlay.show();

      axios
        .get(
          url +
            "?qid=001&draw=" +
            self.tableOptions.currentPage +
            "&order_column=" +
            self.tableOptions.orderField +
            "&length=" +
            self.tableOptions.perPage +
            "&start=" +
            self.tableOptions.limitStart +
            "&order_dir=" +
            self.tableOptions.orderDir +
            "&ac=" +
            self.tableOptions.filterParam.user_status +
            "&lo=" +
            self.tableOptions.filterParam.loginid +
            "&na=" +
            self.tableOptions.filterParam.fullname +
            "&gr=" +
            self.tableOptions.filterParam.user_group +
            "&ph=" +
            self.tableOptions.filterParam.phoneno +
            "&gl=" +
            self.tableOptions.filterParam.geo_level +
            "&gl_id=" +
            self.tableOptions.filterParam.geo_level_id +
            "&bv=" +
            self.tableOptions.filterParam.bank_status +
            "&ri=" +
            self.tableOptions.filterParam.role_id,
        )
        .then(function (response) {
          self.tableData = response.data.data; //All Data
          self.tableOptions.total = response.data.recordsTotal; //Total Records
          if (self.tableOptions.currentPage == 1) {
            self.paginationDefault();
          }
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    selectAll() {
      /*  Manages all check box selection checked */
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          this.tableData[i].pick = true;
        }
      }
    },
    uncheckAll() {
      /*  Manages unchecking of all check box checked */
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          this.tableData[i].pick = false;
        }
      }
    },
    selectToggle() {
      /*  Manages all check box checking and unchecking  */
      if (this.checkToggle == false) {
        this.selectAll();
        this.checkToggle = true;
      } else {
        this.uncheckAll();
        this.checkToggle = false;
      }
    },
    checkedBg(pickOne) {
      /*  Manages the checking of a checkbox */
      return pickOne != "" ? "bg-select" : "";
    },
    toggleFilter() {
      /*  Manages the toggling of a filter box */
      if (this.filterState === false) {
        this.filters = false;
      }
      return (this.filterState = !this.filterState);
    },
    selectedItems() {
      /*  Manages the selections of checkedor selected data object */
      let selectedItems = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedItems.push(this.tableData[i]);
          }
        }
      }
      return selectedItems;
    },
    selectedID() {
      /*  Manages the selections of checkedor selected data object */
      let selectedIds = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedIds.push(this.tableData[i].userid);
          }
        }
      }
      return selectedIds;
    },
    nextPage() {
      this.resetSelected();
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage += 1;
      this.paginationDefault();
      this.loadTableData();
    },
    prevPage() {
      this.resetSelected();
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage -= 1;
      this.paginationDefault();
      this.loadTableData();
    },
    resetSelected() {
      this.uncheckAll();
      this.checkToggle = false;
      this.totalCheckedBox();
    },
    currentPage() {
      this.paginationDefault();
      if (this.tableOptions.currentPage < 1) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else if (this.tableOptions.currentPage > this.tableOptions.pageLength) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else {
        this.loadTableData();
      }
    },
    paginationDefault() {
      //  total page
      this.tableOptions.pageLength = Math.ceil(
        this.tableOptions.total / this.tableOptions.perPage,
      );

      // Page Limit
      this.tableOptions.limitStart = Math.ceil(
        (this.tableOptions.currentPage - 1) * this.tableOptions.perPage,
      );

      //  Next
      if (
        this.tableOptions.currentPage < this.tableOptions.pageLength &&
        this.tableOptions.currentPage != this.tableOptions.pageLength
      ) {
        this.tableOptions.isNext = true;
      } else {
        this.tableOptions.isNext = false;
      }

      // Previous
      if (this.tableOptions.currentPage > 1) {
        this.tableOptions.isPrev = true;
      } else {
        this.tableOptions.isPrev = false;
      }
    },
    changePerPage(val) {
      /*
      let maxPerPage = Math.ceil(this.tableOptions.total / val);
      if (maxPerPage < this.tableOptions.currentPage) {
        this.tableOptions.currentPage = maxPerPage;
      }
      */
      this.resetSelected();
      this.tableOptions.currentPage = 1;
      this.tableOptions.perPage = val;
      this.paginationDefault();
      this.loadTableData();
    },
    sort(col) {
      if (this.tableOptions.orderField === col) {
        this.tableOptions.orderDir =
          this.tableOptions.orderDir === "asc" ? "desc" : "asc";
      } else {
        this.tableOptions.orderField = col;
      }

      this.paginationDefault();
      this.loadTableData();
    },
    applyFilter() {
      // Check if any filter fields are filled
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.user_status != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.loginid != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.fullname != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.user_group != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.phoneno != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.geo_level != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.bank_status != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.role_id != "" ? 1 : 0;

      if (checkFill > 0) {
        this.toggleFilter();
        this.filters = true;
        this.paginationDefault();
        this.loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
        return;
      }
    },
    removeSingleFilter(column_name) {
      // this.tableOptions.filterParam + '.' + column_name == "";
      this.tableOptions.filterParam[column_name] = "";
      if (column_name == "geo_string") {
        this.tableOptions.filterParam.geo_level =
          this.tableOptions.filterParam.geo_level_id =
          this.tableOptions.filterParam.geo_string =
            "";
      }
      if (column_name == "role") {
        this.tableOptions.filterParam.role_id =
          this.tableOptions.filterParam.role = "";
      }
      let g = 0;
      for (let i in this.tableOptions.filterParam) {
        if (this.tableOptions.filterParam[i] != "") {
          g++;
        }
      }
      if (g == 0) {
        this.filters = false;
      }
      this.paginationDefault();
      this.loadTableData();
    },
    clearAllFilter() {
      this.filters = false;
      $(".select2").val("").trigger("change");
      this.tableOptions.filterParam.user_status =
        this.tableOptions.filterParam.loginid =
        this.tableOptions.filterParam.fullname =
        this.tableOptions.filterParam.user_group =
        this.tableOptions.filterParam.phoneno =
        this.tableOptions.filterParam.geo_level =
        this.tableOptions.filterParam.geo_level_id =
        this.tableOptions.filterParam.geo_string =
        this.tableOptions.filterParam.bank_status =
        this.tableOptions.filterParam.role_id =
        this.tableOptions.filterParam.role =
          "";

      this.paginationDefault();
      this.loadTableData();
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    userActivationDeactivation(actionid) {
      var self = this;
      let selectedId = actionid === "all" ? self.selectedID() : [actionid];
      if (selectedId.length < 1) {
        alert.Error("ERROR", "No User selected");
        return;
      }
      var url = common.DataService;
      // overlay.show();
      $.confirm({
        title: "WARNING!",
        content:
          "Are you sure you want to De/Activate Users? <br><br>Make sure you know what you are doing before you De/Activate the user",
        buttons: {
          delete: {
            text: "De/Activate",
            btnClass: "btn btn-danger mr-1",
            action: function () {
              //Attempt Delete
              axios
                .post(url + "?qid=001", JSON.stringify(selectedId))
                .then(function (response) {
                  overlay.hide();
                  if (response.data.result_code == "200") {
                    self.loadTableData();
                    alert.Success(
                      "SUCCESS",
                      response.data.total + " Users Affected",
                    );
                  } else {
                    alert.Error("ERROR", "User De/Activation failed");
                  }
                  overlay.hide();
                })
                .catch(function (error) {
                  overlay.hide();
                  alert.Error("ERROR", error);
                });
            },
          },
          cancel: function () {
            // Do nothing
            overlay.hide();
          },
        },
      });
    },
    goToDetail(userid, user_status) {
      EventBus.$emit("g-event-goto-page", {
        userid: userid,
        page: "detail",
        user_status: user_status,
        role: this.roleListData,
      });
    },
    getRoleList() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=007")
        .then(function (response) {
          self.roleListData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getGeoLocation() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen009")
        .then(function (response) {
          self.geoData = response.data.data; //All Data
          // self.tableOptions.total = response.data.recordsTotal; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    setLocation(select_index) {
      let i = select_index ? select_index : 0;
      this.tableOptions.filterParam.geo_level = this.geoData[i].geo_level;
      this.tableOptions.filterParam.geo_level_id = this.geoData[i].geo_level_id;
      this.tableOptions.filterParam.geo_string = this.geoData[i].geo_string;
    },
    setRole(event) {
      this.tableOptions.filterParam.role =
        event.target.options[event.target.options.selectedIndex].text;
    },
    changeUserGeoLevelModal(
      userid,
      loginid,
      geo_level,
      geolevelid,
      isBulk = false,
    ) {
      if (isBulk && this.selectedID().length < 1) {
        alert.Error("ERROR", "No User selected");
        this.hideGeoModal(); // Already hides the modal if needed
        return;
      }
      this.geoLevelForm.userid = userid;
      this.geoLevelForm.geoLevel = geo_level;
      this.geoLevelForm.currentUserLoginid = loginid;
      this.geoLevelForm.geoLevelId = geolevelid;
      if (isBulk) {
        this.geoLevelForm.geoLevel = "state";
        this.geoLevelForm.geoLevelId = this.sysDefaultData.stateid;
      }
      this.geoLevelForm.isBulk = isBulk;

      $("#geoLevelModal").modal("show");
    },
    updateRole() {
      // $user_id
      // console.log('Hello')
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      // overlay.show();
      if (this.isBulkRole === true) {
        this.submitBulkRole();
        return;
      }
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to change the User Role? </p><br>Make sure you know what you are doing before you confirm the changes.",
        buttons: {
          delete: {
            text: "Change Role",
            btnClass: "btn btn-danger mr-1",
            action: function () {
              //Attempt Delete
              axios
                .post(
                  url +
                    "?qid=008&r=" +
                    self.userRole.currentUserRole +
                    "&u=" +
                    self.userRole.currentUserid,
                )
                .then(function (response) {
                  if (response.data.result_code == "200") {
                    self.loadTableData();
                    overlay.hide();
                    $("#roleForm").modal("hide");
                    alert.Success("SUCCESS", "User Role Updated");
                  } else {
                    overlay.hide();
                    alert.Error("ERROR", "User Role not Updated");
                  }
                })
                .catch(function (error) {
                  overlay.hide();
                  alert.Error("ERROR", error);
                });
            },
          },
          cancel: function () {
            // Do nothing
            overlay.hide();
          },
        },
      });
    },
    refreshData() {
      this.paginationDefault();
      this.getAllUserGroup();
      this.loadTableData();
    },
    downloadBadge(userid) {
      overlay.show();
      window.open(this.url + "?qid=002&e=" + userid, "_parent");
      overlay.hide();
    },
    downloadBadges() {
      // : href = "url+'?qid=003&e='+selectedID()"
      overlay.show();
      // console.log(this.selectedID());

      if (parseInt(this.selectedID().length) > 0) {
        window.open(this.url + "?qid=003&e=" + this.selectedID(), "_parent");
      } else {
        alert.Error("Badge Download Failed", "No user selected");
      }
      overlay.hide();
    },
    hideGeoModal() {
      this.geoLevelForm.currentUserLoginid =
        this.geoLevelForm.geoLevel =
        this.geoLevelForm.geoLevelId =
        this.geoLevelForm.userid =
          "";
      this.geoLevelForm.isBulk = false;

      $("#geoLevelModal").modal("hide");
    },
    showPassResetModal(loginid, name, isBulk = false) {
      if (isBulk && this.selectedID()?.length < 1) {
        alert.Error("ERROR", "No User selected");
        this.hidePassResetModal(); // Already hides the modal if needed
        return;
      }

      this.userPass.loginid = loginid;
      this.userPass.name = name?.trim() || loginid;
      this.userPass.isBulk = isBulk;

      $("#resetPassword").modal("show");
    },
    hidePassResetModal() {
      this.userPass.pass = "";
      this.userPass.loginid = "";
      this.userPass.name = "";
      this.userPass.isBulk = false;
      $("#resetPassword").modal("hide");
    },
    resetPassword() {
      const url = common.DataService;
      const { isBulk, loginid, name, pass } = this.userPass;

      const selected = this.selectedID?.() || [];
      const selectedId = isBulk ? JSON.stringify(selected) : loginid;

      const confirmTitle = isBulk ? `${selected.length} Users` : name;
      const successMessage = isBulk
        ? `${selected.length} Users Password Reset Successfully`
        : `${name} Password Reset Successfully`;
      const qid = isBulk ? "012a" : "012";

      const confirmationMessage = `
        <div>
          Are you sure you want to reset the password for <b>${confirmTitle}</b>?<br>
          Please confirm only if you're certain about this action.
        </div>`;

      $.confirm({
        title: "Password Reset Warning!",
        content: confirmationMessage,
        buttons: {
          delete: {
            text: "Reset Password",
            btnClass: "btn btn-danger btn-sm mr-1",
            action: () => {
              overlay.show();

              axios
                .post(`${url}?qid=${qid}`, {
                  loginid: selectedId,
                  new: pass,
                })
                .then(({ data }) => {
                  overlay.hide();
                  if (data.result_code === 200) {
                    alert.Success("SUCCESS", successMessage);
                    this.hidePassResetModal();
                    this.resetSelected();
                  } else {
                    alert.Error("ERROR", "User Role not Updated");
                  }
                })
                .catch((error) => {
                  overlay.hide();
                  alert.Error("ERROR", error);
                });
            },
          },
          cancel: () => overlay.hide(),
        },
      });
    },
    showWorkExtensionModal(isBulk = false, userId = "") {
      const selectedUsers = this.selectedID?.() || [];

      if (isBulk && selectedUsers.length < 1) {
        alert.Error("ERROR", "No User selected for Working Hour Extension");
        this.hideWorkHourExtensionModal();
        return;
      }

      this.workHourExtensionForm = {
        ...this.workHourExtensionForm,
        affectedUserIds: isBulk ? JSON.stringify(selectedUsers) : userId,
        isBulk,
      };

      $("#workHourModal").modal("show");
    },
    showRoleModal() {
      const selectedUsers = this.selectedID?.() || [];
      this.isBulkRole = true;

      if (selectedUsers.length < 1) {
        alert.Error("ERROR", "No User selected for Role Change");
        // this.hideWorkHourExtensionModal();
        return;
      }
      $("#roleForm").modal("show");
    },
    submitBulkRole() {
      const selectedId = this.selectedID?.() || [];

      let self = this;
      let url = common.DataService;

      const len = selectedId.length;

      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to change the User Role for " +
          len +
          " Users? </p><br>Make sure you sure of your action before you confirm the changes.",
        buttons: {
          delete: {
            text: "Change Role",
            btnClass: "btn btn-danger mr-1",
            action: function () {
              //Attempt Delete
              axios
                .post(
                  url + "?qid=008a&r=" + self.userRole.currentUserRole,
                  JSON.stringify(selectedId),
                )
                .then(function (response) {
                  if (response.data.result_code === 200) {
                    self.isBulkRole = false;
                    self.userRole.currentUserRole =
                      self.userRole.currentUserid = "";

                    self.loadTableData();
                    self.uncheckAll();
                    self.totalCheckedBox();
                    overlay.hide();
                    self.hideRoleModal();
                    alert.Success("SUCCESS", len + " User Role Updated");
                  } else {
                    overlay.hide();
                    alert.Error("ERROR", "User Role not Updated");
                  }
                })
                .catch(function (error) {
                  overlay.hide();
                  alert.Error("ERROR", error);
                });
            },
          },
          cancel: function () {
            // Do nothing
            overlay.hide();
          },
        },
      });
    },
    hideRoleModal() {
      $("#roleForm").modal("hide");
    },
    hideWorkHourExtensionModal() {
      Object.assign(this.workHourExtensionForm, {
        extensionHour: "",
        extensionDate: "",
        isBulk: false,
        affectedUserIds: [],
      });

      $("#extensionDate")
        .flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
          minDate: "today",
        })
        .clear();

      $("#workHourModal").modal("hide");
    },
    onSubmitAddWorkingHour() {
      const { extensionHour, extensionDate, affectedUserIds, isBulk } =
        this.workHourExtensionForm;
      const $form = $(".change-working-hour-form");
      const selectedCount = this.selectedID?.().length || 0;

      if ($form.length && !$form.valid()) {
        alert.Error(
          "Required Fields",
          "All Fields with an asterisk (*) are required",
        );
        return;
      }

      if (selectedCount > 200) {
        alert.Error(
          "Error: Too Many Users",
          "You can't select more than 200 users.",
        );
        return;
      }

      const titleLabel = isBulk ? `${selectedCount} Users` : `1 User`;
      const successMessage = `${titleLabel} Work Hour Extended Successfully`;
      const confirmationMessage = `
        <div>
          Are you sure you want to add work hour for <b>${titleLabel}</b>?<br>
          Please confirm only if you're certain about this action.
        </div>
      `;

      $.confirm({
        title: "WARNING!",
        content: confirmationMessage,
        buttons: {
          confirm: {
            text: "Extend Work Hour",
            btnClass: "btn btn-danger mr-1",
            action: () => {
              const qid = "015";
              const url = `${common.DataService}?qid=${qid}`;
              const currentUserId = document.getElementById("v_g_id").value;

              axios
                .post(url, {
                  authorizationUserId: currentUserId,
                  bulkUserIds: affectedUserIds,
                  extensionHour: extensionHour,
                  extensionDate: extensionDate,
                })
                .then(({ data }) => {
                  overlay.hide();

                  if (data.result_code === 200) {
                    this.hideWorkHourExtensionModal();
                    this.resetSelected();
                    alert.Success("SUCCESS", successMessage);
                  } else {
                    alert.Error(
                      "ERROR",
                      "Unable to update the geo level. Please try again later.",
                    );
                  }
                })
                .catch((error) => {
                  overlay.hide();
                  alert.Error(
                    "ERROR",
                    error?.message || "Unexpected error occurred.",
                  );
                });
            },
          },
          cancel: () => overlay.hide(),
        },
      });
    },
    getsysDefaultDataSettings() {
      /*  Manages the loading of System default settings */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen007")
        .then(function (response) {
          if (response.data.data.length > 0) {
            self.sysDefaultData = response.data.data[0]; //All Data
            self.getLgasLevel(response.data.data[0].stateid);
            //  Set preventDefault();
            self.geoLevelForm.geoLevel = "state";
            self.geoLevelForm.geoLevelId = response.data.data[0].stateid;
            self.defaultStateId = response.data.data[0].stateid;
          }

          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getGeoLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen001")
        .then(function (response) {
          self.geoLevelData = response.data.data; //All Data
          // self.tableOptions.total = response.data.recordsTotal; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getLgasLevel(stateid) {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=gen003", JSON.stringify(stateid))
        .then(function (response) {
          self.lgaLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getClusterLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen004&e=" + self.geoIndicator.cluster)
        .then(function (response) {
          self.clusterLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getWardLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen005&e=" + self.geoIndicator.lga)
        .then(function (response) {
          self.wardLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getDpLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen006&wardid=" + self.geoIndicator.ward)
        .then(function (response) {
          self.dpLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    changeGeoLevel() {
      // if (this.geoLevelForm.geoLevel == "country" || this.geoLevelForm.geoLevel == "dp") {
      if (this.geoLevelForm.geoLevel == "country") {
        alert.Error(
          "ERROR",
          "Invalid Geo-Level selected, please select a valid Geo-Level",
        );
      } else if (this.geoLevelForm.geoLevel == "state") {
        //  State here
        this.geoLevelForm.geoLevelId = this.defaultStateId;
      } else {
        this.geoLevelForm.geoLevelId = "";
        this.geoIndicator.lga =
          this.geoIndicator.ward =
          this.geoIndicator.cluster =
            "";
      }
    },
    changeUserRoleModal(userid, roleid) {
      this.userRole.currentUserRole = roleid;
      this.userRole.currentUserid = userid;
    },
    onSubmitUpdateGeoLevel() {
      const { userid, geoLevel, geoLevelId, isBulk, currentUserLoginid } =
        this.geoLevelForm;

      const selectedUsers = this.selectedID?.() || [];
      const userIdentifier = isBulk ? JSON.stringify(selectedUsers) : userid;

      const titleLabel = isBulk
        ? `${selectedUsers.length} Users`
        : currentUserLoginid;
      const successMessage = `${titleLabel} Geo Level Successfully Changed`;
      const qid = isBulk ? "009a" : "009";
      const url = `${common.DataService}?qid=${qid}`;

      const confirmationMessage = `
              <div>
                Are you sure you want to change the User Geo Level for <b>${titleLabel}</b>?<br>
                Please confirm only if you're certain about this action.
              </div>`;

      $.confirm({
        title: "WARNING!",
        content: confirmationMessage,
        buttons: {
          confirm: {
            text: "Update Geo Level",
            btnClass: "btn btn-danger mr-1",
            action: () => {
              axios
                .post(url, {
                  u: userIdentifier,
                  l: geoLevel,
                  id: geoLevelId,
                })
                .then(({ data }) => {
                  overlay.hide();
                  if (data.result_code === 200) {
                    this.loadTableData();
                    this.hideGeoModal?.();
                    this.resetSelected();
                    alert.Success("SUCCESS", successMessage);
                  } else {
                    alert.Error(
                      "ERROR",
                      "Unable to update the geo level. Please try again later.",
                    );
                  }
                })
                .catch((error) => {
                  overlay.hide();
                  alert.Error(
                    "ERROR",
                    error?.message || "Unexpected error occurred.",
                  );
                });
            },
          },
          cancel: () => overlay.hide(),
        },
      });
    },
    getAllUserGroup() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=026")
        .then(function (response) {
          let group = [];
          for (let i = 0; i < response.data.data.length; i++) {
            group.push(response.data.data[i]["user_group"]); //All Data
          }

          self.userGroup = group;
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    loadAuto() {
      // this.autocomplete(document.getElementById("group-name"), this.userGroup)
      this.autocomplete(document.getElementById("user_group"), this.userGroup);
    },
    verifyAccount(userid, index, first_name, middle_name, last_name) {
      var self = this;
      var url = common.DataService;
      overlay.show();
      let f_name = first_name == null ? "" : first_name;
      let m_name = middle_name == null ? "" : middle_name;
      let l_name = last_name == null ? "" : last_name;

      axios
        .post(url + "?qid=013&userid=" + userid)
        .then(function (response) {
          // console.log(response.data);
          if (response.data.result_code == "200") {
            if (response.data.data.result == "success") {
              index.is_verified = 1;
              index.verification_status = "success";
              overlay.hide();
              alert.Success(
                "Account Verified",
                f_name + " " + l_name + " Bank Account Details Verified",
              );
            } else if (response.data.data.result == "warning") {
              index.is_verified = 1;
              index.verification_status = "warning";
              overlay.hide();
              alert.Warning(
                "Invalid Verified Account Name",
                "Bank Name is different from the supplied Name ",
              );
            } else {
              index.is_verified = 1;
              index.verification_status = "failed";
              overlay.hide();
              alert.Error("Verification Failed", "Invalid Account Details");
            }
          } else {
            index.is_verified = 1;
            index.verification_status = "failed";
            overlay.hide();
            alert.Error("ERROR", "Invalid Account Details");
          }
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    accountVerificationStatus(status) {
      if (status == "success") {
        return ["txt-success", "bg-status-success icon-check-circle"];
      } else if (status == "failed") {
        return ["txt-failed", "bg-status-failed icon-x-circle"];
      } else if (status == "warning") {
        return ["txt-warning", "bg-status-warning icon-circle"];
      } else {
        return ["", "icon-circle"];
      }
    },
    totalCheckedBox() {
      let total = this.selectedID().length;
      if (this.selectedID().length > 0) {
        document.getElementById("total-selected").innerHTML =
          `<span id="selected-counter" class="badge badge-primary btn-icon"><span class="badge badge-success">${total}</span> Selected</span>`;
      } else {
        document.getElementById("total-selected").replaceChildren();
      }
    },
    async exportUserData() {
      var self = this;
      var common_url =
        "&draw=" +
        self.tableOptions.currentPage +
        "&order_column=" +
        self.tableOptions.orderField +
        "&length=" +
        self.tableOptions.perPage +
        "&start=" +
        self.tableOptions.limitStart +
        "&order_dir=" +
        self.tableOptions.orderDir +
        "&ac=" +
        self.tableOptions.filterParam.user_status +
        "&lo=" +
        self.tableOptions.filterParam.loginid +
        "&na=" +
        self.tableOptions.filterParam.fullname +
        "&gr=" +
        self.tableOptions.filterParam.user_group +
        "&ph=" +
        self.tableOptions.filterParam.phoneno +
        "&gl=" +
        self.tableOptions.filterParam.geo_level +
        "&gl_id=" +
        self.tableOptions.filterParam.geo_level_id +
        "&bv=" +
        self.tableOptions.filterParam.bank_status +
        "&ri=" +
        self.tableOptions.filterParam.role_id;

      var veriUrl = "qid=014" + common_url;
      var dlString = "qid=001" + common_url;

      var filename =
        (self.tableOptions.filterParam.geo_string
          ? self.tableOptions.filterParam.geo_string
          : "Recent ") +
        " " +
        (self.tableOptions.filterParam.loginid
          ? self.tableOptions.filterParam.loginid
          : "Recent ") +
        " User List";
      overlay.show();

      //  count export data
      let count = new Promise((resolve, reject) => {
        $.ajax({
          url: common.DataService,
          type: "POST",
          data: veriUrl,
          dataType: "json",
          success: function (data) {
            resolve(data.total);
          },
        });
      });
      let result = await count; //  wait till the promise resolves (*)
      var downloadMax = common.ExportDownloadLimit;

      if (parseInt(result) > downloadMax) {
        //  stop download
        alert.Error(
          "Download Error",
          "Unable to download data because it has exceeded download limit, download limit is " +
            downloadMax,
        );
      } else if (parseInt(result) == 0) {
        alert.Error("Download Error", "No data found");
      } else {
        alert.Info("DOWNLOADING...", "Downloading " + result + " record(s)");
        //  Else continue download data
        var options = {
          fileName: filename,
        };

        let dl = new Promise((resolve, reject) => {
          $.ajax({
            url: common.ExportService,
            type: "POST",
            data: dlString,
            success: function (data) {
              resolve(data);
            },
          });
        });

        let outcome = await dl; //  Wait till downloaded
        var exportData = JSON.parse(outcome);
        Jhxlsx.export(exportData, options);
      }
      this.resetSelected();

      overlay.hide();
    },
    checkIfAndReturnEmpty(data) {
      if (data === null || data === "") {
        return "";
      } else {
        return data;
      }
    },
    numbersOnlyWithoutDot(evt) {
      evt = evt ? evt : window.event;
      var charCode = evt.which ? evt.which : evt.keyCode;
      if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        evt.preventDefault();
      } else {
        return true;
      }
    },
  },
  computed: {},
  template: `

        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item active">Users List </li>
                    </ol>
                    <span id="total-selected"></span>
                </div>
                
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>       
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
                    
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" @click="userActivationDeactivation('all')">De/Activate User</a>
                        <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" @click="exportUserData()">Export Users</a>
                        <a v-if="permission.permission_value >=2" class="dropdown-item"  href="javascript:void(0)" @click="showPassResetModal('', '', true)">Reset Password</a>
                        <a v-if="permission.permission_value >=2" class="dropdown-item"  href="javascript:void(0)" @click="showRoleModal()">Change Role</a>
                        <a v-if="permission.permission_value >=2" class="dropdown-item"  href="javascript:void(0)" @click="showWorkExtensionModal(true, '')">Work Hour Extension</a>
                        <a v-if="permission.permission_value >=2" class="dropdown-item"  href="javascript:void(0)" @click="changeUserGeoLevelModal('', '', '', '', true)">Change Geo Level</a>
                        
                        <a class="dropdown-item" href="javascript:void(0)" @click="downloadBadges()">Download Badge</a>
                        
                    </div>
                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0 && i != 'geo_level' && i != 'geo_level_id'&& i != 'role_id'">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card  custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Active Status</label>
                                        <select name="active" v-model="tableOptions.filterParam.user_status" class="form-control active">
                                            <option value="">All Users</option>
                                            <option value="active">Active Users</option>
                                            <option value="inactive">Inactive Users</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginid" class="form-control login-id" id="login-id" placeholder="Login ID" name="loginid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Fullname</label>
                                        <input type="text" id="fullname" v-model="tableOptions.filterParam.fullname" class="form-control fullname" placeholder="Fullname" name="fullname" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group autocomplete">
                                        <label>User Group</label>
                                        <input autocomplete="off" type="text" @focus="loadAuto()" id="user_group" v-model="tableOptions.filterParam.user_group" class="form-control user_group" placeholder="User Group" name="user_group" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" id="phoneno" v-model="tableOptions.filterParam.phoneno" class="form-control phoneno" placeholder="Phone Number" name="phoneno" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Bank Verification Status</label>
                                        <select name="bank_status" v-model="tableOptions.filterParam.bank_status" class="form-control active">
                                            <option value="">All</option>
                                            <option value="success">Successfull Verification</option>
                                            <option value="none">Pending Verification</option>
                                            <option value="failed">Failed Verification</option>
                                            <option value="warning">Invalid Account Name</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="role" v-model="tableOptions.filterParam.role_id"  @change="setRole($event)" class="form-control role">
                                            <option value="" selected="selected">All</option>
                                            <option v-for="r in roleListData" :value="r.roleid" >{{r.role}}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-4 col-md-3 col-lg-3">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary"  @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                                
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px">

                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :checked="checkToggle" @change="selectToggle(), totalCheckedBox()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(0)" class="pl-0">
                                        Login ID
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)" class="pl-1">
                                        Fullname
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)" class="pl-1">
                                        Role
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(16)" class="pl-1">
                                        Geo String
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 16 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 16 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(12)" class="pl-1">
                                        Status
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 12 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 12 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th class="pl-1 pr-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in tableData" :class="checkedBg(g.pick)">
                                    <td>
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.loginid" v-model="g.pick" @change="totalCheckedBox()" />
                                            <label class="custom-control-label" :for="g.loginid"></label>
                                        </div>
                                    </td>
                                    <td class="pl-0" :class="g.is_verified ==1? accountVerificationStatus(g.verification_status)[0]:''" @dblclick="verifyAccount(g.userid, g, g.first, g.middle, g.last)">
                                      <i class="verified feather" :class="g.is_verified ==1? accountVerificationStatus(g.verification_status)[1]:'icon-circle'" data-toggle="tooltip" data-placement="top" title="Double Click on this Icon to Verify Bank Details"></i>
                                      {{g.loginid}}
                                    </td>
                                    <td class="pl-1">{{g.first}} {{g.middle}} {{g.last}}</td>
                                    <td class="pl-1">                                  
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder text-primary"  v-html="g.role? g.role: 'Role Not Assigned'"></span>
                                                </span>
                                                <small class="emp_post text-muted" v-html="g.user_group? g.user_group: ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-primary" v-html="g.geo_level? g.geo_level.toUpperCase(): 'Geo Not Assigned'"></small>
                                                <small class="emp_post text-muted" v-html="g.geo_string? capitalize(g.geo_string): ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1"><span class="badge rounded-pill font-small-1" :class="g.active==1? 'bg-success' : 'bg-danger'">{{g.active==1? 'Active' : 'Inactive'}}</span>  </td>
                                    <td class="pl-1 pr-1">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <i class="feather icon-more-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToDetail(g.userid, g.active)">
                                                    <i class="feather icon-eye mr-50"></i>
                                                    <span>Details</span>
                                                </a>
                                                <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#geoLevelModal" data-backdrop="static" data-keyboard="false" @click="changeUserGeoLevelModal(g.userid, g.loginid, g.geo_level, g.geo_level_id)">
                                                    <i class="feather icon-user mr-50"></i>
                                                    <span>Change Geo Level</span>
                                                </a>
                                                <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#roleForm" @click="changeUserRoleModal(g.userid, g.roleid, '')">
                                                    <i class="feather icon-user mr-50"></i>
                                                    <span>Change User Role</span>
                                                </a>
                                                <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#resetPassword" @click="showPassResetModal(g.loginid, checkIfAndReturnEmpty(g.first) +' '+ checkIfAndReturnEmpty(g.middle) +' '+ checkIfAndReturnEmpty(g.last))">
                                                    <i class="feather icon-user mr-50"></i>
                                                    <span>Reset User Password</span>
                                                </a>
                                                <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" @click="userActivationDeactivation(g.userid)">
                                                    <i class="feather icon-user-check mr-50"></i>
                                                    <span>De/Activate</span>
                                                </a>
                                                <a class="dropdown-item" href="javascript:void(0);" @click="verifyAccount(g.userid, g, g.first, g.middle, g.last)">
                                                    <i class="feather icon-alert-triangle mr-50"></i>
                                                    <span>Verify Bank Details</span>
                                                </a>
                                                <a class="dropdown-item" href="javascript:void(0);" @click="downloadBadge(g.userid)">
                                                    <i class="feather icon-download mr-50"></i>
                                                    <span>Download Badge</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No User Added</small></td></tr>
                            </tbody>
                        </table>

                    </div>

                    <div class="card-footer">
                        <div class="content-fluid">
                            <div class="row">
                                <div class="col-12 col-xl-4 col-md-4 col-sm-5">
                                    <div class="dropdown sort-dropdown mb-1 mb-sm-0">
                                        <button class="btn filter-btn btn-primary dropdown-toggle border text-dark" type="button" id="tablePaginationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{tableOptions.limitStart+1}} - {{tableOptions.limitStart+tableData.length}} of {{tableOptions.total}} 
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePaginationDropdown">
                                            <a @click="changePerPage(g)" v-for="g in tableOptions.aLength" class="dropdown-item" href="javascript:void(0);">{{g}}</a>
                                        </div>
                                    </div>                            
                                </div>

                                <div class="col-12 col-xl-8 col-md-8 col-sm-7 text-right text-pag">
                                    
                                    <div class="btn-group">
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="tableOptions.isPrev? false: true">
                                            <i data-feather='chevron-left'></i> Prev
                                        </button>
                                        
                                        <input @keyup.13="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary">  of {{this.tableOptions.pageLength}} </small>
                                        </button>
                                        
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round"  :disabled="tableOptions.isNext? false: true">
                                            Next <i data-feather='chevron-right'></i>
                                        </button>
                                        
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mb-50"></div>
                </div>
            </div>

            <!-- Change User Role Modal: Start -->
            <div class="modal fade text-left" id="roleForm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="myModalLabel33">Change User Role</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" @submit.stop.prevent="isBulkRole ? submitBulkRole() : updateRole()">
                            <div class="modal-body">
                                <label>Role:</label>
                                <div class="form-group">
                                    <select name="role" v-model="userRole.currentUserRole" class="form-control role">
                                        <option value="">Choose Role</option>
                                        <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid" :selected="r.roleid == userRole.currentUserRole">{{r.role}}</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary mt-2 me-1 waves-effect waves-float waves-light">Change Role</button>
                                    <button type="reset" class="btn btn-outline-secondary mt-2 waves-effect" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">Discard</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Change User Role Modal: End -->


            <!-- Reset User Password Modal: Start -->
            <div class="modal fade text-left" id="resetPassword" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="resetPassword" data-keyboard="false" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title text-primary" id="myModalLabel34">Reset {{this.userPass.isBulk? this.selectedID().length +' Users':' User'}}  Password</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hidePassResetModal()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="validate-form" method="POST" @submit.stop.prevent="resetPassword()">
                            <div class="modal-body">
                                <div class="form-group">
                                    <div class="d-flex justify-content-between">
                                        <label for="login-password">New Password</label>
                                    </div>
                                    <div class="input-group input-group-merge form-password-toggle1">
                                        <input type="password" required class="form-control new_password" v-model="userPass.pass"  name="new_password" tabindex="2" placeholder="********" aria-describedby="login-password" />
                                        <div class="input-group-append">
                                            <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary mt-2 me-1 waves-effect waves-float waves-light">Reset Password</button>
                                    <button type="reset" class="btn btn-outline-secondary mt-2 waves-effect" data-bs-dismiss="modal" @click="hidePassResetModal()" data-dismiss="modal" aria-label="Close">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Reset User Password Modal: End -->



            <!-- Change Geo Level Modal: Starts -->
            <div class="modal modal-slide-in new-user-modal fade" id="geoLevelModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="change-geo-level modal-content pt-0" @submit.stop.prevent="onSubmitUpdateGeoLevel()">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideGeoModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel">Change <span class="text-primary">{{geoLevelForm.currentUserLoginid}}</span> Geo Level</h5>
                        </div>
                        <div class="modal-body flex-grow-1">

                            <div class="form-group">
                                <label class="form-label" for="user-role">Geo Level</label>
                                <select id="user-role" @change="changeGeoLevel()" class="form-control" v-model="geoLevelForm.geoLevel">
                                    <option v-for="geo in geoLevelData" :value="geo.geo_level">{{(geo.geo_level)}}</option>
                                </select>
                            </div>

                            <div class="form-group" v-if="geoLevelForm.geoLevel == 'state'">
                                <label class="form-label" for="user-role">State</label>
                                <select id="user-role" placeholder="Select Geo Level" class="form-control" v-model="geoLevelForm.geoLevelId">
                                    <option  :value="sysDefaultData.stateid">{{sysDefaultData.state}}</option>
                                </select>
                            </div>

                            <div class="form-group" v-if="geoLevelForm.geoLevel == 'lga'">
                                <label class="form-label" for="user-role">LGA List</label>
                                <select id="user-role" class="form-control" v-model="geoLevelForm.geoLevelId">
                                    <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                </select>
                            </div>

                            <div v-if="geoLevelForm.geoLevel == 'cluster'">
                                <div class="form-group">
                                    <label class="form-label" for="user-role">LGA List</label>
                                    <select id="user-role" class="form-control" @change="getClusterLevel()" v-model="geoIndicator.cluster">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Cluster</label>
                                    <select class="form-control"  v-model="geoLevelForm.geoLevelId">
                                        <option v-for="g in clusterLevelData" :value="g.clusterid">{{g.cluster}}</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="geoLevelForm.geoLevel == 'ward'">
                                <div class="form-group">
                                    <label class="form-label" for="user-role">LGA List</label>
                                    <select id="user-role" class="form-control" @change="getWardLevel()"  v-model="geoIndicator.lga">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Ward</label>
                                    <select class="form-control" v-model="geoLevelForm.geoLevelId">
                                        <option v-for="g in wardLevelData" :value="g.wardid">{{g.ward}}</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="geoLevelForm.geoLevel == 'dp'">
                                <div class="form-group">
                                    <label class="form-label" for="user-role">LGA List</label>
                                    <select id="user-role" class="form-control" @change="getWardLevel()"  v-model="geoIndicator.lga">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Ward</label>
                                    <select class="form-control" @change="getDpLevel()" v-model="geoIndicator.ward">
                                        <option v-for="g in wardLevelData" :value="g.wardid">{{g.ward}}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="user-role">DP List</label>
                                    <select class="form-control" v-model="geoLevelForm.geoLevelId">
                                        <option v-for="g in dpLevelData" :value="g.dpid">{{g.dp}}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">Update Geo Level</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideGeoModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
          <!-- Change Geo Level Modal: Ends -->

          <!-- Working Hour Extension Modal: Starts -->
            <div class="modal modal-slide-in new-user-modal fade" id="workHourModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="change-working-hour-form modal-content pt-0" @submit.stop.prevent="onSubmitAddWorkingHour()">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideWorkHourExtensionModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title text-primary" id="workHourModalLabel">Extend <span class="badge badge-light-success"> {{selectedID()?.length}} Users</span> Work Hour</h5>
                        </div>
                        <div class="modal-body  flex-grow-1">

                            <div class="form-group">
                                <label class="form-label" for="extensionHour">Extension Hour</label>
                                <input type="number" class="form-control extensionHour" name="extensionHour" id="extensionHour" @keypress="numbersOnlyWithoutDot" Placeholder="Extension Hour" v-model="workHourExtensionForm.extensionHour" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="extensionDate">Date of Extension</label>
                                <input type="date" placeholder="Date of Extension" class="form-control extensionDate date" name="extensionDate" id="extensionDate" v-model="workHourExtensionForm.extensionDate" />
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">Add Extension</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideWorkHourExtensionModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to add new user Ends-->

        </div>
    `,
});

// User Details Page
Vue.component("user_details", {
  data: function () {
    return {
      userid: "",
      userDetails: true,
      user_status: "",
      bankListData: [],
      roleListData: [],
      permission: getPermission(per, "users"),
      userData: {
        baseData: [],
        financeData: [],
        identityData: [],
        roleData: [],
      },
    };
  },
  mounted() {
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
    this.getBankLists();
  },
  methods: {
    gotoPageHandler(data) {
      this.userDetails = true;
      this.userid = data.userid;
      this.user_status = data.user_status;
      this.roleListData = data.role;
      this.getUserDetails();
    },
    goToList() {
      EventBus.$emit("g-event-goto-page", {
        page: "list",
        userid: this.userid,
      });
    },
    discardUpdate() {
      var self = this;
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to discard the changes? </p><br>Discarding the changes means you will loss all changes made",
        buttons: {
          delete: {
            text: "Discard Changes",
            btnClass: "btn btn-warning mr-1",
            action: function () {
              //Attempt Delete
              self.getUserDetails();
              self.userDetails = true;
              overlay.hide();
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: function () {
              // Do nothing
              overlay.hide();
            },
          },
        },
      });
    },
    getUserDetails() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=005&e=" + self.userid)
        .then(function (response) {
          self.userData.baseData = response.data.base[0]; //All Data
          self.userData.financeData = response.data.finance[0]; //Total Records
          self.userData.identityData = response.data.identity[0]; //Total Records
          self.userData.roleData = response.data.role[0]; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getBankLists() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen008")
        .then(function (response) {
          self.bankListData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    updateUserProfile() {
      var updateFormData = {
        userid: this.userid,
        roleid: this.userData.baseData.roleid,
        first: this.userData.identityData.first,
        middle: this.userData.identityData.middle,
        last: this.userData.identityData.last,
        gender: this.userData.identityData.gender,
        email: this.userData.identityData.email,
        phone: this.userData.identityData.phone,
        bank_name: this.userData.financeData.bank_name,
        account_name: this.userData.financeData.account_name,
        account_no: this.userData.financeData.account_no,
        bank_code:
          this.userData.financeData.bank_code != ""
            ? this.userData.financeData.bank_code
            : "",
        bio_feature: "",
      };
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      // overlay.show();
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to Update the User? </p><br>Updating the User profile means you are changing the user permissions and details",
        buttons: {
          delete: {
            text: "Update Details",
            btnClass: "btn btn-warning mr-1",
            action: function () {
              //Attempt Delete
              axios
                .post(url + "?qid=006", JSON.stringify(updateFormData))
                .then(function (response) {
                  if (response.data.result_code == "200") {
                    overlay.hide();
                    EventBus.$emit("g-event-update-user", {});
                    self.userDetails = true;
                    alert.Success(
                      "SUCCESS",
                      response.data.total + " User Updated",
                    );
                  } else {
                    overlay.hide();
                    alert.Error("ERROR", "User Details Update failed");
                  }
                })
                .catch(function (error) {
                  overlay.hide();
                  alert.Error("ERROR", error);
                });
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: function () {
              // Do nothing
              overlay.hide();
            },
          },
        },
      });
    },
    getRoleList() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=007")
        .then(function (response) {
          self.roleListData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
      }
    },
    userActivationDeactivation(actionid) {
      var self = this;
      let selectedId = [actionid];

      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=001", JSON.stringify(selectedId))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code == "200") {
            EventBus.$emit("g-event-update-user", {});
            self.user_status == "1"
              ? (self.user_status = 0)
              : (self.user_status = 1);
            alert.Success("SUCCESS", "User De/Activation Successful");
          } else {
            alert.Error("ERROR", "User De/Activation failed");
          }
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    changeRole(event) {
      this.userData.baseData.role =
        event.target.options[event.target.options.selectedIndex].text;
    },
    changeBank(event) {
      this.userData.financeData.bank_name =
        event.target.options[event.target.options.selectedIndex].text;
    },
    downloadBadge(userid) {
      overlay.show();
      window.open(common.BadgeService + "?qid=002&e=" + userid, "_parent");
      overlay.hide();
    },
    numbersOnlyWithoutDot(evt) {
      evt = evt ? evt : window.event;
      var charCode = evt.which ? evt.which : evt.keyCode;
      if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        evt.preventDefault();
      } else {
        return true;
      }
    },
  },
  template: `
        <div class="row">
            <div class="col-md-12 col-sm-12 col-12 mb-1">
                <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToList()">Users List</a></li>
                        <li v-if="userDetails" class="breadcrumb-item active">User Details</li>
                        <li v-else class="breadcrumb-item active">User Update</li>
                    </ol>
                </div>
            </div>

            <!-- User Sidebar -->
            <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0 sidebar-sticky">
                <!-- User Card -->
                <div class="card">
                    <div class="card-body">
                        <div class="user-avatar-section">
                            <div class="d-flex align-items-center flex-column">
                                <img class="img-fluid rounded mt-3 mb-2" src="../app-assets/images/avatar.png" height="110" width="110" alt="User avatar">
                                <div class="user-info text-center">
                                    <h4 v-html="userData.baseData.loginid"></h4>
                                    <span class="badge bg-light-primary" v-html="userData.baseData.role"></span>
                                </div>
                            </div>
                        </div>
                        <div class="fw-bolder border-bottom font-small-2 pb-20 mb-1 mt-1 text-center">{{userData.baseData.geo_string}}</div>
                        <div class="info-container">
                            <ul class="list-unstyled pl-2">
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">Username:</span>
                                    <span v-html="userData.baseData.username"></span>
                                </li>
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">User Group:</span>
                                    <span v-html="userData.baseData.user_group"></span>
                                </li>
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">Status:</span>
                                    <span class="badge " :class="user_status==1? 'bg-light-success' : 'bg-light-danger'">{{user_status==1? 'Active' : 'Inactive'}}</span>
                                </li>
                            </ul>

                            <div class="justify-content-center pt-2 form-group">
                                <button class="btn btn-primary mb-1 form-control suspend-user waves-effect" @click="downloadBadge(userid)"><i class="feather icon-download"></i> Download Badge</button>
                                <button v-if="userDetails && permission.permission_value >=2" @click="userDetails = false" class="btn btn-outline-primary mb-1 form-control suspend-user waves-effect"><i class="feather icon-edit-2"></i>  Edit</button>
                                <button v-if="permission.permission_value >=2" class="btn form-control suspend-user waves-effect" :class="user_status== 1 ? 'btn-danger' : 'btn-success'" @click="userActivationDeactivation(userid)"><i class="feather" :class="user_status== 1 ? 'icon-user-x' : 'icon-user-check'"></i> {{user_status==1? ' Deactivate' : ' Activate'}}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /User Left Sidebar -->
            </div>
            <!--/ User Sidebar -->

            <!-- User Content -->
            <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                <!-- User Details: Start -->
                <div v-if="userDetails">
                    <!-- User Details -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Details</h4>
                            <button v-if="permission.permission_value >=2" class="btn btn-primary btn-sm waves-effect waves-float waves-light" @click="userDetails = false">
                                <i class="feather icon-edit-2"></i> <span> Edit</span>
                            </button>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>

                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Firstname</label>
                                            {{checkIfEmpty(userData.identityData.first)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Middle</label>
                                            {{checkIfEmpty(userData.identityData.middle)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Lastname</label>
                                            {{checkIfEmpty(userData.identityData.last)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Gender</label>
                                            {{checkIfEmpty(userData.identityData.gender)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Phone No</label>
                                            {{checkIfEmpty(userData.identityData.phone)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Email</label>
                                            {{checkIfEmpty(userData.identityData.email)}}
                                        </td>
                                    </tr>

                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Finance</h4>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>
                                        <td colspan="2" class="user-detail-txt">
                                            <label class="d-block text-primary">Account Name</label>
                                            {{checkIfEmpty(userData.financeData.account_name)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Account Number</label>
                                            {{checkIfEmpty(userData.financeData.account_no)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Bank Name</label>
                                            {{checkIfEmpty(userData.financeData.bank_name)}}
                                        </td>
                                    </tr>

                                </table>
                            </div>
                        </div>
                    </div> 

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Role</h4>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>
                                        <td colspan="2" class="user-detail-txt">
                                            <label class="d-block text-primary">Role</label>
                                            {{checkIfEmpty(userData.baseData.role)}}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div> 
              
                </div>
                <!-- User Details: End -->

                <!-- User Details Form -->
                <form method="POST" @submit.stop.prevent="updateUserProfile()" v-else>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Details</h4>
                        </div>
                        <div class="card-body row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" id="firstname" v-model="userData.identityData.first" class="form-control firstname" placeholder="First Name" name="firstname" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Middle Name</label>
                                    <input type="text" id="middlename" v-model="userData.identityData.middle" class="form-control middlename" placeholder="Middle Name" name="middlename" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Lastname</label>
                                    <input type="text" id="lastname" v-model="userData.identityData.last" class="form-control lastname" placeholder="Last Name" name="lastname" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" v-model="userData.identityData.gender" class="form-control">
                                        <option :selected="userData.identityData.gender == 'Male'">Male</option>
                                        <option :selected="userData.identityData.gender == 'Female'">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Phone No</label>
                                    <input type="text" id="phoneno" maxlength="11" v-model="userData.identityData.phone" @keypress="numbersOnlyWithoutDot" class="form-control phoneno" placeholder="Phone No" name="phoneno" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" id="email" v-model="userData.identityData.email" class="form-control email" placeholder="Email" name="email" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- / User Details Form -->

                    <!--  Finance -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Finance</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Account Name</label>
                                        <input type="text" id="account_name" v-model="userData.financeData.account_name" class="form-control account_name" placeholder="Account Name" name="account_name" />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Account Number</label>
                                        <input type="text" id="account_no" @keypress="numbersOnlyWithoutDot" maxlength="10" v-model="userData.financeData.account_no" class="form-control account_no" placeholder="Account Number" name="account_no" />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Bank Name</label>
                                        <select name="bank_code" v-model="userData.financeData.bank_code" @change="changeBank($event)" class="form-control bank_code select2">
                                            <option v-for="b in bankListData" :value="b.bank_code" :selected="b.bank_code == userData.financeData.bank_code">{{b.bank_name}}</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!--  User Role and Priviledge -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Role</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="role" :disabled="permission.permission_value <2" v-model="userData.baseData.roleid" @change="changeRole($event)" class="form-control role select2">
                                            <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid" :selected="r.roleid == userData.baseData.roleid">{{r.role}}</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6">
                            <button type="button" @click="discardUpdate()" class="btn btn-outline-secondary form-control mt-2 waves-effect">Cancel</button>
                        </div>
                        <div class="col-6">
                            <button v-if="permission.permission_value >=2" class="btn btn-primary form-control mt-2 waves-effect waves-float waves-light">Update Details</button>
                        </div>
                    </div>


                </form>
                <!--/ Billing Finance -->
            </div>
            <!--/ User Content -->
        </div>
    `,
});

var vm = new Vue({
  el: "#app",
  data: {},
  methods: {},
  template: `
        <div>
            <page-body/>
        </div>
    `,
});
