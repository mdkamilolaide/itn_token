<?php

#
#   Create Bulk Users
#
if (getPermission($user_priviledge, 'users') >= 2) {
    $inputData = json_decode(file_get_contents('php://input'), true);
    $role_id = $inputData['roleid'];

    if (!empty($inputData['groupName']) && !empty($inputData['password']) && !empty($inputData['geoLevel']) && !empty($inputData['geoLevelId'])) {
        $usr = new Users\BulkUser($inputData['groupName'], $inputData['password'], $inputData['geoLevel'], $inputData['geoLevelId'], $role_id);
        $total = $usr->CreateBulkUser($inputData['totalUser']);
        if ($total) {
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "$total User(s) Created with " . $inputData['groupName'] . " as Group Name, " . $inputData['geoLevel'] . " as Geo Level: ", $result);
            #On User Creation
            echo json_encode(array(
                'result_code' => 201,
                'message' => 'success',
                'total' => $total
            ));
        } else {
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = $inputData['totalUser'] . " User(s) failed to be Created with " . $inputData['groupName'] . " as Group Name, " . $inputData['geoLevel'] . " as Geo Level: ", $result);
            #On user creation failed
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error',
                'total' => $total
            ));
        }
    } else {
        //Log User Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = " User(s) Creation failed due to wrong data input: ", $result);
        #If all data supplied are wrong
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'error'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
