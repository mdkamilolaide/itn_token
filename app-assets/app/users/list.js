/**
 * Users / List submodule — Vue 3 Composition API in place.
 * Three components: page-body (routes), user_list (main table + ~15 modals
 * + bulk actions), user_details (read/edit form).
 *
 * EventBus events (preserved names):
 *   g-event-goto-page    — routes between list and detail views
 *   g-event-update-user  — emitted after a successful update / activation
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

/* ------------------------------------------------------------------ */
/* page-body                                                            */
/* ------------------------------------------------------------------ */
const PageBody = {
  setup() {
    const page = ref("list");
    const gotoPageHandler = (data) => {
      page.value = data.page;
    };
    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
    });
    return { page };
  },
  template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'list'"><user_list/></div>
                <div v-show="page == 'detail'"><user_details/></div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* user_list                                                            */
/* ------------------------------------------------------------------ */
const UserList = {
  setup() {
    const fmtUtils = useFormat();

    const url = ref(window.common && window.common.BadgeService);
    const tableData = ref([]);
    const defaultStateId = ref("");
    const roleListData = ref([]);
    const userRole = reactive({ currentUserRole: "", currentUserid: "" });
    const geoData = ref([]);
    const permission = ref(
      typeof getPermission === "function"
        ? getPermission(typeof per !== "undefined" ? per : null, "users") || {
            permission_value: 0,
          }
        : { permission_value: 0 },
    );
    const userGroup = ref([]);
    const checkToggle = ref(false);
    const filterState = ref(false);
    const filters = ref(false);
    const isBulkRole = ref(false);

    const tableOptions = reactive({
      total: 1,
      pageLength: 1,
      perPage: 10,
      currentPage: 1,
      orderDir: "asc",
      orderField: 0,
      limitStart: 0,
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
    });

    const geoLevelForm = reactive({
      geoLevel: "",
      geoLevelId: 0,
      currentUserLoginid: "",
      userid: "",
      isBulk: false,
    });
    const geoIndicator = reactive({
      state: 50,
      currentLevelId: 0,
      lga: "",
      cluster: "",
      ward: "",
    });
    const geoLevelData = ref([]);
    const sysDefaultData = ref({});
    const lgaLevelData = ref([]);
    const clusterLevelData = ref([]);
    const wardLevelData = ref([]);
    const dpLevelData = ref([]);

    const userPass = reactive({
      pass: "",
      loginid: "",
      name: "",
      isBulk: false,
    });
    const workHourExtensionForm = reactive({
      extensionHour: "",
      extensionDate: "",
      authorizationUserId: "",
      affectedUserIds: [],
      isBulk: false,
    });

    const reloadUserListOnUpdate = () => {
      paginationDefault();
      loadTableData();
    };

    const loadTableData = () => {
      overlay.show();
      var u = common.TableService;
      axios
        .get(
          u +
            "?qid=001&draw=" +
            tableOptions.currentPage +
            "&order_column=" +
            tableOptions.orderField +
            "&length=" +
            tableOptions.perPage +
            "&start=" +
            tableOptions.limitStart +
            "&order_dir=" +
            tableOptions.orderDir +
            "&ac=" +
            tableOptions.filterParam.user_status +
            "&lo=" +
            tableOptions.filterParam.loginid +
            "&na=" +
            tableOptions.filterParam.fullname +
            "&gr=" +
            tableOptions.filterParam.user_group +
            "&ph=" +
            tableOptions.filterParam.phoneno +
            "&gl=" +
            tableOptions.filterParam.geo_level +
            "&gl_id=" +
            tableOptions.filterParam.geo_level_id +
            "&bv=" +
            tableOptions.filterParam.bank_status +
            "&ri=" +
            tableOptions.filterParam.role_id,
        )
        .then((response) => {
          var d = response && response.data;
          tableData.value = Array.isArray(d && d.data) ? d.data : [];
          tableOptions.total = (d && d.recordsTotal) || 0;
          if (tableOptions.currentPage == 1) paginationDefault();
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };

    const selectAll = () => {
      for (var i = 0; i < tableData.value.length; i++)
        tableData.value[i].pick = true;
    };
    const uncheckAll = () => {
      for (var i = 0; i < tableData.value.length; i++)
        tableData.value[i].pick = false;
    };
    const selectToggle = () => {
      if (checkToggle.value === false) {
        selectAll();
        checkToggle.value = true;
      } else {
        uncheckAll();
        checkToggle.value = false;
      }
    };
    const checkedBg = (pickOne) => {
      return pickOne != "" ? "bg-select" : "";
    };

    const toggleFilter = () => {
      if (filterState.value === false) filters.value = false;
      return (filterState.value = !filterState.value);
    };
    const selectedItems = () => {
      return tableData.value.filter((r) => r.pick);
    };
    const selectedID = () => {
      return tableData.value.filter((r) => r.pick).map((r) => r.userid);
    };

    const paginationDefault = () => {
      tableOptions.pageLength = Math.ceil(
        tableOptions.total / tableOptions.perPage,
      );
      tableOptions.limitStart = Math.ceil(
        (tableOptions.currentPage - 1) * tableOptions.perPage,
      );
      tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
      tableOptions.isPrev = tableOptions.currentPage > 1;
    };
    const nextPage = () => {
      resetSelected();
      tableOptions.currentPage += 1;
      paginationDefault();
      loadTableData();
    };
    const prevPage = () => {
      resetSelected();
      tableOptions.currentPage -= 1;
      paginationDefault();
      loadTableData();
    };
    const resetSelected = () => {
      uncheckAll();
      checkToggle.value = false;
      totalCheckedBox();
    };
    const currentPage = () => {
      paginationDefault();
      if (tableOptions.currentPage < 1)
        alert.Error("ERROR", "The Page requested doesn't exist");
      else if (tableOptions.currentPage > tableOptions.pageLength)
        alert.Error("ERROR", "The Page requested doesn't exist");
      else loadTableData();
    };
    const changePerPage = (val) => {
      resetSelected();
      tableOptions.currentPage = 1;
      tableOptions.perPage = val;
      paginationDefault();
      loadTableData();
    };
    const sort = (col) => {
      if (tableOptions.orderField === col)
        tableOptions.orderDir =
          tableOptions.orderDir === "asc" ? "desc" : "asc";
      else tableOptions.orderField = col;
      paginationDefault();
      loadTableData();
    };

    const applyFilter = () => {
      var checkFill = 0;
      checkFill += tableOptions.filterParam.user_status != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.loginid != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.fullname != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.user_group != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.phoneno != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.geo_level != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.bank_status != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.role_id != "" ? 1 : 0;
      if (checkFill > 0) {
        toggleFilter();
        filters.value = true;
        paginationDefault();
        loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
      }
    };
    const removeSingleFilter = (column_name) => {
      tableOptions.filterParam[column_name] = "";
      if (column_name == "geo_string") {
        tableOptions.filterParam.geo_level = "";
        tableOptions.filterParam.geo_level_id = "";
        tableOptions.filterParam.geo_string = "";
      }
      if (column_name == "role") {
        tableOptions.filterParam.role_id = "";
        tableOptions.filterParam.role = "";
      }
      var g = 0;
      for (var k in tableOptions.filterParam) {
        if (tableOptions.filterParam[k] != "") g++;
      }
      if (g == 0) filters.value = false;
      paginationDefault();
      loadTableData();
    };
    const clearAllFilter = () => {
      filters.value = false;
      $(".select2").val("").trigger("change");
      tableOptions.filterParam.user_status = "";
      tableOptions.filterParam.loginid = "";
      tableOptions.filterParam.fullname = "";
      tableOptions.filterParam.user_group = "";
      tableOptions.filterParam.phoneno = "";
      tableOptions.filterParam.geo_level = "";
      tableOptions.filterParam.geo_level_id = "";
      tableOptions.filterParam.geo_string = "";
      tableOptions.filterParam.bank_status = "";
      tableOptions.filterParam.role_id = "";
      tableOptions.filterParam.role = "";
      paginationDefault();
      loadTableData();
    };

    const userActivationDeactivation = (actionid) => {
      var ids = actionid === "all" ? selectedID() : [actionid];
      if (ids.length < 1) {
        alert.Error("ERROR", "No User selected");
        return;
      }
      var u = common.DataService;
      $.confirm({
        title: "WARNING!",
        content:
          "Are you sure you want to De/Activate Users? <br><br>Make sure you know what you are doing before you De/Activate the user",
        buttons: {
          delete: {
            text: "De/Activate",
            btnClass: "btn btn-danger mr-1",
            action: () => {
              axios
                .post(u + "?qid=001", JSON.stringify(ids))
                .then((response) => {
                  overlay.hide();
                  if (response.data.result_code == "200") {
                    loadTableData();
                    alert.Success(
                      "SUCCESS",
                      response.data.total + " Users Affected",
                    );
                  } else {
                    alert.Error("ERROR", "User De/Activation failed");
                  }
                })
                .catch((error) => {
                  overlay.hide();
                  alert.Error("ERROR", safeMessage(error));
                });
            },
          },
          cancel: () => {
            overlay.hide();
          },
        },
      });
    };

    const goToDetail = (userid, user_status) => {
      bus.emit("g-event-goto-page", {
        userid: userid,
        page: "detail",
        user_status: user_status,
        role: roleListData.value,
      });
    };

    const getRoleList = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=007")
        .then((response) => {
          roleListData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };

    const getGeoLocation = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen009")
        .then((response) => {
          geoData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const setLocation = (select_index) => {
      var i = select_index ? select_index : 0;
      var row = geoData.value[i];
      if (!row) return;
      tableOptions.filterParam.geo_level = row.geo_level;
      tableOptions.filterParam.geo_level_id = row.geo_level_id;
      tableOptions.filterParam.geo_string = row.geo_string;
    };
    const setRole = (event) => {
      tableOptions.filterParam.role =
        event.target.options[event.target.options.selectedIndex].text;
    };

    const changeUserGeoLevelModal = (
      userid,
      loginid,
      geo_level,
      geolevelid,
      isBulk,
    ) => {
      isBulk = !!isBulk;
      if (isBulk && selectedID().length < 1) {
        alert.Error("ERROR", "No User selected");
        hideGeoModal();
        return;
      }
      geoLevelForm.userid = userid;
      geoLevelForm.geoLevel = geo_level;
      geoLevelForm.currentUserLoginid = loginid;
      geoLevelForm.geoLevelId = geolevelid;
      if (isBulk) {
        geoLevelForm.geoLevel = "state";
        geoLevelForm.geoLevelId = sysDefaultData.value.stateid;
      }
      geoLevelForm.isBulk = isBulk;
      $("#geoLevelModal").modal("show");
    };

    const updateRole = () => {
      var u = common.DataService;
      if (isBulkRole.value === true) {
        submitBulkRole();
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
            action: () => {
              axios
                .post(
                  u +
                    "?qid=008&r=" +
                    userRole.currentUserRole +
                    "&u=" +
                    userRole.currentUserid,
                )
                .then((response) => {
                  if (response.data.result_code == "200") {
                    loadTableData();
                    overlay.hide();
                    $("#roleForm").modal("hide");
                    alert.Success("SUCCESS", "User Role Updated");
                  } else {
                    overlay.hide();
                    alert.Error("ERROR", "User Role not Updated");
                  }
                })
                .catch((error) => {
                  overlay.hide();
                  alert.Error("ERROR", safeMessage(error));
                });
            },
          },
          cancel: () => {
            overlay.hide();
          },
        },
      });
    };

    const refreshData = () => {
      paginationDefault();
      getAllUserGroup();
      loadTableData();
    };
    const downloadBadge = (userid) => {
      overlay.show();
      window.open(url.value + "?qid=002&e=" + userid, "_parent");
      overlay.hide();
    };
    const downloadBadges = () => {
      overlay.show();
      if (parseInt(selectedID().length) > 0) {
        window.open(url.value + "?qid=003&e=" + selectedID(), "_parent");
      } else {
        alert.Error("Badge Download Failed", "No user selected");
      }
      overlay.hide();
    };
    const hideGeoModal = () => {
      geoLevelForm.currentUserLoginid = "";
      geoLevelForm.geoLevel = "";
      geoLevelForm.geoLevelId = "";
      geoLevelForm.userid = "";
      geoLevelForm.isBulk = false;
      $("#geoLevelModal").modal("hide");
    };
    const showPassResetModal = (loginid, name, isBulk) => {
      isBulk = !!isBulk;
      if (isBulk && (selectedID() || []).length < 1) {
        alert.Error("ERROR", "No User selected");
        hidePassResetModal();
        return;
      }
      userPass.loginid = loginid;
      userPass.name = (name && name.trim()) || loginid;
      userPass.isBulk = isBulk;
      $("#resetPassword").modal("show");
    };
    const hidePassResetModal = () => {
      userPass.pass = "";
      userPass.loginid = "";
      userPass.name = "";
      userPass.isBulk = false;
      $("#resetPassword").modal("hide");
    };
    const resetPassword = () => {
      var u = common.DataService;
      var isBulk = userPass.isBulk;
      var loginid = userPass.loginid;
      var name = userPass.name;
      var pass = userPass.pass;
      var selected = selectedID() || [];
      var selectedId = isBulk ? JSON.stringify(selected) : loginid;
      var confirmTitle = isBulk ? selected.length + " Users" : name;
      var successMessage = isBulk
        ? selected.length + " Users Password Reset Successfully"
        : name + " Password Reset Successfully";
      var qid = isBulk ? "012a" : "012";
      var confirmationMessage =
        "<div>Are you sure you want to reset the password for <b>" +
        confirmTitle +
        "</b>?<br>" +
        "Please confirm only if you're certain about this action.</div>";

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
                .post(u + "?qid=" + qid, { loginid: selectedId, new: pass })
                .then((res) => {
                  overlay.hide();
                  if (res.data.result_code === 200) {
                    alert.Success("SUCCESS", successMessage);
                    hidePassResetModal();
                    resetSelected();
                  } else {
                    alert.Error("ERROR", "User Role not Updated");
                  }
                })
                .catch((error) => {
                  overlay.hide();
                  alert.Error("ERROR", safeMessage(error));
                });
            },
          },
          cancel: () => {
            overlay.hide();
          },
        },
      });
    };

    const showWorkExtensionModal = (isBulk, userId) => {
      isBulk = !!isBulk;
      userId = userId || "";
      var selectedUsers = selectedID() || [];
      if (isBulk && selectedUsers.length < 1) {
        alert.Error("ERROR", "No User selected for Working Hour Extension");
        hideWorkHourExtensionModal();
        return;
      }
      workHourExtensionForm.affectedUserIds = isBulk
        ? JSON.stringify(selectedUsers)
        : userId;
      workHourExtensionForm.isBulk = isBulk;
      $("#workHourModal").modal("show");
    };

    const showRoleModal = () => {
      var selectedUsers = selectedID() || [];
      isBulkRole.value = true;
      if (selectedUsers.length < 1) {
        alert.Error("ERROR", "No User selected for Role Change");
        return;
      }
      $("#roleForm").modal("show");
    };
    const submitBulkRole = () => {
      var ids = selectedID() || [];
      var u = common.DataService;
      var len = ids.length;
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
            action: () => {
              axios
                .post(
                  u + "?qid=008a&r=" + userRole.currentUserRole,
                  JSON.stringify(ids),
                )
                .then((response) => {
                  if (response.data.result_code === 200) {
                    isBulkRole.value = false;
                    userRole.currentUserRole = "";
                    userRole.currentUserid = "";
                    loadTableData();
                    uncheckAll();
                    totalCheckedBox();
                    overlay.hide();
                    hideRoleModal();
                    alert.Success("SUCCESS", len + " User Role Updated");
                  } else {
                    overlay.hide();
                    alert.Error("ERROR", "User Role not Updated");
                  }
                })
                .catch((error) => {
                  overlay.hide();
                  alert.Error("ERROR", safeMessage(error));
                });
            },
          },
          cancel: () => {
            overlay.hide();
          },
        },
      });
    };
    const hideRoleModal = () => {
      $("#roleForm").modal("hide");
    };
    const hideWorkHourExtensionModal = () => {
      workHourExtensionForm.extensionHour = "";
      workHourExtensionForm.extensionDate = "";
      workHourExtensionForm.isBulk = false;
      workHourExtensionForm.affectedUserIds = [];
      try {
        $("#extensionDate")
          .flatpickr({
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
            minDate: "today",
          })
          .clear();
      } catch (e) {
        /* swallow */
      }
      $("#workHourModal").modal("hide");
    };
    const onSubmitAddWorkingHour = () => {
      var extensionHour = workHourExtensionForm.extensionHour;
      var extensionDate = workHourExtensionForm.extensionDate;
      var affectedUserIds = workHourExtensionForm.affectedUserIds;
      var isBulk = workHourExtensionForm.isBulk;
      var $form = $(".change-working-hour-form");
      var selectedCount = (selectedID() || []).length;

      if ($form.length && typeof $form.valid === "function" && !$form.valid()) {
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
      var titleLabel = isBulk ? selectedCount + " Users" : "1 User";
      var successMessage = titleLabel + " Work Hour Extended Successfully";
      var confirmationMessage =
        "<div>Are you sure you want to add work hour for <b>" +
        titleLabel +
        "</b>?<br>" +
        "Please confirm only if you're certain about this action.</div>";
      $.confirm({
        title: "WARNING!",
        content: confirmationMessage,
        buttons: {
          confirm: {
            text: "Extend Work Hour",
            btnClass: "btn btn-danger mr-1",
            action: () => {
              var u = common.DataService + "?qid=015";
              var currentUserId =
                (document.getElementById("v_g_id") &&
                  document.getElementById("v_g_id").value) ||
                "";
              axios
                .post(u, {
                  authorizationUserId: currentUserId,
                  bulkUserIds: affectedUserIds,
                  extensionHour: extensionHour,
                  extensionDate: extensionDate,
                })
                .then((res) => {
                  overlay.hide();
                  if (res.data.result_code === 200) {
                    hideWorkHourExtensionModal();
                    resetSelected();
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
                  alert.Error("ERROR", safeMessage(error));
                });
            },
          },
          cancel: () => {
            overlay.hide();
          },
        },
      });
    };

    const getsysDefaultDataSettings = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen007")
        .then((response) => {
          if (response.data.data && response.data.data.length > 0) {
            sysDefaultData.value = response.data.data[0];
            getLgasLevel(response.data.data[0].stateid);
            geoLevelForm.geoLevel = "state";
            geoLevelForm.geoLevelId = response.data.data[0].stateid;
            defaultStateId.value = response.data.data[0].stateid;
          }
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getGeoLevel = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen001")
        .then((response) => {
          geoLevelData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getLgasLevel = (stateid) => {
      overlay.show();
      axios
        .post(common.DataService + "?qid=gen003", JSON.stringify(stateid))
        .then((response) => {
          lgaLevelData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getClusterLevel = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen004&e=" + geoIndicator.cluster)
        .then((response) => {
          clusterLevelData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getWardLevel = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen005&e=" + geoIndicator.lga)
        .then((response) => {
          wardLevelData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getDpLevel = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen006&wardid=" + geoIndicator.ward)
        .then((response) => {
          dpLevelData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const changeGeoLevel = () => {
      if (geoLevelForm.geoLevel == "country") {
        alert.Error(
          "ERROR",
          "Invalid Geo-Level selected, please select a valid Geo-Level",
        );
      } else if (geoLevelForm.geoLevel == "state") {
        geoLevelForm.geoLevelId = defaultStateId.value;
      } else {
        geoLevelForm.geoLevelId = "";
        geoIndicator.lga = "";
        geoIndicator.ward = "";
        geoIndicator.cluster = "";
      }
    };
    const changeUserRoleModal = (userid, roleid) => {
      userRole.currentUserRole = roleid;
      userRole.currentUserid = userid;
    };
    const onSubmitUpdateGeoLevel = () => {
      var userid = geoLevelForm.userid;
      var geoLevel = geoLevelForm.geoLevel;
      var geoLevelId = geoLevelForm.geoLevelId;
      var isBulk = geoLevelForm.isBulk;
      var currentUserLoginid = geoLevelForm.currentUserLoginid;
      var selectedUsers = selectedID() || [];
      var userIdentifier = isBulk ? JSON.stringify(selectedUsers) : userid;
      var titleLabel = isBulk
        ? selectedUsers.length + " Users"
        : currentUserLoginid;
      var successMessage = titleLabel + " Geo Level Successfully Changed";
      var qid = isBulk ? "009a" : "009";
      var u = common.DataService + "?qid=" + qid;
      var confirmationMessage =
        "<div>Are you sure you want to change the User Geo Level for <b>" +
        titleLabel +
        "</b>?<br>" +
        "Please confirm only if you're certain about this action.</div>";
      $.confirm({
        title: "WARNING!",
        content: confirmationMessage,
        buttons: {
          confirm: {
            text: "Update Geo Level",
            btnClass: "btn btn-danger mr-1",
            action: () => {
              axios
                .post(u, { u: userIdentifier, l: geoLevel, id: geoLevelId })
                .then((res) => {
                  overlay.hide();
                  if (res.data.result_code === 200) {
                    loadTableData();
                    hideGeoModal();
                    resetSelected();
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
                  alert.Error("ERROR", safeMessage(error));
                });
            },
          },
          cancel: () => {
            overlay.hide();
          },
        },
      });
    };
    const getAllUserGroup = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=026")
        .then((response) => {
          var rows = (response.data && response.data.data) || [];
          var group = [];
          for (var i = 0; i < rows.length; i++)
            group.push(rows[i]["user_group"]);
          userGroup.value = group;
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };

    // Lightweight autocomplete (preserved from v2). Wires DOM events
    // onto an input by id and fills tableOptions.filterParam.user_group.
    const autocomplete = (inp, arr) => {
      if (!inp) return;
      var currentFocus;
      inp.addEventListener("input", function () {
        var a,
          b,
          i,
          val = this.value;
        closeAllLists();
        if (!val) return false;
        currentFocus = -1;
        a = document.createElement("DIV");
        a.setAttribute("id", this.id + "autocomplete-list");
        a.setAttribute("class", "autocomplete-items");
        this.parentNode.appendChild(a);
        for (i = 0; i < arr.length; i++) {
          if (
            String(arr[i]).substr(0, val.length).toUpperCase() ==
            val.toUpperCase()
          ) {
            b = document.createElement("DIV");
            b.innerHTML =
              "<strong>" +
              arr[i].substr(0, val.length) +
              "</strong>" +
              arr[i].substr(val.length);
            b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
            b.addEventListener("click", function () {
              inp.value = this.getElementsByTagName("input")[0].value;
              closeAllLists();
            });
            a.appendChild(b);
          }
        }
      });
      inp.addEventListener("keydown", function (e) {
        var x = document.getElementById(this.id + "autocomplete-list");
        if (x) x = x.getElementsByTagName("div");
        if (e.keyCode == 40) {
          currentFocus++;
          addActive(x);
        } else if (e.keyCode == 38) {
          currentFocus--;
          addActive(x);
        } else if (e.keyCode == 13) {
          e.preventDefault();
          if (currentFocus > -1 && x) x[currentFocus].click();
        }
      });
      const addActive = (x) => {
        if (!x) return false;
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = x.length - 1;
        x[currentFocus].classList.add("autocomplete-active");
      };
      const removeActive = (x) => {
        for (var i = 0; i < x.length; i++)
          x[i].classList.remove("autocomplete-active");
      };
      const closeAllLists = (elmnt) => {
        var x = document.getElementsByClassName("autocomplete-items");
        for (var i = 0; i < x.length; i++) {
          if (elmnt != x[i] && elmnt != inp) {
            x[i].parentNode.removeChild(x[i]);
            tableOptions.filterParam.user_group = inp.value;
          }
        }
      };
      document.addEventListener("click", (e) => {
        closeAllLists(e.target);
      });
    };
    const loadAuto = () => {
      autocomplete(document.getElementById("user_group"), userGroup.value);
    };

    const verifyAccount = (
      userid,
      index,
      first_name,
      middle_name,
      last_name,
    ) => {
      overlay.show();
      var f_name = first_name == null ? "" : first_name;
      var l_name = last_name == null ? "" : last_name;
      axios
        .post(common.DataService + "?qid=013&userid=" + userid)
        .then((response) => {
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
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const accountVerificationStatus = (status) => {
      if (status == "success")
        return ["txt-success", "bg-status-success icon-check-circle"];
      if (status == "failed")
        return ["txt-failed", "bg-status-failed icon-x-circle"];
      if (status == "warning")
        return ["txt-warning", "bg-status-warning icon-circle"];
      return ["", "icon-circle"];
    };
    const totalCheckedBox = () => {
      var total = selectedID().length;
      var el = document.getElementById("total-selected");
      if (!el) return;
      if (total > 0) {
        el.innerHTML =
          '<span id="selected-counter" class="badge badge-primary btn-icon"><span class="badge badge-success">' +
          total +
          "</span> Selected</span>";
      } else {
        el.replaceChildren();
      }
    };
    const exportUserData = async () => {
      var common_url =
        "&draw=" +
        tableOptions.currentPage +
        "&order_column=" +
        tableOptions.orderField +
        "&length=" +
        tableOptions.perPage +
        "&start=" +
        tableOptions.limitStart +
        "&order_dir=" +
        tableOptions.orderDir +
        "&ac=" +
        tableOptions.filterParam.user_status +
        "&lo=" +
        tableOptions.filterParam.loginid +
        "&na=" +
        tableOptions.filterParam.fullname +
        "&gr=" +
        tableOptions.filterParam.user_group +
        "&ph=" +
        tableOptions.filterParam.phoneno +
        "&gl=" +
        tableOptions.filterParam.geo_level +
        "&gl_id=" +
        tableOptions.filterParam.geo_level_id +
        "&bv=" +
        tableOptions.filterParam.bank_status +
        "&ri=" +
        tableOptions.filterParam.role_id;
      var veriUrl = "qid=014" + common_url;
      var dlString = "qid=001" + common_url;
      var filename =
        (tableOptions.filterParam.geo_string
          ? tableOptions.filterParam.geo_string
          : "Recent ") +
        " " +
        (tableOptions.filterParam.loginid
          ? tableOptions.filterParam.loginid
          : "Recent ") +
        " User List";

      overlay.show();
      var count = await new Promise((resolve) => {
        $.ajax({
          url: common.DataService,
          type: "POST",
          data: veriUrl,
          dataType: "json",
          success: (data) => {
            resolve(data.total);
          },
        });
      });
      var downloadMax =
        (window.common && window.common.ExportDownloadLimit) || 25000;
      if (parseInt(count) > downloadMax) {
        alert.Error(
          "Download Error",
          "Unable to download data because it has exceeded download limit, download limit is " +
            downloadMax,
        );
      } else if (parseInt(count) == 0) {
        alert.Error("Download Error", "No data found");
      } else {
        alert.Info("DOWNLOADING...", "Downloading " + count + " record(s)");
        var outcome = await new Promise((resolve) => {
          $.ajax({
            url: common.ExportService,
            type: "POST",
            data: dlString,
            success: (data) => {
              resolve(data);
            },
          });
        });
        var exportData = JSON.parse(outcome);
        if (window.Jhxlsx && typeof window.Jhxlsx.export === "function") {
          window.Jhxlsx.export(exportData, { fileName: filename });
        }
      }
      resetSelected();
      overlay.hide();
    };

    const checkIfAndReturnEmpty = (data) => {
      return data === null || data === "" ? "" : data;
    };
    const numbersOnlyWithoutDot = (evt) => {
      var e = evt || window.event;
      var charCode = e.which || e.keyCode;
      if (charCode > 31 && (charCode < 48 || charCode > 57)) e.preventDefault();
      return true;
    };

    onMounted(() => {
      getGeoLocation();

      // jQuery select2 init for the geo dropdown — preserved as-is.
      var select = $(".select2");
      select.each(function () {
        var $this = $(this);
        $this.wrap('<div class="position-relative"></div>');
        $this
          .select2({
            dropdownAutoWidth: true,
            width: "100%",
            dropdownParent: $this.parent(),
          })
          .on("change", function () {
            setLocation(this.value);
          });
      });
      $(".select2-selection__arrow").html(
        '<i class="feather icon-chevron-down"></i>',
      );

      getAllUserGroup();
      loadTableData();
      getGeoLevel();
      getsysDefaultDataSettings();
      getRoleList();
      bus.on("g-event-update-user", reloadUserListOnUpdate);

      $(".form-password-toggle1 .input-group-text").on("click", function (e) {
        e.preventDefault();
        var $this = $(this);
        var inputGroupText = $this.closest(".form-password-toggle1");
        var formPasswordToggleIcon = $this;
        var formPasswordToggleInput = inputGroupText.find("input");
        if (formPasswordToggleInput.attr("type") === "text") {
          formPasswordToggleInput.attr("type", "password");
          if (typeof feather !== "undefined" && feather && feather.icons) {
            formPasswordToggleIcon
              .find("svg")
              .replaceWith(
                feather.icons["eye"].toSvg({ class: "font-small-4" }),
              );
          }
        } else if (formPasswordToggleInput.attr("type") === "password") {
          formPasswordToggleInput.attr("type", "text");
          if (typeof feather !== "undefined" && feather && feather.icons) {
            formPasswordToggleIcon
              .find("svg")
              .replaceWith(
                feather.icons["eye-off"].toSvg({ class: "font-small-4" }),
              );
          }
        }
      });

      var $form = $(".change-working-hour-form");
      if ($form.length) {
        $(".date").flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
          minDate: "today",
        });
        if (typeof $form.validate === "function") {
          $form.validate({
            rules: {
              extensionHour: { required: true, number: true },
              extensionDate: { required: true },
            },
          });
        }
      }
    });

    onBeforeUnmount(() => {
      bus.off("g-event-update-user", reloadUserListOnUpdate);
    });

    return {
      // state
      url,
      tableData,
      defaultStateId,
      roleListData,
      userRole,
      geoData,
      permission,
      userGroup,
      checkToggle,
      filterState,
      filters,
      isBulkRole,
      tableOptions,
      geoLevelForm,
      geoIndicator,
      geoLevelData,
      sysDefaultData,
      lgaLevelData,
      clusterLevelData,
      wardLevelData,
      dpLevelData,
      userPass,
      workHourExtensionForm,
      // methods
      reloadUserListOnUpdate,
      loadTableData,
      selectAll,
      uncheckAll,
      selectToggle,
      checkedBg,
      toggleFilter,
      selectedItems,
      selectedID,
      totalCheckedBox,
      nextPage,
      prevPage,
      resetSelected,
      currentPage,
      paginationDefault,
      changePerPage,
      sort,
      applyFilter,
      removeSingleFilter,
      clearAllFilter,
      userActivationDeactivation,
      goToDetail,
      getRoleList,
      getGeoLocation,
      setLocation,
      setRole,
      changeUserGeoLevelModal,
      updateRole,
      refreshData,
      downloadBadge,
      downloadBadges,
      hideGeoModal,
      showPassResetModal,
      hidePassResetModal,
      resetPassword,
      showWorkExtensionModal,
      showRoleModal,
      submitBulkRole,
      hideRoleModal,
      hideWorkHourExtensionModal,
      onSubmitAddWorkingHour,
      getsysDefaultDataSettings,
      getGeoLevel,
      getLgasLevel,
      getClusterLevel,
      getWardLevel,
      getDpLevel,
      changeGeoLevel,
      changeUserRoleModal,
      onSubmitUpdateGeoLevel,
      getAllUserGroup,
      autocomplete,
      loadAuto,
      verifyAccount,
      accountVerificationStatus,
      exportUserData,
      checkIfAndReturnEmpty,
      numbersOnlyWithoutDot,
      // utility methods
      capitalize: fmtUtils.capitalize,
      formatNumber: fmtUtils.formatNumber,
      displayDate: fmtUtils.displayDate,
      fmt: fmtUtils.fmt,
    };
  },
  template: `
        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item active">Users List</li>
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
                        <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" @click="userActivationDeactivation('all')">De/Activate User</a>
                        <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" @click="exportUserData()">Export Users</a>
                        <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0)" @click="showPassResetModal('', '', true)">Reset Password</a>
                        <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0)" @click="showRoleModal()">Change Role</a>
                        <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0)" @click="showWorkExtensionModal(true, '')">Work Hour Extension</a>
                        <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0)" @click="changeUserGeoLevelModal('', '', '', '', true)">Change Geo Level</a>
                        <a class="dropdown-item" href="javascript:void(0)" @click="downloadBadges()">Download Badge</a>
                    </div>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box"
                              v-if="String(filterParam).length > 0 && i != 'geo_level' && i != 'geo_level_id' && i != 'role_id'"
                              @click="removeSingleFilter(i)">
                            {{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i>
                        </span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Active Status</label>
                                        <select v-model="tableOptions.filterParam.user_status" class="form-control active">
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
                                        <select v-model="tableOptions.filterParam.bank_status" class="form-control active">
                                            <option value="">All</option>
                                            <option value="success">Successful Verification</option>
                                            <option value="none">Pending Verification</option>
                                            <option value="failed">Failed Verification</option>
                                            <option value="warning">Invalid Account Name</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select v-model="tableOptions.filterParam.role_id" @change="setRole($event)" class="form-control role">
                                            <option value="">All</option>
                                            <option v-for="r in roleListData" :key="r.roleid" :value="r.roleid">{{ r.role }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4 col-md-3 col-lg-3">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button>
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
                                            <input type="checkbox" class="custom-control-input" :checked="checkToggle" @change="selectToggle(); totalCheckedBox()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(0)" class="pl-0">Login ID
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(8)" class="pl-1">Fullname
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(5)" class="pl-1">Role
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(16)" class="pl-1">Geo String
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 16 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 16 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(12)" class="pl-1">Status
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 12 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 12 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th class="pl-1 pr-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in tableData" :key="g.userid || g.loginid" :class="checkedBg(g.pick)">
                                    <td>
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.loginid" v-model="g.pick" @change="totalCheckedBox()" />
                                            <label class="custom-control-label" :for="g.loginid"></label>
                                        </div>
                                    </td>
                                    <td class="pl-0" :class="g.is_verified == 1 ? accountVerificationStatus(g.verification_status)[0] : ''" @dblclick="verifyAccount(g.userid, g, g.first, g.middle, g.last)">
                                        <i class="verified feather" :class="g.is_verified == 1 ? accountVerificationStatus(g.verification_status)[1] : 'icon-circle'" data-toggle="tooltip" data-placement="top" title="Double Click on this Icon to Verify Bank Details"></i>
                                        {{ g.loginid }}
                                    </td>
                                    <td class="pl-1">{{ g.first }} {{ g.middle }} {{ g.last }}</td>
                                    <td class="pl-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder text-primary" v-html="g.role ? g.role : 'Role Not Assigned'"></span>
                                                </span>
                                                <small class="emp_post text-muted" v-html="g.user_group ? g.user_group : ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-primary" v-html="g.geo_level ? g.geo_level.toUpperCase() : 'Geo Not Assigned'"></small>
                                                <small class="emp_post text-muted" v-html="g.geo_string ? capitalize(g.geo_string) : ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1"><span class="badge rounded-pill font-small-1" :class="g.active == 1 ? 'bg-success' : 'bg-danger'">{{ g.active == 1 ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="pl-1 pr-1">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <i class="feather icon-more-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToDetail(g.userid, g.active)">
                                                    <i class="feather icon-eye mr-50"></i><span>Details</span>
                                                </a>
                                                <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#geoLevelModal" data-backdrop="static" data-keyboard="false" @click="changeUserGeoLevelModal(g.userid, g.loginid, g.geo_level, g.geo_level_id)">
                                                    <i class="feather icon-user mr-50"></i><span>Change Geo Level</span>
                                                </a>
                                                <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#roleForm" @click="changeUserRoleModal(g.userid, g.roleid, '')">
                                                    <i class="feather icon-user mr-50"></i><span>Change User Role</span>
                                                </a>
                                                <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#resetPassword" @click="showPassResetModal(g.loginid, checkIfAndReturnEmpty(g.first) + ' ' + checkIfAndReturnEmpty(g.middle) + ' ' + checkIfAndReturnEmpty(g.last))">
                                                    <i class="feather icon-user mr-50"></i><span>Reset User Password</span>
                                                </a>
                                                <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" @click="userActivationDeactivation(g.userid)">
                                                    <i class="feather icon-user-check mr-50"></i><span>De/Activate</span>
                                                </a>
                                                <a class="dropdown-item" href="javascript:void(0);" @click="verifyAccount(g.userid, g, g.first, g.middle, g.last)">
                                                    <i class="feather icon-alert-triangle mr-50"></i><span>Verify Bank Details</span>
                                                </a>
                                                <a class="dropdown-item" href="javascript:void(0);" @click="downloadBadge(g.userid)">
                                                    <i class="feather icon-download mr-50"></i><span>Download Badge</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length === 0"><td class="text-center pt-2" colspan="7"><small>No User Added</small></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        <div class="content-fluid">
                            <div class="row">
                                <div class="col-12 col-xl-4 col-md-4 col-sm-5">
                                    <div class="dropdown sort-dropdown mb-1 mb-sm-0">
                                        <button class="btn filter-btn btn-primary dropdown-toggle border text-dark" type="button" id="tablePaginationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{ tableOptions.limitStart + 1 }} - {{ tableOptions.limitStart + tableData.length }} of {{ tableOptions.total }}
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePaginationDropdown">
                                            <a @click="changePerPage(g)" v-for="g in tableOptions.aLength" :key="g" class="dropdown-item" href="javascript:void(0);">{{ g }}</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 col-md-8 col-sm-7 text-right text-pag">
                                    <div class="btn-group">
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="!tableOptions.isPrev">
                                            <i data-feather='chevron-left'></i> Prev
                                        </button>
                                        <input @keyup.enter="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary"> of {{ tableOptions.pageLength }}</small>
                                        </button>
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round" :disabled="!tableOptions.isNext">
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

            <!-- Change User Role Modal -->
            <div class="modal fade text-left" id="roleForm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="myModalLabel33">Change User Role</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <form method="POST" @submit.stop.prevent="isBulkRole ? submitBulkRole() : updateRole()">
                            <div class="modal-body">
                                <label>Role:</label>
                                <div class="form-group">
                                    <select v-model="userRole.currentUserRole" class="form-control role">
                                        <option value="">Choose Role</option>
                                        <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid">{{ r.role }}</option>
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

            <!-- Reset User Password Modal -->
            <div class="modal fade text-left" id="resetPassword" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="resetPassword" data-keyboard="false" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title text-primary" id="myModalLabel34">Reset {{ userPass.isBulk ? selectedID().length + ' Users' : ' User' }} Password</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hidePassResetModal()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="validate-form" method="POST" @submit.stop.prevent="resetPassword()">
                            <input type="text" name="username" autocomplete="username" :value="userPass.loginid || ''" hidden readonly />
                            <div class="modal-body">
                                <div class="form-group">
                                    <div class="d-flex justify-content-between">
                                        <label for="login-password">New Password</label>
                                    </div>
                                    <div class="input-group input-group-merge form-password-toggle1">
                                        <input type="password" required autocomplete="new-password" class="form-control new_password" v-model="userPass.pass" name="new_password" tabindex="2" placeholder="********" aria-describedby="login-password" />
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

            <!-- Change Geo Level Modal -->
            <div class="modal modal-slide-in new-user-modal fade" id="geoLevelModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="change-geo-level modal-content pt-0" @submit.stop.prevent="onSubmitUpdateGeoLevel()">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideGeoModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel">Change <span class="text-primary">{{ geoLevelForm.currentUserLoginid }}</span> Geo Level</h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label">Geo Level</label>
                                <select @change="changeGeoLevel()" class="form-control" v-model="geoLevelForm.geoLevel">
                                    <option v-for="geo in geoLevelData" :value="geo.geo_level" :key="geo.geo_level">{{ geo.geo_level }}</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="geoLevelForm.geoLevel == 'state'">
                                <label class="form-label">State</label>
                                <select placeholder="Select Geo Level" class="form-control" v-model="geoLevelForm.geoLevelId">
                                    <option :value="sysDefaultData.stateid">{{ sysDefaultData.state }}</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="geoLevelForm.geoLevel == 'lga'">
                                <label class="form-label">LGA List</label>
                                <select class="form-control" v-model="geoLevelForm.geoLevelId">
                                    <option v-for="lga in lgaLevelData" :value="lga.lgaid" :key="lga.lgaid">{{ lga.lga }}</option>
                                </select>
                            </div>
                            <div v-if="geoLevelForm.geoLevel == 'cluster'">
                                <div class="form-group">
                                    <label class="form-label">LGA List</label>
                                    <select class="form-control" @change="getClusterLevel()" v-model="geoIndicator.cluster">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid" :key="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Cluster</label>
                                    <select class="form-control" v-model="geoLevelForm.geoLevelId">
                                        <option v-for="g in clusterLevelData" :value="g.clusterid" :key="g.clusterid">{{ g.cluster }}</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="geoLevelForm.geoLevel == 'ward'">
                                <div class="form-group">
                                    <label class="form-label">LGA List</label>
                                    <select class="form-control" @change="getWardLevel()" v-model="geoIndicator.lga">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid" :key="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Ward</label>
                                    <select class="form-control" v-model="geoLevelForm.geoLevelId">
                                        <option v-for="g in wardLevelData" :value="g.wardid" :key="g.wardid">{{ g.ward }}</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="geoLevelForm.geoLevel == 'dp'">
                                <div class="form-group">
                                    <label class="form-label">LGA List</label>
                                    <select class="form-control" @change="getWardLevel()" v-model="geoIndicator.lga">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid" :key="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Ward</label>
                                    <select class="form-control" @change="getDpLevel()" v-model="geoIndicator.ward">
                                        <option v-for="g in wardLevelData" :value="g.wardid" :key="g.wardid">{{ g.ward }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">DP List</label>
                                    <select class="form-control" v-model="geoLevelForm.geoLevelId">
                                        <option v-for="g in dpLevelData" :value="g.dpid" :key="g.dpid">{{ g.dp }}</option>
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

            <!-- Working Hour Extension Modal -->
            <div class="modal modal-slide-in new-user-modal fade" id="workHourModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="change-working-hour-form modal-content pt-0" @submit.stop.prevent="onSubmitAddWorkingHour()">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideWorkHourExtensionModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title text-primary" id="workHourModalLabel">
                                Extend <span class="badge badge-light-success">{{ (selectedID() || []).length }} Users</span> Work Hour
                            </h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label" for="extensionHour">Extension Hour</label>
                                <input type="number" class="form-control extensionHour" name="extensionHour" id="extensionHour" @keypress="numbersOnlyWithoutDot" placeholder="Extension Hour" v-model="workHourExtensionForm.extensionHour" />
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
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* user_details                                                         */
/* ------------------------------------------------------------------ */
const UserDetails = {
  setup() {
    const fmtUtils = useFormat();
    const userid = ref("");
    const userDetails = ref(true);
    const user_status = ref("");
    const bankListData = ref([]);
    const roleListData = ref([]);
    const permission = ref(
      typeof getPermission === "function"
        ? getPermission(typeof per !== "undefined" ? per : null, "users") || {
            permission_value: 0,
          }
        : { permission_value: 0 },
    );
    const userData = reactive({
      baseData: {},
      financeData: {},
      identityData: {},
      roleData: {},
    });

    const gotoPageHandler = (data) => {
      if (!data || data.page !== "detail") return;
      userDetails.value = true;
      userid.value = data.userid;
      user_status.value = data.user_status;
      roleListData.value = data.role || [];
      getUserDetails();
    };
    const goToList = () => {
      bus.emit("g-event-goto-page", { page: "list", userid: userid.value });
    };
    const discardUpdate = () => {
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to discard the changes? </p><br>Discarding the changes means you will lose all changes made",
        buttons: {
          delete: {
            text: "Discard Changes",
            btnClass: "btn btn-warning mr-1",
            action: () => {
              getUserDetails();
              userDetails.value = true;
              overlay.hide();
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: () => {
              overlay.hide();
            },
          },
        },
      });
    };
    const getUserDetails = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=005&e=" + userid.value)
        .then((response) => {
          var d = response.data || {};
          userData.baseData = (d.base && d.base[0]) || {};
          userData.financeData = (d.finance && d.finance[0]) || {};
          userData.identityData = (d.identity && d.identity[0]) || {};
          userData.roleData = (d.role && d.role[0]) || {};
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getBankLists = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen008")
        .then((response) => {
          bankListData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const updateUserProfile = () => {
      var updateFormData = {
        userid: userid.value,
        roleid: userData.baseData.roleid,
        first: userData.identityData.first,
        middle: userData.identityData.middle,
        last: userData.identityData.last,
        gender: userData.identityData.gender,
        email: userData.identityData.email,
        phone: userData.identityData.phone,
        bank_name: userData.financeData.bank_name,
        account_name: userData.financeData.account_name,
        account_no: userData.financeData.account_no,
        bank_code:
          userData.financeData.bank_code != ""
            ? userData.financeData.bank_code
            : "",
        bio_feature: "",
      };
      var u = common.DataService;
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to Update the User? </p><br>Updating the User profile means you are changing the user permissions and details",
        buttons: {
          delete: {
            text: "Update Details",
            btnClass: "btn btn-warning mr-1",
            action: () => {
              axios
                .post(u + "?qid=006", JSON.stringify(updateFormData))
                .then((response) => {
                  overlay.hide();
                  if (response.data.result_code == "200") {
                    bus.emit("g-event-update-user", {});
                    userDetails.value = true;
                    alert.Success(
                      "SUCCESS",
                      response.data.total + " User Updated",
                    );
                  } else {
                    alert.Error("ERROR", "User Details Update failed");
                  }
                })
                .catch((error) => {
                  overlay.hide();
                  alert.Error("ERROR", safeMessage(error));
                });
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: () => {
              overlay.hide();
            },
          },
        },
      });
    };
    const checkIfEmpty = (data) => {
      return data === null || data === "" || data === undefined ? "Nil" : data;
    };
    const userActivationDeactivation = (actionid) => {
      overlay.show();
      axios
        .post(common.DataService + "?qid=001", JSON.stringify([actionid]))
        .then((response) => {
          overlay.hide();
          if (response.data.result_code == "200") {
            bus.emit("g-event-update-user", {});
            user_status.value = String(user_status.value) === "1" ? 0 : 1;
            alert.Success("SUCCESS", "User De/Activation Successful");
          } else {
            alert.Error("ERROR", "User De/Activation failed");
          }
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const changeRole = (event) => {
      userData.baseData.role =
        event.target.options[event.target.options.selectedIndex].text;
    };
    const changeBank = (event) => {
      userData.financeData.bank_name =
        event.target.options[event.target.options.selectedIndex].text;
    };
    const downloadBadge = (id) => {
      overlay.show();
      window.open(common.BadgeService + "?qid=002&e=" + id, "_parent");
      overlay.hide();
    };
    const numbersOnlyWithoutDot = (evt) => {
      var e = evt || window.event;
      var charCode = e.which || e.keyCode;
      if (charCode > 31 && (charCode < 48 || charCode > 57)) e.preventDefault();
      return true;
    };

    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
      getBankLists();
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
    });

    return {
      userid,
      userDetails,
      user_status,
      bankListData,
      roleListData,
      permission,
      userData,
      gotoPageHandler,
      goToList,
      discardUpdate,
      getUserDetails,
      getBankLists,
      updateUserProfile,
      checkIfEmpty,
      userActivationDeactivation,
      changeRole,
      changeBank,
      downloadBadge,
      numbersOnlyWithoutDot,
      displayDate: fmtUtils.displayDate,
      capitalize: fmtUtils.capitalize,
      formatNumber: fmtUtils.formatNumber,
    };
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

            <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0 sidebar-sticky">
                <div class="card">
                    <div class="card-body">
                        <div class="user-avatar-section">
                            <div class="d-flex align-items-center flex-column">
                                <img class="img-fluid rounded mt-3 mb-2" :src="'/app-assets/images/avatar.png'" height="110" width="110" alt="User avatar">
                                <div class="user-info text-center">
                                    <h4 v-html="userData.baseData.loginid"></h4>
                                    <span class="badge bg-light-primary" v-html="userData.baseData.role"></span>
                                </div>
                            </div>
                        </div>
                        <div class="fw-bolder border-bottom font-small-2 pb-20 mb-1 mt-1 text-center">{{ userData.baseData.geo_string }}</div>
                        <div class="info-container">
                            <ul class="list-unstyled pl-2">
                                <li class="mb-75"><span class="fw-bolder me-25">Username:</span><span v-html="userData.baseData.username"></span></li>
                                <li class="mb-75"><span class="fw-bolder me-25">User Group:</span><span v-html="userData.baseData.user_group"></span></li>
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">Status:</span>
                                    <span class="badge" :class="user_status == 1 ? 'bg-light-success' : 'bg-light-danger'">{{ user_status == 1 ? 'Active' : 'Inactive' }}</span>
                                </li>
                            </ul>
                            <div class="justify-content-center pt-2 form-group">
                                <button class="btn btn-primary mb-1 form-control suspend-user waves-effect" @click="downloadBadge(userid)"><i class="feather icon-download"></i> Download Badge</button>
                                <button v-if="userDetails && permission.permission_value >= 2" @click="userDetails = false" class="btn btn-outline-primary mb-1 form-control suspend-user waves-effect"><i class="feather icon-edit-2"></i> Edit</button>
                                <button v-if="permission.permission_value >= 2" class="btn form-control suspend-user waves-effect" :class="user_status == 1 ? 'btn-danger' : 'btn-success'" @click="userActivationDeactivation(userid)">
                                    <i class="feather" :class="user_status == 1 ? 'icon-user-x' : 'icon-user-check'"></i> {{ user_status == 1 ? ' Deactivate' : ' Activate' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                <div v-if="userDetails">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Details</h4>
                            <button v-if="permission.permission_value >= 2" class="btn btn-primary btn-sm waves-effect waves-float waves-light" @click="userDetails = false">
                                <i class="feather icon-edit-2"></i> <span>Edit</span>
                            </button>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Firstname</label>{{ checkIfEmpty(userData.identityData.first) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Middle</label>{{ checkIfEmpty(userData.identityData.middle) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Lastname</label>{{ checkIfEmpty(userData.identityData.last) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Gender</label>{{ checkIfEmpty(userData.identityData.gender) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Phone No</label>{{ checkIfEmpty(userData.identityData.phone) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Email</label>{{ checkIfEmpty(userData.identityData.email) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Finance</h4></div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr><td colspan="2" class="user-detail-txt"><label class="d-block text-primary">Account Name</label>{{ checkIfEmpty(userData.financeData.account_name) }}</td></tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Account Number</label>{{ checkIfEmpty(userData.financeData.account_no) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Bank Name</label>{{ checkIfEmpty(userData.financeData.bank_name) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Role</h4></div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr><td colspan="2" class="user-detail-txt"><label class="d-block text-primary">Role</label>{{ checkIfEmpty(userData.baseData.role) }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" @submit.stop.prevent="updateUserProfile()" v-else>
                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Details</h4></div>
                        <div class="card-body row">
                            <div class="col-6"><div class="form-group"><label>First Name</label><input type="text" id="firstname" v-model="userData.identityData.first" class="form-control firstname" placeholder="First Name" name="firstname" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Middle Name</label><input type="text" id="middlename" v-model="userData.identityData.middle" class="form-control middlename" placeholder="Middle Name" name="middlename" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Lastname</label><input type="text" id="lastname" v-model="userData.identityData.last" class="form-control lastname" placeholder="Last Name" name="lastname" /></div></div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select v-model="userData.identityData.gender" class="form-control">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6"><div class="form-group"><label>Phone No</label><input type="text" id="phoneno" maxlength="11" v-model="userData.identityData.phone" @keypress="numbersOnlyWithoutDot" class="form-control phoneno" placeholder="Phone No" name="phoneno" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Email</label><input type="email" id="email" v-model="userData.identityData.email" class="form-control email" placeholder="Email" name="email" /></div></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Finance</h4></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12"><div class="form-group"><label>Account Name</label><input type="text" id="account_name" v-model="userData.financeData.account_name" class="form-control account_name" placeholder="Account Name" name="account_name" /></div></div>
                                <div class="col-6"><div class="form-group"><label>Account Number</label><input type="text" id="account_no" @keypress="numbersOnlyWithoutDot" maxlength="10" v-model="userData.financeData.account_no" class="form-control account_no" placeholder="Account Number" name="account_no" /></div></div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Bank Name</label>
                                        <select v-model="userData.financeData.bank_code" @change="changeBank($event)" class="form-control bank_code select2">
                                            <option v-for="b in bankListData" :key="b.bank_code" :value="b.bank_code">{{ b.bank_name }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Role</h4></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select :disabled="permission.permission_value < 2" v-model="userData.baseData.roleid" @change="changeRole($event)" class="form-control role select2">
                                            <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid">{{ r.role }}</option>
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
                            <button v-if="permission.permission_value >= 2" class="btn btn-primary form-control mt-2 waves-effect waves-float waves-light">Update Details</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* Mount                                                              */
/* ------------------------------------------------------------------ */
useApp({ template: `<div><page-body/></div>` })
  .component("page-body", PageBody)
  .component("user_list", UserList)
  .component("user_details", UserDetails)
  .mount("#app");
