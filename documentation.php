<?php
include("lib/config.php");
$server_claim .= 'api';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='<?php echo $config_pre_append_link . "app-assets/css/bootstrap.min.css"; ?>' rel='stylesheet' type='text/css' />
    <title>Ipolongo Solutions API DOcumentation</title>
</head>

<body style="padding: 15px;">

    <?php
    echo '<pre><h2 class="mt-2 pb-0 mb-0">       API Documentation</h2></pre>';
    echo '<pre> 
                    #   <b style="color: #FF5722">LOGIN USING USER ID AND PASSWORD</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   result_code - 400 : Error
                    #   <span style="color: #6A1B9A; margin-bottom:5px">User Login Sample using User ID and Password</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                            <tr>
                                <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <span style="color: #000">' . $server_claim . '?qid=010</span> (POST Request)</th>
                            </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>loginid</td>
                                <td>HYM00001</td>
                                <td>User Login ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>password</td>
                                <td>DEmo2021</td>
                                <td>The user password</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>device_id</td>
                                <td>OWS004</td>
                                <td>Device Serial No</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">LOGIN USING BADGE</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   result_code - 400 : Error
                    #   <span style="color: #6A1B9A">User Login Sample using badge</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <span style="color: #000">' . $server_claim . '?qid=011</span> (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>badge_data</td>
                                <td>JTV00002|79mzhz79-u4h9-8df8-a9o8-9vr3b0zkttxi</td>
                                <td>User badge data after scanning</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>device_serial</td>
                                <td>OWS004</td>
                                <td>Device Serial No</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">USER LISTS</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">Users Informations</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=012</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>userid</td>
                                <td>User ID generated</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>loginid</td>
                                <td>User Login ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>userid</td>
                                <td>User ID generated</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>roleid</td>
                                <td>User Role ID</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>role</td>
                                <td>User Role</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>first</td>
                                <td>User First Name</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>middle</td>
                                <td>User Middle Name</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>last</td>
                                <td>User Lastname</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>gender</td>
                                <td>User Gender (Male | Female)</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>email</td>
                                <td>User Email Address</td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>phone</td>
                                <td>User Phone No</td>
                            </tr>
                            <tr>
                                <td>12</td>
                                <td>bank_name</td>
                                <td>User Banker</td>
                            </tr>
                            <tr>
                                <td>13</td>
                                <td>bank_code</td>
                                <td>Bank Sort Code</td>
                            </tr>
                            <tr>
                                <td>14</td>
                                <td>account_name</td>
                                <td>User Bank Account Name</td>
                            </tr>
                            <tr>
                                <td>15</td>
                                <td>account_no</td>
                                <td>User Account No</td>
                            </tr>
                            <tr>
                                <td>16</td>
                                <td>bio_feature</td>
                                <td>User Fingerprint</td>
                            </tr>
                        </table>
                </pre>';
    echo '<pre>
                    #   <b style="color: #FF5722">Update User FCM Register</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Failed Reconcilation 
                    #   message - : success
                    #   <span style="color: #6A1B9A">Update User FCM Register (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=013</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b>Body data</b><div style="color:#7367f0">                                {
                                    "userid": 1,
                                    "device_serial ": "SN3126",
                                    "fcm_token": "SPAQ903MMu8849IO40409"
                                }</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>userid</td>
                                <td>User ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>device_serial</td>
                                <td>The device Serial No</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>fcm_token</td>
                                <td>Firbase SDK token generated</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">BANK LISTS</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">List of bank master data</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen001</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>bank_code</td>
                                <td>Bank Sort Code</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>bank_name</td>
                                <td>Name of the bank</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">USER ROLE LISTS</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">List of available user roles</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=007</span>  (GET Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>roleid</td>
                                <td>The user role ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>role</td>
                                <td>User Role Name</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">BULK USER UPDATE</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">List of available user roles</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=006</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>user_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "userid": "",
                                "roleid": "",
                                "first": "",
                                "middle": "",
                                "last": "",
                                "gender": "",
                                "email": "",
                                "phone": "",
                                "bank_name": "",
                                "account_name": "",
                                "account_no": "",
                                "bank_code": "",
                                "bio_feature": ""
                            }
                        ]</span>                      
                                </td>
                                <td>
                                An array of user supplied data
                                <span style="color: #f00; text-align: left;">
                            array(
                                array(\'userid\'=>\'4450\', \'roleid\'=>\'4\', \'first\'=>\'Bennet\', \'middle\'=>\'Solomon\', \'last\'=>\'Omale\', 
                                        \'gender\'=>\'Male\', \'email\'=>\'someone@live.com\', \'phone\'=>\'08099399393\', \'bank_name\'=>\'\', 
                                        \'account_name\'=>\'Bennet Solomon\', \'account_no\'=>\'002992929\', \'bank_code\'=>\'033\', \'bio_feature\'=>\'\'),

                                array(\'userid\'=>\'4451\', \'roleid\'=>\'4\', \'first\'=>\'Bennet\', \'middle\'=>\'Solomon\', \'last\'=>\'Omale\', 
                                \'gender\'=>\'Male\', \'email\'=>\'someone@live.com\', \'phone\'=>\'08099399393\', \'bank_name\'=>\'\', 
                                \'account_name\'=>\'Bennet Solomon\',\'account_no\'=>\'002992929\',\'bank_code\'=>\'035\',\'bio_feature\'=>\'\')
                            )
                                </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>roleid</td>
                                <td>The user role ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>role</td>
                                <td>User Role Name</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">UPDATE USER ROLE</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Update a single user role</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=008</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>role_id</td>
                                <td><span style="color: #f00; text-align: left;">The role ID</span></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>user_id</td>
                                <td><span style="color: #f00; text-align: left;">The user ID</span></td>
                                <td></td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>roleid</td>
                                <td>The user role ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>role</td>
                                <td>User Role Name</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">GET TRAINING LIST</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Training List (Without Priviledge)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=100</span>  (GET Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                {
                                    "geo_level": "state",
                                    "geo_level_id": "26"
                                } 
                                - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>trainingid</td>
                                <td>Traininng ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>title</td>
                                <td>Training Title</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>geo_location</td>
                                <td>Training Geo Level</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>location_id</td>
                                <td>Geo level ID</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>guid</td>
                                <td>User Guid</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>description</td>
                                <td>Training Descriptions</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>start_date</td>
                                <td>Training Start Date</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>end_date</td>
                                <td>Training End Date</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>participant_count</td>
                                <td>Total participant in a training</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">USER BANK VERIFICATION</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Verify User Bank Account Verification Details</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=009</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                {
                                    "user_id": 3,
                                    "long": "6.8654",
                                    "lat": "4.5563"
                                } 
                                - body data (User ID of the User you want to verify)
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 | 401</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Messages that comes if the verification process is or not executed</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>data</td>
                                <td>The Bank Verification result Data</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>result</td>
                                <td>success | none | failed</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>message</td>
                                <td>Verification data message</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>account_name</td>
                                <td>The resolved Account Name</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>account_number</td>
                                <td>The account Number verified</td>
                            </tr>
                        </table>
                </pre>';



    echo '<pre>
                    #   <b style="color: #FF5722">GET TRAINING SESSIONS LIST</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Training Session List (Without Priviledge)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=101</span>  (GET Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>training_id</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after selecting a training}</span></td>
                                <td>ID of training you want to get the sessions</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>sessionid</td>
                                <td>The training Session ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>trainingid</td>
                                <td>Training ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>title</td>
                                <td>Session name</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>guid</td>
                                <td>User GUID</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>guid</td>
                                <td>User Guid</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>session_date</td>
                                <td>Training Session Date</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>created</td>
                                <td>Session Created Date</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>updated</td>
                                <td>Session Updated Date</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">GET TRAINING PARTICIPANTS LIST</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Training Participant List (Without Priviledge)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=102</span>  (GET Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>training_id</td>
                                <td><span style="color: #f00; text-align: left;">{Gotten on selecting a training}</span></td>
                                <td>ID of training you want to get the Participants</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>geo_level</td>
                                <td><span style="color: #f00; text-align: left;">{Gotten When a user login}</span></td>
                                <td>e.g "state"</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>geo_level_id</td>
                                <td><span style="color: #f00; text-align: left;">{Gotten When a user login}</span></td>
                                <td>e.g 7</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>participant_id</td>
                                <td>The Participant ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>userid</td>
                                <td>User ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>loginid</td>
                                <td>The user Login ID</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>roleid</td>
                                <td>User Login ID</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>role</td>
                                <td>User Role</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>first</td>
                                <td>Firstname of the user</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>middle</td>
                                <td>Middle Name of Participant</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>last</td>
                                <td>Lastname of Participant</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>gender</td>
                                <td>Gender of Participant</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>email</td>
                                <td>Email of Participant</td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>phone</td>
                                <td>Phone no of Participant</td>
                            </tr>
                            <tr>
                                <td>12</td>
                                <td>bank_name</td>
                                <td>Bank name of Participant</td>
                            </tr>
                            <tr>
                                <td>13</td>
                                <td>bank_code</td>
                                <td>Bank Sort Code of the bank</td>
                            </tr>
                            <tr>
                                <td>14</td>
                                <td>account_name</td>
                                <td>Bank account name of Participant</td>
                            </tr>
                            <tr>
                                <td>15</td>
                                <td>account_no</td>
                                <td>Bank Account No of Participant</td>
                            </tr>
                            <tr>
                                <td>16</td>
                                <td>is_verified</td>
                                <td>1 or 0 if user account has been verify before</td>
                            </tr>
                            <tr>
                                <td>17</td>
                                <td>verification_status</td>
                                <td>success | failed | none </td>
                            </tr>
                            <tr>
                                <td>18</td>
                                <td>bio_feature</td>
                                <td>Fingerprint Captured String</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">BULK TRAINING ATTENDANCE UPDATE</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">List of available user roles</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=103</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>user_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "session_id": "",
                                "participant_id": "",
                                "at_type": "",
                                "bio_auth": "",
                                "collected": "",
                                "longitude": "",
                                "latitude": "",
                                "userid": "",
                                "app_version": ""
                            },
                        ]</span>                      
                                </td>
                                <td>
                            An array of user supplied data
                                <span style="color: #f00; text-align: left;">
                            array(
                                array(\'session_id\'=>1,\'participant_id\'=>12,\'at_type\'=>\'ClOCK-OUT\',\'bio_auth\'=>true,\'collected\'=>\'2022-03-16 16:00\',\'longitude\'=>\'8.0027\',\'latitude\'=>\'5.67822\',\'userid\'=>1,\'app_version\'=>\'14.0.5\'),
                                array(\'session_id\'=>1,\'participant_id\'=>13,\'at_type\'=>\'ClOCK-in\',\'bio_auth\'=>true,\'collected\'=>\'2022-03-16 08:00\',\'longitude\'=>\'8.0027\',\'latitude\'=>\'5.67822\',\'userid\'=>1,\'app_version\'=>\'14.0.5\'),
                                array(\'session_id\'=>1,\'participant_id\'=>14,\'at_type\'=>\'ClOCK-in\',\'bio_auth\'=>false,\'collected\'=>\'2022-03-16 08:34\',\'longitude\'=>\'8.0027\',\'latitude\'=>\'5.67822\',\'userid\'=>1,\'app_version\'=>\'14.0.5\'),
                                array(\'session_id\'=>1,\'participant_id\'=>15,\'at_type\'=>\'ClOCK-in\',\'bio_auth\'=>true,\'collected\'=>\'2022-03-16 08:46\',\'longitude\'=>\'8.0027\',\'latitude\'=>\'5.67822\',\'userid\'=>1,\'app_version\'=>\'14.0.5\'),
                                array(\'session_id\'=>1,\'participant_id\'=>16,\'at_type\'=>\'ClOCK-in\',\'bio_auth\'=>true,\'collected\'=>\'2022-03-16 08:57\',\'longitude\'=>\'8.0027\',\'latitude\'=>\'5.67822\',\'userid\'=>1,\'app_version\'=>\'14.0.5\')
                            )
                                </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Erro Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>total</td>
                                <td>Total attendance data updated</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">GET HH Mobilizer List and Net Balances per ward (Contain Duplicates )</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">HH Mobilizer List (using wardid - in the API body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=201</span>  (GET Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"wardid": 1}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>balance</td>
                                <td>Total net balance ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>userid</td>
                                <td>Mobilizer User ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>loginid</td>
                                <td>Mobilizer Login ID</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>fullname</td>
                                <td>HHM Fullname</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>geo_level</td>
                                <td>HHTM Geo Level</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>geo_level_id</td>
                                <td>HHM Geo Level ID</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>title</td>
                                <td>Ward Name</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>geo_string</td>
                                <td>Geo String</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>pick</td>
                                <td>For check box Selection (True/False)</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>device_serial</td>
                                <td>The Device ID in which the Netcard has been downlaoded (Null if it hans\'t been downloaded)</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Bulk e-Netcard Allocation to HHM</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Bulk e-Netcard Allocation to HHM (API in the header)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=202</span>  (POST Request in the header)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>allocation_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "total": 10,
                                "wardid": 1,
                                "mobilizerid": 3,
                                "userid": 2
                            },
                            {
                                "total": 10,
                                "wardid": 1,
                                "mobilizerid": 4,
                                "userid": 2
                            }
                        ] </span></td>
                                <td>
                                <span style="color: #f00; text-align: left;">
                                    [array("total"=>10, "wardid"=>1, "mobilizerid"=>3, "userid"=>2),
                                    array("total"=>10, "wardid"=>1, "mobilizerid"=>4, "userid"=>2),
                                    array("total"=>10, "wardid"=>1, "mobilizerid"=>5, "userid"=>2)];
                                </span>
                                </td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Ward Landing Page e-Netcard Balances</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Ward balances, received and disbursed balances (using wardid - in the API body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=203</span>  (GET Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"wardid": 1}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>balance</td>
                                <td>Total net balance</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>received</td>
                                <td>Total Received Netcard</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>disbursed</td>
                                <td>Total Disbursed Netcard</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">e-Netcard Allocation Transaction List</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Allocation Transaction List - Forward transaction (using wardid - in the API body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=204</span>  (GET Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"wardid": 1}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>atid</td>
                                <td>Total net balance</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>performed_by</td>
                                <td>Name of the user that performed the transaction</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>total</td>
                                <td>Total e-Netcard Transfered</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>a_type</td>
                                <td>Transfer Type</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>hhm_userid</td>
                                <td>HHTM User ID that the transfer was done to</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>hhm</td>
                                <td>The HHM Name</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">e-Netcard Reverse Transaction List</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Allocation Transaction List - Reverse transaction (using wardid - in the API body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=205</span>  (GET Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"wardid": 1}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>created</td>
                                <td>Date of ordering</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>fulfilled_date</td>
                                <td>Order Fulfilled date</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>mobilizer</td>
                                <td>HH Mobilizer Name from which e-Netcard was removed</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>mobilizer_loginid</td>
                                <td>HHM Login Id</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>mobilizer_userid</td>
                                <td>HHM User ID that the e-Neetcard was removed from</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>orderid</td>
                                <td>The Order ID</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>pick</td>
                                <td>For Checkbox selection on the web</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>requester</td>
                                <td>Name of Admin that performed the reverse transaction</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>requester_id</td>
                                <td>Admin User ID</td>
                            </tr>

                            <tr>
                                <td>10</td>
                                <td>requester_loginid</td>
                                <td>Login Id of Admin that performed the reverse transaction</td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>status</td>
                                <td>Transaction Status</td>
                            </tr>
                            <tr>
                                <td>12</td>
                                <td>total_fulfilment</td>
                                <td>Total e-Netcard that was successfully removed/ reversed</td>
                            </tr>
                            <tr>
                                <td>13</td>
                                <td>total_order</td>
                                <td>Requested e-Netcard to be removed</td>
                            </tr>
                        </table>
                </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">Online e-Netcard Reverse Transaction List</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Allocation Transaction List - Reverse transaction (using wardid - in the API body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=206</span>  (GET Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"wardid": 1}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>created</td>
                                <td>Date of ordering</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>fulfilled_date</td>
                                <td>Order Fulfilled date</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>mobilizer</td>
                                <td>HH Mobilizer Name from which e-Netcard was removed</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>mobilizer_loginid</td>
                                <td>HHM Login Id</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>mobilizer_userid</td>
                                <td>HHM User ID that the e-Neetcard was removed from</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>orderid</td>
                                <td>The Order ID</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>pick</td>
                                <td>For Checkbox selection on the web</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>requester</td>
                                <td>Name of Admin that performed the reverse transaction</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>requester_id</td>
                                <td>Admin User ID</td>
                            </tr>

                            <tr>
                                <td>10</td>
                                <td>requester_loginid</td>
                                <td>Login Id of Admin that performed the reverse transaction</td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>status</td>
                                <td>Transaction Status</td>
                            </tr>
                            <tr>
                                <td>12</td>
                                <td>total_fulfilment</td>
                                <td>Total e-Netcard that was successfully removed/ reversed</td>
                            </tr>
                            <tr>
                                <td>13</td>
                                <td>total_order</td>
                                <td>Requested e-Netcard to be removed</td>
                            </tr>
                        </table>
                </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">Netcard Allocation reverse order Form</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Netcard Allocation reverse order (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=207</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>allocation_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        {
                            "total": "10",
                            "mobilizerid" : 3,
                            "wardid" : 1028,
                            "userid" : 3
                            "device_serial" : "JMA003"
                        } </span></td>
                                <td><span style="color: #f00; text-align: left;"></span></td>
                            </tr>
                        </table>
                </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">GET Combined HH Mobilizer List and Net Balances per ward</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">HH Mobilizer List (using wardid - in the API body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=208</span>  (GET Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"wardid": 1}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>balance</td>
                                <td>Total net balance ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>userid</td>
                                <td>Mobilizer User ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>loginid</td>
                                <td>Mobilizer Login ID</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>fullname</td>
                                <td>HHM Fullname</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>geo_level</td>
                                <td>HHTM Geo Level</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>geo_level_id</td>
                                <td>HHM Geo Level ID</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>title</td>
                                <td>Ward Name</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>geo_string</td>
                                <td>Geo String</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>pick</td>
                                <td>For check box Selection (True/False)</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>device_serial</td>
                                <td>The Device ID in which the Netcard has been downlaoded (Null if it hans\'t been downloaded)</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Netcard Online Reverse Form</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Netcard Allocation reverse order (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=209</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>allocation_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        {
                            "total": "10",
                            "mobilizerid" : 3
                        } </span></td>
                                <td><span style="color: #f00; text-align: left;"></span></td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">GET Combined HH Mobilizer List and Net Balances per ward Version 2</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">HH Mobilizer List (using wardid - in the API body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=210</span>  (GET Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"wardid": 1}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr> 
                            <tr>
                                <td>1</td>
                                <td>userid</td>
                                <td>Mobilizer User ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>loginid</td>
                                <td>Mobilizer Login ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>fullname</td>
                                <td>HHM Fullname</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>online</td>
                                <td>Online Balance</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>pending</td>
                                <td>Pending Balance</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>wallet</td>
                                <td>Wallet Balance</td>
                            </tr>
                        </table>
                </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">Change user password using login ID</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Change user password using login ID (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen002</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>allocation_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        {
                            "loginid": "",
                            "old" : "DEmo2021",
                            "new" : "DEmo2022"
                        } </span></td>
                                <td><span style="color: #f00; text-align: left;">
                            <b style="color: #7367f0">loginid</b> The user Login ID
                            <b style="color: #7367f0">old</b>: User Old Password
                            <b style="color: #7367f0">new</b>: New User Password</span></td>
                            </tr>
                        </table>
                </pre>';

    echo '<hr style="height: 10px; background-color: #7367f0">';

    echo '<pre>
                    #   <b style="color: #FF5722">Get Location Category List</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A"> Get location category list (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen003</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>

                    #   <b style="color: #FF5722">Get Community list Using Ward ID</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get Community list Using Ward ID (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=300a</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"wardid": 1007}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>dpid</td>
                                <td>Unique ID of DP in the selected ward</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>wardid</td>
                                <td>The ward ID of the DP</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dp</td>
                                <td>The DP Name</td>
                            </tr>
                        </table>

                </pre>';


    echo '<pre>

                    #   <b style="color: #FF5722">Get Community/Settlement list Using DP ID</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get DP list Using Ward ID (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=300b</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"dpid": 2027}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>dpid</td>
                                <td>Unique ID of DP in the selected ward</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>wardid</td>
                                <td>The ward ID of the DP</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dp</td>
                                <td>The DP Name</td>
                            </tr>
                        </table>

                </pre>';

    echo '<pre>

                    #   <b style="color: #FF5722">Get DP list Using Ward ID</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get DP list Using Ward ID (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=301</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"wardid": 1}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>dpid</td>
                                <td>Unique ID of DP in the selected ward</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>wardid</td>
                                <td>The ward ID of the DP</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dp</td>
                                <td>The DP Name</td>
                            </tr>
                        </table>

                </pre>';

    echo '<pre>

                    #   <b style="color: #FF5722">Download e-netcard</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Download e-netcard (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=302</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{
                                    "mobilizerid": 5,
                                    "device_serial": ISJ001
                                }</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>ncid</td>
                                <td>Netcard ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>uuid</td>
                                <td>Netcard GUID</td>
                            </tr>
                        </table>

                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Check for Pending Reverse Order</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Check for Pending Reverse Order (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=303</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{
                                    "mobilizerid": 5,
                                    "device_serial": ISJ001
                                }</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>orderid</td>
                                <td>The reverse order ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>total_order</td>
                                <td>To Netcard to Reverse</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>created</td>
                                <td>The date the reverse order was placed</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Generate e-Token List</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Generate e-Token List (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=304</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table> 
                                <b>Body data</b><div style="color:#7367f0">
                                {
                                    "device_id": "KM00299292", 
                                    "total": 10
                                }</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>tokenid</td>
                                <td>The Token ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>batchid</td>
                                <td>Generated Token Batch ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>batch_no</td>
                                <td>Generated Token Batch No</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>uuid</td>
                                <td>The Token GUID</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>serial_no</td>
                                <td>The Token Serial No</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Bulk Posting mobilization data</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Bulk Posting mobilization data (API in the header)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=305</span>  (POST Request in the header)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>mobilization_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "dp_id": 1,
                                "comid": 4001,
                                "hm_id": 5,
                                "co_hm_id": 13,
                                "hoh_first": "Kanzambili",
                                "hoh_last": "Samuel",
                                "hoh_phone": "08023456789",
                                "hoh_gender": "Male",
                                "family_size": 4,
                                <span style="color: #12c412">"hod_mother": "Omowumi Salewa",
                                "sleeping_space": "12",
                                "adult_female": "4",
                                "adult_male": "4",
                                "children": "4",</span>
                                "allocated_net": 2,
                                "location_description": "Household",
                                "longitude": "5.67890",
                                "latitude": "7.2339038",
                                "eolin_have_old_net": "1",
                                "eolin_total_old_net": "3",
                                "netcards": "h24h55kb-id4n-f5nf-9rgm-z3u9f1r663ow,q7e9idm1-3ggr-diwv-idfa-zmpemocb3lob",
                                "etoken_id": 41,
                                "etoken_serial": "WO00041",
                                "etoken_pin": "12345",
                                "collected_date": "2022-04-20",
                                "device_serial": "ISJ001", 
                                "app_version": "25.10"
                            }
                        ] </span></td>
                                <td>
                        <span style="color: #f00; text-align: left;">
                    [array("dp_id"=>1, "comid"=>4001, "hm_id"=>5,"co_hm_id"=>13,"hoh_first"=>"Kanzambili","hoh_last"=>"Samuel","hoh_phone"=>"08023456789",
                    "hoh_gender"=>"Male","family_size"=>4, <span style="background-color: #6cde6c">"hod_mother"=>"Omowumi Salewa","sleeping_space"=>"12",
                    "adult_female"=>"4","adult_male"=>"4","children"=>"4",</span> "allocated_net"=>2,"location_description"=>"Household",
                    "longitude"=>"5.67890","latitude"=>"7.2339038", "eolin_have_old_net"=>"1","eolin_total_old_net"=>"3",
                    "netcards"=>"h24h55kb-id4n-f5nf-9rgm-z3u9f1r663ow,q7e9idm1-3ggr-diwv-idfa-zmpemocb3lob",
                    "etoken_id"=>"41","etoken_serial"=>"WO00041","etoken_pin"=>"12345","collected_date"=>"2022-04-20")];
                        </span>
                        <i>Kindly note that hoh - means "Head of Household"</i>
                                </td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Get HH Mobilizer balances</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get HH Mobilizer balances (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=306</span>  (POST Request in the header)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b>Body data</b><div style="color:#7367f0">                        {
                                    "mobilizerid": 1
                                }</div>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Netcard Allocation reverse order fulfilment</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Netcard Allocation reverse order fulfilment (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=307</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>allocation_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        {
                            "mobilizerid" : 3,
                            "wardid" : 1,
                            "userid" : 3,
                            "orderid" : 2,
                            "netcards": [
                                "7khh4t0s-ljhz-a7zm-o3lo-wzo3px7tfsmc",
                                "fiofptns-ts73-ierg-085m-shas60b5eq5j",
                                "kot0af87-v3t9-4alu-f7fc-j5r5xwdyym0b"
                            ]
                        } </span></td>
                                <td><span style="color: #f00; text-align: left;"></span></td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Push Unused e-NetCard back to the Ward</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Push Unused e-NetCard back to the Ward (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=308</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>allocation_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        {
                            "mobilizerid" : 3,     //The Mobilizer ID of the owner off the netcard or the one that downloaded it
                            "device_serial" : "AJ0994",  //Serial No of the Device that is pushing
                            "netcards": [
                                "7khh4t0s-ljhz-a7zm-o3lo-wzo3px7tfsmc",
                                "fiofptns-ts73-ierg-085m-shas60b5eq5j",
                                "kot0af87-v3t9-4alu-f7fc-j5r5xwdyym0b"
                            ]
                        } </span></td>
                                <td><span style="color: #f00; text-align: left;"></span></td>
                            </tr>
                        </table>
                </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">Mobilizer Confirm e-NetCardDownload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Mobilizer eNetcard Dwonload Confirmation (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=309</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Downloaded eNetcard Details data</td>
                                <td><span style="color: #f00; text-align: left;">
                        {
                            "mobilizerid" : 3,     //The Mobilizer ID of the owner off the netcard or the one that downloaded it
                            "device_serial" : "AJ0994",  //Serial No of the Device that is pushing
                            "download_id": 1, //The ID of the download record in the database
                        } </span></td>
                                <td><span style="color: #f00; text-align: left;"></span></td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>status</td>
                                <td>success || destroyed || error</td>
                            </tr>
                        </table>
                </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">Get receipt header</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get receipt header (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen004</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>logo</td>
                                <td>Logo in in base 64 to be converted on the mobile to bitmap</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>receipt_header</td>
                                <td>Receipt Header</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Download Mobilization Data</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Download Mobilization Data (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=401</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b>Body data</b><div style="color:#7367f0">                                {
                                    "dpid": 1
                                }</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>hhid</td>
                                <td>Household ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>hoh_first</td>
                                <td>Household First Name</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>hoh_last</td>
                                <td>Household Last Name</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>lgaid</td>
                                <td>DP LGA ID</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>hoh_phone</td>
                                <td>Household Phone No</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>hoh_gender</td>
                                <td>Household Gender</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>family_size</td>
                                <td>Family Size</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>allocated_net</td>
                                <td>Allocated Netcard</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>location_description</td>
                                <td>Category</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>netcards</td>
                                <td>Alloted Netcards</td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>etoken_id</td>
                                <td>eToken Id</td>
                            </tr>
                            <tr>
                                <td>12</td>
                                <td>etoken_serial</td>
                                <td>eToken Serial</td>
                            </tr>
                            <tr>
                                <td>13</td>
                                <td>etoken_pin</td>
                                <td>eToken Pin</td>
                            </tr>
                            <tr>
                                <td>14</td>
                                <td>collected_date</td>
                                <td>Netcard Collected date</td>
                            </tr>
                            <tr>
                                <td>15</td>
                                <td>mobilizer_fullname</td>
                                <td>HH Mobilizer Fullname</td>
                            </tr>
                            <tr>
                                <td>16</td>
                                <td>mobilizer_loginid</td>
                                <td>HH Mobilizer ID</td>
                            </tr>
                            <tr>
                                <td>17</td>
                                <td>etoken_uuid</td>
                                <td>eToken GUID</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Distribution  Bulk distribution data upload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Distribution  Bulk distribution data upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=402</span>  (JWT POST Request in the header)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>distribution_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "dp_id": 1,
                                "mobilization_id": 1,
                                "recorder_id": 21,
                                "distributor_id": 25,
                                "collected_nets": 4,
                                "is_gs_net": 1,
                                "gs_net_serial": "992019291292012920129928,2881921029912021022,192992192912928122,188281929928182912",
                                "collected_date": "2022-04-22",
                                "etoken_id": 140,
                                "etoken_serial": AJ140, 
                                "longitude": 4.23, 
                                "latitude": 2.34, 
                                "device_serial": BG4023, 
                                "app_version": "23.2",
                                "eolin_bring_old_net": "1",
                                "eolin_total_old_net": "3",
                            }
                        ] </span></td>
                                <td>
                        <span style="color: #f00; text-align: left;">
                    "dp_id"=>1,"mobilization_id"=>1,"recorder_id"=>21,"distributor_id"=>25,"collected_nets"=>4,"is_gs_net"=>1,
                    "gs_net_serial"=>"992019291292012920129928,2881921029912021022,192992192912928122,188281929928182912",
                    "collected_date"=>"2022-04-22","etoken_id"=>140, "etoken_serial"=>"AJ140", "longitude"=>4.23, "latitude"=>2.34, 
                    "device_serial"=>"BG4023", "app_version"=>"23.2", "eolin_bring_old_net"=>1, "eolin_total_old_net"=>3;
                        </span>
                        <i>Kindly note that hoh - means "Head of Household"</i>
                                </td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Bulk distribution data upload with e-token ID returned</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Failed
                    #   message - : success
                    #   <span style="color: #6A1B9A">Distribution  Bulk distribution data upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=402a</span>  (JWT POST Request in the header)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>distribution_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "dp_id": 1,
                                "mobilization_id": 1,
                                "recorder_id": 21,
                                "distributor_id": 25,
                                "collected_nets": 4,
                                "is_gs_net": 1,
                                "gs_net_serial": "992019291292012920129928,2881921029912021022,192992192912928122,188281929928182912",
                                "collected_date": "2022-04-22",
                                "etoken_id": 140,
                                "etoken_serial": AJ140, 
                                "longitude": 4.23, 
                                "latitude": 2.34, 
                                "device_serial": BG4023, 
                                "app_version": "23.2",
                                "eolin_bring_old_net": "1",
                                "eolin_total_old_net": "3",
                            }
                        ] </span></td>
                                <td>
                        <span style="color: #f00; text-align: left;">
                    "dp_id"=>1,"mobilization_id"=>1,"recorder_id"=>21,"distributor_id"=>25,"collected_nets"=>4,"is_gs_net"=>1,
                    "gs_net_serial"=>"992019291292012920129928,2881921029912021022,192992192912928122,188281929928182912",
                    "collected_date"=>"2022-04-22","etoken_id"=>40, "etoken_serial"=>"AJ140", "longitude"=>4.23, "latitude"=>2.34, 
                    "device_serial"=>"BG4023", "app_version"=>"23.2", "eolin_bring_old_net"=>1, "eolin_total_old_net"=>3;
                        </span>
                        <i>Kindly note that hoh - means "Head of Household"</i>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 or 400 or 401</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>dataset</td>
                                <td>array of e-token ID that successfully updated or an empty array if none was successful</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>message</td>
                                <td>Description of what happen</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>total</td>
                                <td>Total Number of e-Token that was successfully updated</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Traceability Search</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Traceability Search (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=600</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table> 
                                <b>Body data</b><div style="color:#7367f0">
                                {
                                    "gtin": "8906126051976", // The GTIN you are searching for
                                    "sgtin": "21HRS93J28HMC" //GS1 Gtin Serial
                                }</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th colspan="2">Data Result</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td colspan="2">
                                
        "data": {
            "manufacturer": [
            {
                "itemid": "1",
                "brand_name": "VEERALIN",
                "product_description": "LLIN,Individual Packing without Accessories",
                "manufacturer_name": "VEERALIN",
                "gtin": "8906126051976",
                "created": "2022-11-18 09:20:50"
            }
            ],
            "logistic": [
            {
                "gtin": "8906126051976",
                "sgtin": "21HRS93J28HMC",
                "batch": "VN-2203-W-044",
                "collected_nets": "3",
                "collected_date": "2023-03-26 15:26:23",
                "state_warehouse": "Cross River Warehouse",
                "hoh_first": "Saidat",
                "hoh_last": "Karimi",
                "hoh_phone": "08075469213",
                "family_size": "5",
                "location_description": "Household",
                "longitude": "7.221667",
                "Latitude": "8.874689",
                "etoken_serial": "ZP02410",
                "geo_string": "NASARAWA > Akwanga > Agyaga > PHC RINZE"
            }
            ]
        }
                                </td>
                            </tr>
                        </table>
                </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">Specific User working hours</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Specific User working hours (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen005</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table> 
                                <b>Body data</b><div style="color:#7367f0">
                                {
                                    "userid": 1
                                }</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>start_time</td>
                                <td>Time that Mobilization or Distribution can occur</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>added_hours</td>
                                <td>Extra added time or extension</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>end_time</td>
                                <td>Working hour time</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>

                    #   <b style="color: #FF5722">Get DP list Using GUID ID</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get DP list Using GUID ID (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=403</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"guid": "up0ddwj9-wu8y-vg3q-ey4g-nrv9igokyxxh"}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>id</td>
                                <td>ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>guid</td>
                                <td>The DP GUID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>stateid</td>
                                <td>The State ID</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>lgaid</td>
                                <td>The LGA ID</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>wardid</td>
                                <td>The Ward ID</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>stateid</td>
                                <td>The State ID</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>dpid</td>
                                <td>The DP ID</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>geo_level_id</td>
                                <td>The DP Geo Level ID</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>geo_level</td>
                                <td>The DP Level</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>geo_value</td>
                                <td>The DP Geo Value</td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>geo_title</td>
                                <td>The DP Title or Name</td>
                            </tr>
                            <tr>
                                <td>12</td>
                                <td>geo_string</td>
                                <td>The DP Geo Location</td>
                            </tr>
                            <tr>
                                <td>13</td>
                                <td>geo_title</td>
                                <td>The DP Title or Name</td>
                            </tr>
                            <tr>
                                <td>14</td>
                                <td>ward</td>
                                <td>The DP Ward Name</td>
                            </tr>
                            <tr>
                                <td>15</td>
                                <td>lga</td>
                                <td>The DP LGA Name</td>
                            </tr>
                            <tr>
                                <td>16</td>
                                <td>state</td>
                                <td>The DP State Name</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre> 
                        #   <b style="color: #FF5722">Device Registration</b>
                        #   Result Code Documentation (result_code)
                        #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                        #   result_code - 400 : Error
                        #   <span style="color: #6A1B9A; margin-bottom:5px">Device Registration Sample using Device Name, Device ID and Device Type via header</span>
                            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                                <tr>
                                    <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <span style="color: #000">' . $server_claim . '?qid=501</span> (POST Request)</th>
                                </tr>
                                <tr class="table-primary">
                                    <th width="40px">#</th>
                                    <th>KEY</th>
                                    <th>VALUE</th>
                                    <th>DESCRIPTION</th>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>device_name</td>
                                    <td>TCAN T2 POS Andrid 6</td>
                                    <td>Device Name</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>device_id</td>
                                    <td>KSTC-0039-LOTT</td>
                                    <td>Device ID</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>device_type</td>
                                    <td>POS</td>
                                    <td>Device Type</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>long</td>
                                    <td>7.45</td>
                                    <td>Longitude</td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td>lat</td>
                                    <td>8.435</td>
                                    <td>Latitude</td>
                                </tr>
                            </table>
                    </pre>';

    echo '<pre> 
                        #   <b style="color: #FF5722">Check if Device is Registered</b>
                        #   Result Code Documentation (result_code)
                        #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                        #   result_code - 400 : Error
                        #   <span style="color: #6A1B9A; margin-bottom:5px">Device Registration Sample using Device Name, Device ID and Device Type via body</span>

                            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                                <tr>
                                    <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <span style="color: #000">' . $server_claim . '?qid=503</span> (POST Request)</th>
                                </tr>
                            </table>
                                <b style="color:#7367f0">{"device_id": "KSTC-0039-LOTT"}</b> - body data

                    </pre>';
    echo '<hr style="height: 10px; background-color: #7367f0">';
    echo '<pre>
                    <h1 style="color: #7367f0"> e-NETCARD MOVEMENT API</h1>

                    #   <b style="color: #FF5722"> Get LGA e-Netcard Movement Dashboard Balances and 5 Top Movement Histories</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">LGA Movement Dashboard and Top 5 Movement Histories - get dashboard balances (using lgaid - in the API body)</span>

                    #   <i style="color: #FF5722">JWT must be passed via header</i>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=700</span>  (GET Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"lgaid": 91}</b> - body data

                                <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                                <tr>
                                    <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                                </tr>
                                    <tr class="table-primary">
                                        <th width="40px">#</th>
                                        <th colspan="2">Data Result</th>
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td colspan="2">    
        {
            "result_code": 200,
            "dataset": "LGA Movement Dashboard balances and TOp 5 Histories",
            "message": "success",
            "data": {
                "lga_balances": [
                    {
                        "balance": "30",
                        "received": "21090",
                        "disbursed": "21060"
                    }
                ],
                "lga_movement_top_history": [
                    {
                        "mtid": "60",
                        "wardid": "1012",
                        "ward": "LIM/KUNDAK",
                        "total": "1000",
                        "move_type": "forward",
                        "destination_level": "ward",
                        "created": "2023-11-03 14:17:01"
                    },
                    {
                        "mtid": "58",
                        "wardid": "1012",
                        "ward": "LIM/KUNDAK",
                        "total": "1000",
                        "move_type": "forward",
                        "destination_level": "ward",
                        "created": "2023-11-03 14:16:10"
                    }
                ]
            }
        }
                                        </td>
                                    </tr>
                                </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Result Discription: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>lga_balances</td>
                                <td>This is an <b>array list</b> containing all LGA balances distributions</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>balance</td>
                                <td>Total e-Netcard balance in an LGA</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>received</td>
                                <td>Total e-Netcard Received in an LGA</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>disbursed</td>
                                <td>Total Distributed e-Netcard to the Wards</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>lga_movement_top_history</td>
                                <td>This is an <b>array list</b> consisting of the top 5 movement histories</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>mtid</td>
                                <td>Movement ID</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>wardid</td>
                                <td>Ward ID</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>ward</td>
                                <td>The name of the Ward</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>total</td>
                                <td>The total moved</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>move_type</td>
                                <td>The type of movement which can be forward or reverse</td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>destination_level</td>
                                <td>Movement destination (WARD or LGA)</td>
                            </tr>
                            <tr>
                                <td>12</td>
                                <td>created</td>
                                <td>Date of transaction</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                #   <b style="color: #FF5722">Get LGA Movement Histories Lists</b>
                #   Result Code Documentation (result_code)
                #   result_code - 200 : Success
                #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                #               - 400 : Error
                #   message - : success
                #   <span style="color: #6A1B9A">LGA Movement Histories - get list of Movement Histories (using lgaid - in the API body)</span>

                #   <i style="color: #FF5722">JWT must be passed via header</i>
                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=701</span>  (GET Request in the body)</th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th>KEY</th>
                            <th>VALUE</th>
                            <th>DESCRIPTION</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>jwt</td>
                            <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                            <td>Generated token after successful login, to be passed via header</td>
                        </tr>
                    </table>
                            <b style="color:#7367f0">{"lgaid": 91}</b> - body data

                            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                            <tr>
                                <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                            </tr>
                                <tr class="table-primary">
                                    <th width="40px">#</th>
                                    <th colspan="2">Data Result</th>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td colspan="2">    
        {
            "result_code": 200,
            "dataset": "LGA Movement mobile app dashboard balances",
            "message": "success",
            "data": [
                {
                    "mtid": "60",
                    "wardid": "1012",
                    "ward": "LIM/KUNDAK",
                    "total": "1000",
                    "move_type": "forward",
                    "destination_level": "ward",
                    "created": "2023-11-03 14:17:01"
                },
                {
                    "mtid": "58",
                    "wardid": "1012",
                    "ward": "LIM/KUNDAK",
                    "total": "1000",
                    "move_type": "forward",
                    "destination_level": "ward",
                    "created": "2023-11-03 14:16:10"
                }
            ]
        }
                                    </td>
                                </tr>
                            </table>
                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;">Result Discription: </b></th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th>KEY</th>
                            <th>DESCRIPTION</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>mtid</td>
                            <td>Movement ID</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>wardid</td>
                            <td>Ward ID</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>ward</td>
                            <td>The name of the Ward</td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>total</td>
                            <td>The total moved</td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>move_type</td>
                            <td>The type of movement which can be forward or reverse</td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>destination_level</td>
                            <td>Movement destination (WARD or LGA)</td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>created</td>
                            <td>Date of transaction</td>
                        </tr>
                    </table>
            </pre>';

    echo '<pre>
            #   <b style="color: #FF5722">Get Ward List and e-Netcard Balances</b>
            #   Result Code Documentation (result_code)
            #   result_code - 200 : Success
            #   result_code - 401 : Error (Unauthorized User/Unauthorized)
            #               - 400 : Error
            #   message - : success
            #   <span style="color: #6A1B9A">Get Ward List and e-Netcard Balances - get list of wards and their balances (using lgaid - in the API body)</span>

            #   <i style="color: #FF5722">JWT must be passed via header</i>
                <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                <tr>
                    <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=702</span>  (GET Request in the body)</th>
                </tr>
                    <tr class="table-primary">
                        <th width="40px">#</th>
                        <th>KEY</th>
                        <th>VALUE</th>
                        <th>DESCRIPTION</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>jwt</td>
                        <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                        <td>Generated token after successful login, to be passed via header</td>
                    </tr>
                </table>
                        <b style="color:#7367f0">{"lgaid": 91}</b> - body data

                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th colspan="2">Data Result</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td colspan="2">    
        {
            "result_code": 200,
            "dataset": "Ward List and their e-Netcard balances",
            "message": "success",
            "data": [
                {
                    "wardid": "1001",
                    "ward": "ALKALERI EAST",
                    "balance": "0"
                },
                {
                    "wardid": "1002",
                    "ward": "ALKALERI WEST",
                    "balance": "0"
                }
            ]
        }
                                </td>
                            </tr>
                        </table>
                <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                <tr>
                    <th colspan="4"><b style="color: #f00; text-align: left;">Result Discription: </b></th>
                </tr>
                    <tr class="table-primary">
                        <th width="40px">#</th>
                        <th>KEY</th>
                        <th>DESCRIPTION</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>wardid</td>
                        <td>Ward ID</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>ward</td>
                        <td>The name of the Ward</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>balance</td>
                        <td>The total e-Netcard Balances at the ward</td>
                    </tr>
                </table>
        </pre>';

    echo '<pre>
        #   <b style="color: #FF5722">Get mobilizers balances per Ward Level</b>
        #   Result Code Documentation (result_code)
        #   result_code - 200 : Success
        #   result_code - 401 : Error (Unauthorized User/Unauthorized)
        #               - 400 : Failed
        #   message - : success
        #   <span style="color: #6A1B9A">Get mobilizers balances per Ward Level - get list of household mobilizers balances per ward (using lgaid - in the API body)</span>

        #   <i style="color: #FF5722">JWT must be passed via header</i>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=703</span>  (GET Request in the body)</th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>VALUE</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>jwt</td>
                    <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                    <td>Generated token after successful login, to be passed via header</td>
                </tr>
            </table>
                    <b style="color:#7367f0">{"lgaid": 91}</b> - body data

                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th colspan="2">Data Result</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td colspan="2">    
        {
            "result_code": 200,
            "dataset": "Get mobilizers balances per Ward Level",
            "message": "success",
            "data": [
                {
                    "wardid": "1001",
                    "ward": "ALKALERI EAST",
                    "online": "543",
                    "wallet": "566"
                },
                {
                    "wardid": "1002",
                    "ward": "ALKALERI WEST",
                    "online": "43",
                    "wallet": "145"
                }
            ]
        }
                            </td>
                        </tr>
                    </table>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;">Result Discription: </b></th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>wardid</td>
                    <td>Ward ID</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>ward</td>
                    <td>The name of the Ward</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>online</td>
                    <td>The total No of e-Netcard Balances of all household mobilizer that hasnot been download per ward</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>wallet</td>
                    <td>The total No of e-Netcard Balances of all household mobilizer that has been download per ward</td>
                </tr>
            </table>
        </pre>';

    echo '<pre>
        #   <b style="color: #FF5722">Netcard movement from LGA to Ward</b>
        #   Result Code Documentation (result_code)
        #   result_code - 200 : Success
        #   result_code - 401 : Error (Unauthorized User/Unauthorized)
        #               - 400 : Failed
        #   message - : success
        #   <span style="color: #6A1B9A">Netcard movement from LGA to Ward</span>

        #   <i style="color: #FF5722">JWT must be passed via header</i>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=704</span>  (GET Request in the body)</th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>VALUE</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>jwt</td>
                    <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                    <td>Generated token after successful login, to be passed via header</td>
                </tr>
            </table>
                    - Body data
                    <b style="color:#7367f0">
                    {
                        "originatingLgaid": 91, 
                        "total": 2, 
                        "destinationWardid": 1012, 
                        "userid": 3

                    }
                    </b>

                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th colspan="2">Data Result</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td colspan="2">    
        {
            "result_code": 200,
            "dataset": "Netcard movement from LGA to Ward",
            "message": "success",
            "data": {
                "total": 0
            }
        }
                            </td>
                        </tr>
                    </table>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;">Result Discription: </b></th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>total</td>
                    <td>Total Moved e-Netcard to Ward</td>
                </tr>
            </table>
        </pre>';

    echo '<pre>
        #   <b style="color: #FF5722">Netcard Reversal from Ward to LGA</b>
        #   Result Code Documentation (result_code)
        #   result_code - 200 : Success
        #   result_code - 401 : Error (Unauthorized User/Unauthorized)
        #               - 400 : Failed
        #   message - : success
        #   <span style="color: #6A1B9A">Netcard Reversal from Ward to LGA</span>

        #   <i style="color: #FF5722">JWT must be passed via header</i>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;">Endpoint URL: </b> <b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=705</span>  (GET Request in the body)</th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>VALUE</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>jwt</td>
                    <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                    <td>Generated token after successful login, to be passed via header</td>
                </tr>
            </table>
                    - Body data
                    <b style="color:#7367f0">
                    {
                        "destinationLgaid": 91, 
                        "total": 2, 
                        "originatingWardid": 1012, 
                        "userid": 3
                    }
                    </b>

                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th colspan="2">Data Result</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td colspan="2">    
        {
            "result_code": 200,
            "dataset": "Netcard movement from LGA to Ward",
            "message": "success",
            "data": {
                "total": 0
            }
        }
                            </td>
                        </tr>
                    </table>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;">Result Discription: </b></th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>total</td>
                    <td>Total Moved e-Netcard to Ward</td>
                </tr>
            </table>
        </pre>';

    echo '<hr style="height: 10px; background-color: #7367f0">';

    echo '<pre>
                <h1 style="color: #7367f0"> SMC MODULE API: STARTS</h1>
                <hr style="height: 10px; background-color: #7367f0">
            </pre> ';

    echo '<pre>
                    #   <b style="color: #FF5722">Create Bulk Parents\' Record</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">List of available user roles</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=900</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>household_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "dpid": 3001,
                                "hh_token": "EDG0023",
                                "hoh": "Samuel Perry",
                                "phone": "08088282828",
                                "longitude": "7.98030",
                                "latitude": "9.809069",
                                "user_id": 3,
                                "device_serial": "KM23456",
                                "app_version": "1.0.0",
                                "created": "2024-05-02 13:44:55"
                            },
                            {
                                "dpiddpid": 3001,
                                "hh_token": "ECG0024",
                                "hoh": "Jumaid Salam",
                                "phone": "08062358989",
                                "longitude": "7.98030",
                                "latitude": "9.809069",
                                "user_id": 3,
                                "device_serial": "KM23456",
                                "app_version": "1.0.0",
                                "created": "2024-05-02 13:44:55"
                            }
                        ]</span>                      
                                </td>
                                <td>
        An array of user supplied data
        <span style="color: #f00; text-align: left;">
        array(
            array(
                \'dpid\'=>5001,
                \'hh_token\'=>\'EDG0023\',
                \'hoh\'=>\'Samuel Perry\',
                \'phone\'=>\'08088282828\',
                \'longitude\'=>\'7.98030\',
                \'latitude\'=>\'9.809069\',
                \'user_id\'=> 3,
                \'device_serial\' => \'KM23456\',
                \'app_version\'=>\'1.0.0\',
                \'created\'=>\'2024-05-02 13:44:55\'
            ),
            array(
                \'dpid\'=>5001,
                \'hh_token\'=>\'ECG0024\',
                \'hoh\'=>\'Jumaid Salam\',
                \'phone\'=>\'08062358989\',
                \'longitude\'=>\'7.98030\',
                \'latitude\'=>\'9.809069\',
                \'user_id\'=> 3,
                \'device_serial\' => \'KM23456\',
                \'app_version\'=>\'1.0.0\',
                \'created\'=>\'2024-05-02 13:43:55\'
            )
        )
        </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error or failed Creation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Error Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dataset</td>
                                <td>Array of household Token Successfully Created 
                                <span style="color: #f00;">e.g  [EDG0023", "ECG0024", "EDO0025"]</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>total</td>
                                <td>Total parents created</td>
                            </tr>
                        </table>
                </pre>';
    echo '<pre>
                    #   <b style="color: #FF5722">Bulk Parents\' Record Update</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : success
                    #   result_code - 400 : failed (Bad Request)
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">List of available user roles</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=901</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>household_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
[
    {
        "hh_token": "EDG0023",
        "hoh": "Saka Tosin",
        "phone": "07061977018",
    },
    {
        "hh_token": "ECG0024",
        "hoh": "Mdkamil Abdulazeez",
        "phone": "08167667682"
    }
]</span>                      
                                </td>
                                <td>
        An array of user supplied data
        <span style="color: #f00; text-align: left;">
        array(
            array(
                \'hh_token\'=>\'EDG0023\',
                \'hoh\'=>\'Saka Tosin\',
                \'phone\'=>\'08088282828\'
            ),
            array(
                \'hh_token\'=>\'ECG0024\',
                \'hoh\'=>\'Mdkamil Abdulazeez\',
                \'phone\'=>\'08062358989\'
            )
        )
        </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error or failed Update</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Error Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dataset</td>
                                <td>Array of household Token Successfully Updated 
                                <span style="color: #f00;">e.g  [EDG0023", "ECG0024", "EDO0025"]</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>total</td>
                                <td>Total parents Updated</td>
                            </tr>
                        </table>
                </pre>';
    echo '<pre>
                    #   <b style="color: #FF5722">Create Bulk Childs\' Record</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">List of available user roles</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=902</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>child_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "hh_token": "EDG0023",
                                "beneficiary_id": "TT172800",
                                "dpid": 3001,
                                "name":"James Bennet",
                                "gender": "Male",
                                "dob": "2023-01-01",
                                "longitude": "7.98030",
                                "latitude": "9.809069",
                                "user_id": 3,
                                "device_serial": "KM23456",
                                "app_version": "1.0.0",
                                "created": "2024-05-02 13:44:55"
                            },                            
                            {
                                "hh_token": "EDG0023",
                                "beneficiary_id": "AD172900",
                                "dpid": 3001,
                                "name":"Samuel Bennet",
                                "gender": "Female",
                                "dob": "2023-01-01",
                                "longitude": "7.98030",
                                "latitude": "9.809069",
                                "user_id": 3,
                                "device_serial": "KM23456",
                                "app_version": "1.0.0",
                                "created": "2024-05-02 13:44:55"
                            }
                        ]</span>                      
                                </td>
                                <td>
        An array of user supplied data
        <span style="color: #f00; text-align: left;">
        array(
            array(
                \'hh_token\'=>\'EDG0023\',
                \'beneficiary_id\'=>\'TT172800\',
                \'comminity_id\'=>\'5001\',
                \'name\'=>\'James Bennet\',
                \'gender\'=>\'Male\',
                \'dob\'=>\'2023-01-01\',
                \'longitude\'=>\'7.98030\',
                \'latitude\'=>\'9.809069\',
                \'user_id\'=> 3,
                \'device_serial\' => \'KM23456\',
                \'app_version\'=>\'1.0.0\',
                \'created\'=>\'2024-05-03 13:44:55\'
            ),
            array(
                \'hh_token\'=>\'EDG0023\',
                \'beneficiary_id\'=>\'AD172900\',
                \'comminity_id\'=>\'5001\',
                \'name\'=>\'Samuel Bennet\',
                \'gender\'=>\'Female\',
                \'dob\'=>\'2022-01-01\',
                \'longitude\'=>\'7.98030\',
                \'latitude\'=>\'9.809069\',
                \'user_id\'=> 3,
                \'device_serial\' => \'KM23456\',
                \'app_version\'=>\'1.0.0\',
                \'created\'=>\'2024-05-03 13:44:55\'
            )
        )
        </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error or failed Creation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Error Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dataset</td>
                                <td>Array of household Token Successfully Created 
                                <span style="color: #f00;">e.g  [EDG0023", "ECG0024", "EDO0025"]</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>total</td>
                                <td>Total parents created</td>
                            </tr>
                        </table>
                </pre>';
    echo '<pre>
                    #   <b style="color: #FF5722">Bulk Childs\' Record Upload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=903</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>child_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "beneficiary_id": "TT172800",
                                "name":"James Bennet",
                                "gender": "Male",
                                "dob": "2023-01-01",
                            },                            
                            {
                                "beneficiary_id": "AD172900",
                                "name":"Samuel Bennet",
                                "gender": "Female",
                                "dob": "2023-01-01"
                            }
                        ]</span>                      
                                </td>
                                <td>
        An array of user supplied data
        <span style="color: #f00; text-align: left;">
        array(
            array(
                \'beneficiary_id\'=>\'TT172800\',
                \'name\'=>\'James Bennet\',
                \'gender\'=>\'Male\',
                \'dob\'=>\'2023-01-01\'
            ),
            array(
                \'beneficiary_id\'=>\'AD172900\',
                \'name\'=>\'Samuel Bennet\',
                \'gender\'=>\'Female\',
                \'dob\'=>\'2022-01-01\'
            )
        )
        </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error or failed Creation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Error Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dataset</td>
                                <td>Array of Child e-Token Successfully Created 
                                <span style="color: #f00;">e.g  [EDG0023", "ECG0024", "EDO0025"]</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>total</td>
                                <td>Total parents created</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Bulk Drug Administration Record</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">Bulk Drug Administration Upload</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=904</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>drug_admin_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
        [
            {
                "periodid":1,
                "uid":"BMN0029-02920",
                "dpid":3001,
                "beneficiary_id":"DM3009",
                "is_eligible":1,
                "not_eligible_reason":"just test",
                "is_refer":1,
                "drug":"SPAQ 1",
                "drug_qty":1,
                "redose_count":1,
                "redose_reason":"redose reason",
                "user_id":3456,
                "longitude":"8.738390",
                "latitude":"9.049940",
                "device_serial": "KM23456",
                "app_version": "1.0.0",
                "collected_date":"2024-05-08 13:02:33",
                "issue_id" : 10,
                "resode_issue_id": 2
            },
            {
                "periodid":2,
                "uid":"BMN0029-02921",
                "dpid":3001,
                "beneficiary_id":"DM30010",
                "is_eligible":1,
                "not_eligible_reason":"just test",
                "is_refer":1,
                "drug":"SPAQ 1",
                "drug_qty":1,
                "redose_count":1,
                "redose_reason":"redose reason",
                "user_id":3456,
                "longitude":"8.738390",
                "latitude":"9.049940",
                "device_serial": "KM23456",
                "app_version": "1.0.0",
                "collected_date":"2024-05-08 13:02:33",
                "issue_id" : 10,
                "resode_issue_id": 2
            }
        ]</span>                      
                                </td>
                                <td>
        An array of user supplied data
        <span style="color: #f00; text-align: left;">
        array(
            array(
                \'periodid\'=>1,
                \'uid\'=>\'BMN0029-02920\',
                \'dpid\'=>3001,
                \'beneficiary_id\'=>\'DM3009\',
                \'is_eligible\'=>1,
                \'not_eligible_reason\'=>\'just test\',
                \'is_refer\'=>1,
                \'drug\'=>\'SPAQ 1\',
                \'drug_qty\'=>1,
                \'redose_count\'=>1,
                \'redose_reason\'=>\'redose reason\',
                \'user_id\'=>3456,
                \'longitude\'=>\'8.738390\',
                \'latitude\'=>\'9.049940\',
                \'device_serial\' => \'KM23456\',
                \'app_version\'=>\'1.0.0\',
                \'collected_date\'=>\'2024-05-08 13:02:33\',
                "issue_id" => 10,
                "resode_issue_id" => 2
            ),
            array(
                \'periodid\'=>1,
                \'uid\'=>\'BMN0029-02921\',
                \'dpid\'=>3001,
                \'beneficiary_id\'=>\'DM30010\',
                \'is_eligible\'=>1,
                \'not_eligible_reason\'=>\'just test\',
                \'is_refer\'=>1,
                \'drug\'=>\'SPAQ 1\',
                \'drug_qty\'=>1,
                \'redose_count\'=>1,
                \'redose_reason\'=>\'redose reason\',
                \'user_id\'=>3456,
                \'longitude\'=>\'8.738390\',
                \'latitude\'=>\'9.049940\',
                \'device_serial\' => \'KM23456\',
                \'app_version\'=>\'1.0.0\',
                \'collected_date\'=>\'2024-05-08 13:02:33\',
                "issue_id" => 10,
                "resode_issue_id" => 2
            )
        )
        </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error or failed Creation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Error Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dataset</td>
                                <td>Array of Beneficiary id Successfully Created 
                                <span style="color: #f00;">e.g  [DM3009", "DM30010"]</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>total</td>
                                <td>Total parents created</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Bulk Redose Update</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">Bulk Redose Update</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=904b</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>drug_redose_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
        [
            {
                "uid" : "BMN0029-02920", 
                "redose_count" : 2, 
                "redose_reason" : "check testing 01",
                "resode_issue_id": 3
            },
            {
                "uid" : "BMN0029-02921", 
                "redose_count" : 2, 
                "redose_reason" : "check testing 02",
                "resode_issue_id": 3
            },
            {
                "uid" : "BMN0029-02922", 
                "redose_count" : 2, 
                "redose_reason" : "check testing 03",
                "resode_issue_id": 3
            }
        ]</span>                      
                                </td>
                                <td>
        An array of user supplied data
        <span style="color: #f00; text-align: left;">
    array(
        array(
            \'uid\' : \'BMN0029-02920\', 
            \'redose_count\' : 2, 
            \'redose_reason\' : \'check testing 01\',
            \'resode_issue_id\' : 3
        ),
        array(
            \'uid\' : \'BMN0029-02921\', 
            \'redose_count\' : 2, 
            \'redose_reason\' : \'check testing 02\',
            \'resode_issue_id\' : 3
        ),
        array(
            \'uid\' : \'BMN0029-02922\', 
            \'redose_count\' : 2, 
            \'redose_reason\' : \'check testing 03\',
            \'resode_issue_id\' : 3
        )
    )
        </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error or failed Creation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Error Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dataset</td>
                                <td>Array of UUID Successfully Updated 
                                <span style="color: #f00;">e.g  [DM3009", "DM30010"]</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>total</td>
                                <td>Total Redose Updated</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">ICC - Issue: Inventory Control Administration (ICC)</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">ICC - Issue: Inventory Control Administration (ICC)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=905</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>icc_Issue_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
    [
        {
            "uid":"WE099-2002920", 
            "dpid" : 3001, 
            "issuer_id" : 2001, 
            "cdd_lead_id" : 2501, 
            "cdd_team_code":"NB/4664/ABU/002", 
            "periodid" : 1, 
            "issue_date":"2024-05-09 08:32:14", 
            "issue_day":"Day 1", 
            "issue_drug":"SPAQ 1", 
            "drug_qty" : 20,
            "device_serial": "KM23456",
            "app_version": "1.0.0"

        },
        {
            "uid":"WE099-2002921", 
            "dpid" : 3001, 
            "issuer_id" : 2001, 
            "cdd_lead_id" : 2502, 
            "cdd_team_code":"NB/4664/ABU/012", 
            "periodid" : 1, 
            "issue_date":"2024-05-09 08:32:14", 
            "issue_day":"Day 1", 
            "issue_drug":"SPAQ 2", 
            "drug_qty" : 30,
            "device_serial": "KM23456",
            "app_version": "1.0.0"
        }
    ]</span>                      
                                </td>
                                <td>
        An array of user supplied data
        <span style="color: #f00; text-align: left;">
        array(
            array(
                \'uid\' => \'WE099-2002920\', 
                \'dpid\' => 3001, 
                \'issuer_id\' => 2001, 
                \'cdd_lead_id\' => 2501, 
                \'cdd_team_code\' => \'NB/4664/ABU/002\', 
                \'periodid\' => 1, 
                \'issue_date\' => \'2024-05-09 08:32:14\', 
                \'issue_day\' => \'Day 1\', 
                \'issue_drug\' => \'SPAQ 1\', 
                \'drug_qty\' => 20,
                \'device_serial\' => \'KM23456\',
                \'app_version\'=>\'1.0.0\'
            ),
            array(
                \'uid\' => \'WE099-2002921\', 
                \'dpid\' => 3001, 
                \'issuer_id\' => 2001,
                 \'cdd_lead_id\' => 2502, 
                 \'cdd_team_code\' => \'NB/4664/ABU/012\', 
                 \'periodid\' => 1, 
                 \'issue_date\' => \'2024-05-09 08:32:14\', 
                 \'issue_day\' => \'Day 1\', 
                 \'issue_drug\' => \'SPAQ 2\', 
                 \'drug_qty\' => 30,
                \'device_serial\' => \'KM23456\',
                \'app_version\'=>\'1.0.0\'
            )
        )
        </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error or failed Creation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Error Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dataset</td>
                                <td>Array of uid Successfully Uploaded 
                                <span style="color: #f00;">e.g  [
                                            {
                                                "uid": "WE099-2002920"
                                                "issue_id: "10"
                                            },
                                            {
                                                "uid": "WE099-2002921"
                                                "issue_id: "11"
                                            }
                                    ]</span></td>
                            </tr> 
                        </table>
                </pre>';
    echo '<pre>
                    #   <b style="color: #FF5722">ICC - Receive: Inventory Control Administration (ICC)</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">ICC - Receive: Inventory Control Administration (ICC)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=906</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>icc_Issue_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
    [
        {
            "uid":"WE099-2002920",
            "dpid":3001,
            "receiver_id":2001,
            "cdd_lead_id":2501,
            "cdd_team_code":"NB/4664/ABU/002",
            "periodid":1,
            "received_date":"2024-05-09 08:32:14",
            "received_day":"Day 1",
            "received_drug":"SPAQ 1",
            "total_qty":5,
            "full_dose_qty":3,
            "partial_qty":5,
            "wasted_qty":1

        },
        {
            "uid":"WE099-2002921",
            "dpid":3001,
            "receiver_id":2001,
            "cdd_lead_id":2502,
            "cdd_team_code":"NB/4664/ABU/012",
            "periodid":1,
            "received_date":"2024-05-09 08:32:14",
            "received_day":"Day 1",
            "received_drug":"SPAQ 2",
            "total_qty":4,
            "full_dose_qty":2,
            "partial_qty":2,
            "wasted_qty":0 
        }
    ]</span>                      
                                </td>
                                <td>
        An array of user supplied data
        <span style="color: #f00; text-align: left;">
         array(
            array(
                \'uid\'=>\'WE099-2002920\',
                \'dpid\'=>3001,
                \'receiver_id\'=>2001,
                \'cdd_lead_id\'=>2501,
                \'cdd_team_code\'=>\'NB/4664/ABU/002\',
                \'periodid\'=>1,
                \'received_date\'=>\'2024-05-09 08:32:14\',
                \'received_day\'=>\'Day 1\',
                \'received_drug\'=>\'SPAQ 1\',
                \'total_qty\'=>5,
                \'full_dose_qty\'=>3,
                \'partial_qty\'=>5,
                \'wasted_qty\'=>1
            ),
            array(
                \'uid\'=>\'WE099-2002921\',
                \'dpid\'=>3001,
                \'receiver_id\'=>2001,
                \'cdd_lead_id\'=>2502,
                \'cdd_team_code\'=>\'NB/4664/ABU/012\',
                \'periodid\'=>1,
                \'received_date\'=>\'2024-05-09 08:32:14\',
                \'received_day\'=>\'Day 1\',
                \'received_drug\'=>\'SPAQ 2\',
                \'total_qty\'=>4,
                \'full_dose_qty\'=>2,
                \'partial_qty\'=>2,
                \'wasted_qty\'=>0
            )        
        )
        </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error or failed Creation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Error Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dataset</td>
                                <td>Array of ICC Received uid Successfully Uploaded 
                                <span style="color: #f00;">e.g  ["WE099-2002920", "WE099-2002921"]</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>total</td>
                                <td>Total parents created</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                #   <b style="color: #FF5722">Get ICC Administration Record List using dpid</b>
                #   Result Code Documentation (result_code)
                #   result_code - 200 : Success
                #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                #               - 400 : Error
                #   message - : success
                #   <span style="color: #6A1B9A">Get ICC Administration Record List using dpid (API in the body)</span>
                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=907</span>  (POST Request in the body)</th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th>KEY</th>
                            <th>VALUE</th>
                            <th>DESCRIPTION</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>jwt</td>
                            <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                            <td>Generated token after successful login</td>
                        </tr>
                    </table>
                            <b>Body data</b><div style="color:#7367f0">                                {
                                "dpid": 1
                            }</div>
                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th>KEY</th>
                            <th>DESCRIPTION</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>user_id</td>
                            <td>CDD Lead User ID</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>date</td>
                            <td>Administration Record Date</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>loginid</td>
                            <td>CDD Lead Login ID</td>
                        </tr> 
                        <tr>
                            <td>4</td>
                            <td>cdd_lead</td>
                            <td>CDD Lead Fullname</td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>drug</td>
                            <td>Drug Name (i.e. SPAQ 1. Ineligible)</td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>qty</td>
                            <td>Drug Quantity</td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>redose</td>
                            <td>Redose Quantity</td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>ineligible</td>
                            <td>Ineligible Quantity</td>
                        </tr>
                    </table>
            </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Referrer attended to, Bulk Record Save</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #   message - : success
                    #   <span style="color: #6A1B9A">Referrer attended to Bulk Record Save</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=908</span>  (POST Request)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>drug_redose_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
        [
            {
                "adm_id":"", 
                "uid":"", 
                "beneficiary_id":"", 
                "userid":"", 
                "refer_type":"", 
                "ill_cause_of":"", 
                "ill_diagnosis":"", 
                "ill_child_treated":"", 
                "ill_dose_of_treatment":"", 
                "ill_admitted":"", 
                "fe_tested_for_malaria":"", 
                "fe_rdt_result":"", 
                "fe_admitted":"", 
                "fe_treated_with_act":"", 
                "fe_name_dose":"", 
                "fe_given_spaq":"", 
                "ad_child_evaluated":"", 
                "ad_pv_form_completed":"", 
                "ad_child_admitted":"", 
                "outcome":"", 
                "collected_date":""
            },
            {
                "adm_id":"", 
                "uid":"", 
                "beneficiary_id":"", 
                "userid":"", 
                "refer_type":"", 
                "ill_cause_of":"", 
                "ill_diagnosis":"", 
                "ill_child_treated":"", 
                "ill_dose_of_treatment":"", 
                "ill_admitted":"", 
                "fe_tested_for_malaria":"", 
                "fe_rdt_result":"", 
                "fe_admitted":"", 
                "fe_treated_with_act":"", 
                "fe_name_dose":"", 
                "fe_given_spaq":"", 
                "ad_child_evaluated":"", 
                "ad_pv_form_completed":"", 
                "ad_child_admitted":"", 
                "outcome":"", 
                "collected_date":""
            },
        ]</span>                      
                                </td>
                                <td>
        An array of user supplied data
        <span style="color: #f00; text-align: left;">
array(
    array(
        adm_id => "45",
        uid => "BMN0029-02920",
        beneficiary_id => "23",
        userid => "2",
        refer_type => "1",
        ill_cause_of => "",
        ill_diagnosis => "",
        ill_child_treated => "",
        ill_dose_of_treatment => "",
        ill_admitted => "",
        fe_tested_for_malaria => "",
        fe_rdt_result => "",
        fe_admitted => "",
        fe_treated_with_act => "",
        fe_name_dose => "",
        fe_given_spaq => "",
        ad_child_evaluated => "",
        ad_pv_form_completed => "",
        ad_child_admitted => "",
        outcome => "",
        collected_date => ""
    ),
    array(
        adm_id => "",
        uid => "BMN0029-02922",
        beneficiary_id => "",
        userid => "",
        refer_type => "",
        ill_cause_of => "",
        ill_diagnosis => "",
        ill_child_treated => "",
        ill_dose_of_treatment => "",
        ill_admitted => "",
        fe_tested_for_malaria => "",
        fe_rdt_result => "",
        fe_admitted => "",
        fe_treated_with_act => "",
        fe_name_dose => "",
        fe_given_spaq => "",
        ad_child_evaluated => "",
        ad_pv_form_completed => "",
        ad_child_admitted => "",
        outcome => "",
        collected_date => ""
    )

)
        </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>result_code</td>
                                <td>200 for success, 400 for error or failed Creation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>message</td>
                                <td>Success or Error Message</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dataset</td>
                                <td>Array of UUID Successfully Updated 
                                <span style="color: #f00;">e.g  [DM3009", "DM30010"]</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>total</td>
                                <td>Total Redose Updated</td>
                            </tr>
                        </table>
                </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">Download ICC Balance by CDD</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Download ICC Balance Data (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=909</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b>Body data</b><div style="color:#7367f0">
                                {
                                    "periodid": "1",  
                                    "cddid": 1000,
                                    "device_id": "KL001",
                                    "app_version": "1.3" 
                                }</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>periodid</td>
                                <td>Period ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>issue_id</td>
                                <td>Issue ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>drug</td>
                                <td>SPAQ or Drug Name i.e SPAQ 1</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>qty</td>
                                <td>Drug Quantity</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>issue_date</td>
                                <td>Drug Issue Date</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>download_id</td>
                                <td>Download ID</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Confirm ICC Download by CDD</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Confirm ICC Download by CDD (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=909a</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b>Body data</b><div style="color:#7367f0">
                                {
                                    "download_id": "10",  
                                    "issue_id": 1000,
                                    "cddid": "20001"
                                }</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>status</td>
                                <td>True or False for the confirmation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>download_id</td>
                                <td>Download ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>issue_id</td>
                                <td>Issue ID</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>cddid</td>
                                <td>CDD ID</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">ICC Acceptance by CDD</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">ICC Acceptance by CDD (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=909b</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b>Body data</b><div style="color:#7367f0">
                                {
                                    "issue_id": 10,
                                }</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>status</td>
                                <td>True or False for the confirmation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>issue_id</td>
                                <td>Issue ID</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">ICC Rejection by CDD</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">ICC Rejection by CDD (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=909c</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b>Body data</b><div style="color:#7367f0">
                                {
                                    "issue_id": 10,
                                    "reasons": "The value received doesn\'t correspond to the value sent",
                                }</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>status</td>
                                <td>True or False for the confirmation</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>issue_id</td>
                                <td>Issue ID</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Get list of reconciliation by Facility</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get list of reconciliation Data using dpid (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=910</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b>Body data</b><div style="color:#7367f0">                                {
                                    "dpid": 1 ,
                                    "periodid": 1 
                                }</div>
                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th colspan="2">Data Result</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td colspan="2">    
        {
            "result_code": 200,
            "dataset": "Get ICC Reconcilation Data",
            "message": "success",
            "data": [
                {
                    "issue_id": 1,
                    "cdd_lead_id": KC2344,
                    "loginid": 2344,
                    "cdd": "Saka Tayo",
                    "drug": "SPAQ 1",
                    "full": 15,
                    "used": 2,
                    "partial": 1,
                    "wasted": 0,
                    "issued": 30
                },
                {
                    "issue_id": 2,
                    "cdd_lead_id": KC2344,
                    "loginid": 2344,
                    "cdd": "Saka Tayo",
                    "drug": "SPAQ 2",
                    "full": 25,
                    "used": 4,
                    "partial": 1,
                    "wasted": 0,
                    "issued": 40
                }
            ]
        }
                            </td>
                        </tr>
                    </table>
                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Result Description: </b></th>
                        </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th>KEY</th>
                            <th>DESCRIPTION</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>issue_id</td>
                            <td>Unique issue ID</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>cdd_lead_id</td>
                            <td>The CDD Lead ID</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>loginid</td>
                            <td>The CDD Lead Login ID</td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>cdd</td>
                            <td>The CDD Lead Full Name</td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>drug</td>
                            <td>The Drug Type Name</td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>full</td>
                            <td>Total No of full Drug Issued</td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>used</td>
                            <td>Total No of Drug Used</td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>partial</td>
                            <td>Total Partioal Drug</td>
                        </tr>
                        <tr>
                            <td>9</td>
                            <td>wasted</td>
                            <td>Total Wasted Drug</td>
                        </tr>
                        <tr>
                            <td>10</td>
                            <td>issued</td>
                            <td>Total Drug Issued</td>
                        </tr>
                    </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Bulk ICC Reconcile Upload / Bulk ICC Save by HFW</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Bulk ICC Reconcile Upload(API in the header) by HFW</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=911</span>  (POST Request in the header)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>allocation_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "issue_id":9,
                                "cdd_lead_id": 1081,
                                "drug": "SPAQ 1",
                                "used_qty": 5,
                                "full_qty": 3,
                                "partial_qty": 2,
                                "wasted_qty": 0, 
                                "loss_qty": 1,
                                "loss_reason": "Lost because of unknown reason",
                                "receiver_id":23, 
                                "device_serial": "KM23456",
                                "app_version": "1.0.0",
                                "reconcile_date" : "2024-05-09 08:32:14"
                            },
                            {
                                "issue_id":10,
                                "cdd_lead_id": 1081,
                                "drug": "SPAQ 2",
                                "used_qty": 3,
                                "full_qty": 1,
                                "partial_qty": 2,
                                "wasted_qty": 0, 
                                "loss_qty": 0,
                                "loss_reason": "",
                                "receiver_id":23, 
                                "device_serial": "KM23456",
                                "app_version": "1.0.0",
                                "reconcile_date" : "2024-05-09 08:32:14"
                            }
                        ] </span></td>
                                <td>
                                <span style="color: #f00; text-align: left;">
                                    [array("issue_id"=>9, "cdd_lead_id"=>1081, "drug"=>"SPAQ 1", "used_qty"=>5, "full_qty"=>3, "partial_qty"=>2, "wasted_qty"=>0, "loss_qty"=>0, "loss_reason"=>"none", "receiver_id"=>2001, "device_serial"=>"NM0098", "app_version"=>"v1.0.9", "reconcile_date"=>"2024-05-09 08:32:14"),
                                     array("issue_id"=>10, "cdd_lead_id"=>1081, "drug"=>"SPAQ 2", "used_qty"=>5, "full_qty"=>3, "partial_qty"=>2, "wasted_qty"=>0, "loss_qty"=>0, "loss_reason"=>"none", "receiver_id"=>2001, "device_serial"=>"NM0098", "app_version"=>"v1.0.9", "reconcile_date"=>"2024-05-09 08:32:14")];
                                </span>
                                </td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Push CCD Lead Drug Balance Online</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Push CCD Lead Drug Balance Online (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=912</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>drug Form Data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "periodid": "2024 Cycle 1",
                                "dpid": 1000,
                                "issue_id": 20,
                                "cdd_lead_id": 4012,
                                "drug": "SPAQ 1",
                                "qty": 40,
                                "device_id": "KLM2044",
                                "app_version": "1.0.2",
                                "created": "2024-05-09 08:32:14"
                            },
                            {
                                "periodid": "2024 Cycle 1",
                                "dpid": 1000,
                                "issue_id": 20,
                                "cdd_lead_id": 4012,
                                "drug": "SPAQ 2",
                                "qty": 40,
                                "device_id": "KLM2044",
                                "app_version": "1.0.2",
                                "created": "2024-05-09 08:32:14"
                            }
                        ]</span>                      
                                </td>
                                <td>
                                An array of Drug Left on the device
                                <span style="color: #f00; text-align: left;">
                            array(
                                array("periodid"=> "2024 Cycle 1", \'dpid\'=> 1000, "issue_id"=> 20, \'cdd_lead_id\'=> \'4012\', \'drug\'=> \'SPAQ 1\', \'qty\'=> 4, \'device_id\'=> \'KLM2044\', \'app_version\'=> \'1.0.2\', "created"=> "2024-05-09 08:32:14"),
                                array("periodid"=> "2024 Cycle 1", \'dpid\'=> 1000, "issue_id"=> 20, \'cdd_lead_id\'=> \'4012\', \'drug\'=> \'SPAQ 2\', \'qty\'=> 10, \'device_id\'=> \'KLM2044\', \'app_version\'=> \'1.0.2\', "created"=> "2024-05-09 08:32:14"),
                            )
                                </span>
                                </td>
                            </tr>

                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Reconcile CDD Balance</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : <b>Failed Reconcilation</b> 
                    #   message - : success
                    #   <span style="color: #6A1B9A">Reconcile CDD Balance Data (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=913</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b>Body data</b><div style="color:#7367f0"> 
                            [                               
                                { 
                                    "uid": "KM10332209202451620",
                                    "cdd_lead_id": 1033,
                                    "dpid": 3126,
                                    "drug": "SPAQ 1",
                                    "qty": 3,
                                    "device_id": "KL001",
                                    "app_version": "v1.2.test" 
                                },
                                {
                                    "uid": "KM10332209202451621",
                                    "cdd_lead_id": 1033,
                                    "dpid": 3126,
                                    "drug": "SPAQ 2",
                                    "qty": 6,
                                    "device_id": "KL001",
                                    "app_version": "v1.2.test" 
                                }
                            ]</div>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>uid</td>
                                <td>A Client generated Unique Balance Transaction ID for the drug balances</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>cdd_lead_id</td>
                                <td>CDD Lead ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>dpid</td>
                                <td>The ID of Health Facility</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>drug</td>
                                <td>SPAQ or Drug Name i.e SPAQ 1</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>qty</td>
                                <td>Drug Quantity</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>device_id</td>
                                <td>Unique Device ID</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>app_version</td>
                                <td>Current Application version</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>uid</td>
                                <td>The unique client ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>status</td>
                                <td>Success or failure message whuch can be <b>successful</b> or <b>failed</b></td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Get All DP/Facility Balances for CDDS</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">>Get DP/Facility Balances for CDDS (using dpid & Period ID - in the API body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=914</span>  (GET Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{
                                    "dpid": 1001,
                                    "periodid": 1
                                }</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>issue_id</td>
                                <td>Issue ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>cdd_lead_id</td>
                                <td>CDD Lead USer ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>loginid</td>
                                <td>CDD Lead Login ID</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>fullname</td>
                                <td>Fullname of CDD Lead</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>drug</td>
                                <td>Drug Name</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>issued</td>
                                <td>Issued Drug Qty</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>pending</td>
                                <td>Pending Drug Qty</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>confirmed</td>
                                <td>Confirm Drug</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>accepted</td>
                                <td>Accepted Drug</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>returned</td>
                                <td>Returned Drug</td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>Reconciled</td>
                                <td>Reconcile Drug</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">Bulk ICC ISSUED Return by CDD</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Bulk ICC ISSUED Return by CDD (API in the header) by CDD</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=915</span>  (POST Request in the header)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>allocation_form_data</td>
                                <td><span style="color: #f00; text-align: left;">
                        [
                            {
                                "returned_qty": 5,
                                "returned_partial": 2,
                                "issue_id":9,
                            },
                            {
                                "returned_qty": 5,
                                "returned_partial": 1,
                                "issue_id":10,
                            }
                        ] </span></td>
                                <td>
                                <span style="color: #f00; text-align: left;">
                                    [array("returned_qty"=>5, "returned_partial"=>2, "issue_id"=>9),
                                     array("returned_qty"=>5, "returned_partial"=>1, "issue_id"=>10)];
                                </span>
                                </td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>
                #   <b style="color: #FF5722">Get Current User DP/Facility Inventory Balance</b>
                #   Result Code Documentation (result_code)
                #   result_code - 200 : Success
                #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                #               - 400 : Error
                #   message - : success
                #   <span style="color: #6A1B9A">Get Current User DP/Facility Inventory Balanc (using dpid ID - in the API body)</span>
                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=916</span>  (GET Request in the body)</th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th>KEY</th>
                            <th>VALUE</th>
                            <th>DESCRIPTION</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>jwt</td>
                            <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                            <td>Generated token after successful login</td>
                        </tr>
                    </table>
                            <b style="color:#7367f0">{
                                "dpid": 1001
                            }</b> - body data
                    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                    <tr>
                        <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                    </tr>
                        <tr class="table-primary">
                            <th width="40px">#</th>
                            <th>KEY</th>
                            <th>DESCRIPTION</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>inventory_id</td>
                            <td>Inventory ID</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>product_code</td>
                            <td>Product Code</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>product_name</td>
                            <td>Product Name</td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>batch</td>
                            <td>Product Batch No</td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>expiry</td>
                            <td>Product Expiry Date</td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>rate</td>
                            <td>Rate</td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>unit</td>
                            <td>Unit Rate (1x50)</td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>primary_qty</td>
                            <td>Primary Quantity</td>
                        </tr>
                    </table>
            </pre>';

    echo '<pre>
            #   <b style="color: #FF5722">Get APP Movement List using period and Conveyor Details</b>
            #   Result Code Documentation (result_code)
            #   result_code - 200 : Success
            #   result_code - 401 : Error (Unauthorized User/Unauthorized)
            #               - 400 : Error
            #   message - : success
            #   <span style="color: #6A1B9A">Get APP Movement List (using periodId and ConveyorId - in the API body)</span>
                <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                <tr>
                    <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=917</span>  (GET Request in the body)</th>
                </tr>
                    <tr class="table-primary">
                        <th width="40px">#</th>
                        <th>KEY</th>
                        <th>VALUE</th>
                        <th>DESCRIPTION</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>jwt</td>
                        <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                        <td>Generated token after successful login</td>
                    </tr>
                </table>
                        <b style="color:#7367f0">{
                            "periodId": 1,
                            "conveyorId":53
                        }</b> - body data
                <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                <tr>
                    <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                </tr>
                    <tr class="table-primary">
                        <th width="40px">#</th>
                        <th>KEY</th>
                        <th>DESCRIPTION</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>movement</td>
                        <td>Array or set of all movements</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>movement_id</td>
                        <td>Movement ID</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>period</td>
                        <td>Period</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>transporter</td>
                        <td>Transporter Name</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>shipments</td>
                        <td>Shipment Data Sets</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>item_id</td>
                        <td>Item ID</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>shipment_type</td>
                        <td>Forward or Reverse</td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>origin_location_type</td>
                        <td>CMS/Facility</td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>origin_string</td>
                        <td>Origin location</td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>destination_location_type</td>
                        <td>Facility...</td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>destination_string</td>
                        <td>Destination String location</td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td>total_qty</td>
                        <td>Total Primary Quantity</td>
                    </tr>
                    <tr>
                        <td>13</td>
                        <td>total_value</td>
                        <td>Total Secondary Value</td>
                    </tr>
                    <tr>
                        <td>14</td>
                        <td>Shipment Status</td>
                        <td>Pending</td>
                    </tr>
                    <tr>
                        <td>15</td>
                        <td>created</td>
                        <td>Date Created</td>
                    </tr>
                </table>
        </pre>';

    echo '<pre>
        #   <b style="color: #FF5722">Confirm Movement Route</b>
        #   Result Code Documentation (result_code)
        #   result_code - 200 : Success
        #   result_code - 401 : Error (Unauthorized User/Unauthorized)
        #               - 400 : Error
        #   message - : success
        #   <span style="color: #6A1B9A">Confirm Movement Route using Movement ID (API in the body)</span>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=918</span>  (POST Request in the body)</th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>VALUE</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>jwt</td>
                    <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                    <td>Generated token after successful login</td>
                </tr>
            </table>
                    <b>Body data</b><div style="color:#7367f0">
                    {
                        "movementId": 1,  
                    }</div>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>status</td>
                    <td>True or False for the confirmation</td>
                </tr>
            </table>
    </pre>';

    echo '<pre>
        #   <b style="color: #FF5722">Origin Approval</b>
        #   Result Code Documentation (result_code)
        #   result_code - 200 : Success
        #   result_code - 401 : Error (Unauthorized User/Unauthorized)
        #               - 400 : Error
        #   message - : success
        #   <span style="color: #6A1B9A">Origin Approval (API in the body)</span>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=919</span>  (POST Request in the body)</th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>VALUE</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>jwt</td>
                    <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                    <td>Generated token after successful login</td>
                </tr>
            </table>
                    <b>Body data</b><div style="color:#7367f0">
                    {
                        "movementId": 1,  
                        "name": "Mdkamil Abdulazeez",
                        "designation": "Conveyor",
                        "phone": "07061977018",

                        "userId":33,
                        "locationString": "Benue CMS"
                        "signature":"",
                        "approveDate":"",
                        "latitude":"",
                        "longitude":"",
                        "deviceSerial":"",
                        "appVersion": ""                        

                    }</div>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>status</td>
                    <td>True or False for the confirmation</td>
                </tr>
            </table>
    </pre>';

    echo '<pre>
        #   <b style="color: #FF5722">Origin Approval</b>
        #   Result Code Documentation (result_code)
        #   result_code - 200 : Success
        #   result_code - 401 : Error (Unauthorized User/Unauthorized)
        #               - 400 : Error
        #   message - : success
        #   <span style="color: #6A1B9A">Origin Approval (API in the body)</span>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=920</span>  (POST Request in the body)</th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>VALUE</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>jwt</td>
                    <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                    <td>Generated token after successful login</td>
                </tr>
            </table>
                    <b>Body data</b><div style="color:#7367f0">
                    {
                        "movementId": 1,  
                        "name": "Mdkamil Abdulazeez",
                        "designation": "Conveyor",
                        "phone": "07061977018",

                        "userId":33,
                        "locationString": "Benue CMS"
                        "signature":"",
                        "approveDate":"",
                        "latitude":"",
                        "longitude":"",
                        "deviceSerial":"",
                        "appVersion": ""                        

                    }</div>
            <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
            <tr>
                <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
            </tr>
                <tr class="table-primary">
                    <th width="40px">#</th>
                    <th>KEY</th>
                    <th>DESCRIPTION</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>status</td>
                    <td>True or False for the confirmation</td>
                </tr>
            </table>
    </pre>';

    echo '<pre>
    #   <b style="color: #FF5722">Destination Approval</b>
    #   Result Code Documentation (result_code)
    #   result_code - 200 : Success
    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
    #               - 400 : Error
    #   message - : success
    #   <span style="color: #6A1B9A">Destination Approval (API in the body)</span>
        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
        <tr>
            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=921</span>  (POST Request in the body)</th>
        </tr>
            <tr class="table-primary">
                <th width="40px">#</th>
                <th>KEY</th>
                <th>VALUE</th>
                <th>DESCRIPTION</th>
            </tr>
            <tr>
                <td>1</td>
                <td>jwt</td>
                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                <td>Generated token after successful login</td>
            </tr>
        </table>
                <b>Body data</b><div style="color:#7367f0">
                {
                    "movementId": 1,  
                    "name": "Mdkamil Abdulazeez",
                    "designation": "Conveyor",
                    "phone": "07061977018",

                    "shipmentId": 1,
                    "userId":33,
                    "locationString": "Benue CMS"
                    "signature":"",
                    "approveDate":"",
                    "latitude":"",
                    "longitude":"",
                    "deviceSerial":"",
                    "appVersion": ""                        

                }</div>
        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
        <tr>
            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
        </tr>
            <tr class="table-primary">
                <th width="40px">#</th>
                <th>KEY</th>
                <th>DESCRIPTION</th>
            </tr>
            <tr>
                <td>1</td>
                <td>status</td>
                <td>True or False for the confirmation</td>
            </tr>
        </table>
</pre>';

    echo '<pre>
#   <b style="color: #FF5722">Inter Facility Transfer</b>
#   Result Code Documentation (result_code)
#   result_code - 200 : Success
#   result_code - 401 : Error (Unauthorized User/Unauthorized)
#               - 400 : Error
#   message - : success
#   <span style="color: #6A1B9A">Inter Facility Transfer between 2 Facilities (API in the body)</span>
    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
    <tr>
        <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=922</span>  (POST Request in the body)</th>
    </tr>
        <tr class="table-primary">
            <th width="40px">#</th>
            <th>KEY</th>
            <th>VALUE</th>
            <th>DESCRIPTION</th>
        </tr>
        <tr>
            <td>1</td>
            <td>jwt</td>
            <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
            <td>Generated token after successful login</td>
        </tr>
    </table>
            <b>Body data</b><div style="color:#7367f0">
            {
                "inventoryId": 1,  
                "fromFalicityId": 1001,
                "toFacilityId": 1002,
                "primaryQty": 30                     
            }</div>
    <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
    <tr>
        <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
    </tr>
        <tr class="table-primary">
            <th width="40px">#</th>
            <th>KEY</th>
            <th>DESCRIPTION</th>
        </tr>
        <tr>
            <td>1</td>
            <td>status</td>
            <td>True or False for the confirmation</td>
        </tr>
    </table>
</pre>';

    echo '<pre>
                <h1 style="color: #7367f0"> MONITORING TOOLS MODULE API: STARTS</h1>
                <hr style="height: 10px; background-color: #7367f0">
            </pre> ';


    echo '<pre>
                    #   <b style="color: #FF5722">i9a Form Bulk Data Upload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #                      - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">i9a Form Bulk Data Upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=1000</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>i9a Form Data</td>
                                <td><span style="color: #f00; text-align: left;">
        [
            {
                "uid": "uid-8723ff0c2a",
                "lgaid": "100",
                "wardid": "1000",
                "comid": "256",
                "userid": "3",
                "latitude": "8.4921",
                "longitude": "7.5463",
                "aa": "Yes",
                "ab": "Mrs. Grace Danjuma",
                "ac": "Yes",
                "ad": "To inform us about the upcoming ITN distribution campaign",
                "ae": "5",
                "af": "Yes",
                "ag": "2",
                "ah": "Yes",
                "ai": "He explained how to prevent malaria and the importance of using mosquito nets.",
                "domain": "demo.ipolongo.org",
                "app_version": "pwa-1.0.1",
                "capture_date": "2025-04-24T10:35:00Z"
            },
            {
                "uid": "uid-5479be88a1",
                "lgaid": "100",
                "wardid": "1000",
                "comid": "256",
                "userid": "3",
                "latitude": "8.5290",
                "longitude": "7.5600",
                "aa": "No",
                "ab": "Mr. Samuel Okeke",
                "ac": "No",
                "ad": "",
                "ae": "7",
                "af": "No",
                "ag": "0",
                "ah": "No",
                "ai": "",
                "domain": "demo.ipolongo.org",
                "app_version": "pwa-1.0.1",
                "capture_date": "2025-04-24T10:40:00Z"
            }
        ]</span>                      
                                </td>
                                <td>
                        An array of Drug Left on the device
                                <span style="color: #f00; text-align: left;">
                        array(
                            array(\'uid\' => \'xc0993600696049901\', \'lgaid\' => \'100\', \'wardid\' => \'1000\', \'comid\' => \'4000\', \'userid\' => \'1001\', \'latitude\' => \'9.20029\', \'longitude\' => \'5.68869\', \'aa\' => \'\', \'ab\' => \'\', \'ac\' => \'\', \'ad\' => \'\', \'ae\' => \'\', \'af\' => \'\', \'ag\' => \'\', \'ah\' => \'\', \'ai\' => \'\', \'domain\' => demo.ipolongo.org, \'app_version\' => \'PWA-1.0.1\', \'capture_date\' => \'2023-09-20 08:00:00\'),
                            array(\'uid\' => \'xc0993600696040902\', \'lgaid\' => \'100\', \'wardid\' => \'1000\', \'comid\' => \'4000\', \'userid\' => \'1001\', \'latitude\' => \'9.20029\', \'longitude\' => \'5.68869\', \'aa\' => \'\', \'ab\' => \'\', \'ac\' => \'\', \'ad\' => \'\', \'ae\' => \'\', \'af\' => \'\', \'ag\' => \'\', \'ah\' => \'\', \'ai\' => \'\', \'domain\' => demo.ipolongo.org, \'app_version\' => \'PWA-1.0.1\', \'capture_date\' => \'2023-09-20 08:00:00\'),
                            array(\'uid\' => \'xc0993600696090003\', \'lgaid\' => \'100\', \'wardid\' => \'1000\', \'comid\' => \'4000\', \'userid\' => \'1001\', \'latitude\' => \'9.20029\', \'longitude\' => \'5.68869\', \'aa\' => \'\', \'ab\' => \'\', \'ac\' => \'\', \'ad\' => \'\', \'ae\' => \'\', \'af\' => \'\', \'ag\' => \'\', \'ah\' => \'\', \'ai\' => \'\', \'domain\' => demo.ipolongo.org, \'app_version\' => \'PWA-1.0.1\', \'capture_date\' => \'2023-09-20 08:00:00\')
                        )
                                </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                            <thead style="background-color: #f2f2f2;">
                                <tr>
                                <th>Field</th>
                                <th>Data Type</th>
                                <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>uid</td><td>string</td><td>Unique identifier for the form submission, generated on the client side.</td></tr>
                                <tr><td>lgaid</td><td>string</td><td>Local Government Area ID.</td></tr>
                                <tr><td>wardid</td><td>string</td><td>Ward ID within the LGA.</td></tr>
                                <tr><td>comid</td><td>string</td><td>Community ID within the ward.</td></tr>
                                <tr><td>userid</td><td>string</td><td>ID of the user who submitted the data (server-generated).</td></tr>
                                <tr><td>latitude</td><td>string</td><td>Latitude coordinate of the household location.</td></tr>
                                <tr><td>longitude</td><td>string</td><td>Longitude coordinate of the household location.</td></tr>
                                <tr><td>aa</td><td>string</td><td>Was the household visited by a mobilizer? (Expected: "Yes"/"No")</td></tr>
                                <tr><td>ab</td><td>string</td><td>Name of the household head.</td></tr>
                                <tr><td>ac</td><td>string</td><td>Confirmation if a mobilizer visited. (Expected: "Yes"/"No")</td></tr>
                                <tr><td>ad</td><td>string</td><td>Reason stated by mobilizer for the visit.</td></tr>
                                <tr><td>ae</td><td>string</td><td>Number of people in the household.</td></tr>
                                <tr><td>af</td><td>string</td><td>Did the mobilizer give a Net Card? (Expected: "Yes"/"No")</td></tr>
                                <tr><td>ag</td><td>string</td><td>Number of Net Cards given.</td></tr>
                                <tr><td>ah</td><td>string</td><td>Was the Net Card filled correctly? (Expected: "Yes"/"No")</td></tr>
                                <tr><td>ai</td><td>string</td><td>Malaria prevention information shared by the mobilizer.</td></tr>
                                <tr><td>domain</td><td>string</td><td>Domain from which the app was executed (e.g., demo.ipolongo.org).</td></tr>
                                <tr><td>app_version</td><td>string</td><td>Version of the app used (e.g., pwa-1.0.1).</td></tr>
                                <tr><td>capture_date</td><td>string</td><td>ISO 8601 formatted timestamp of form capture (e.g., 2025-04-24T10:40:00Z).</td></tr>
                            </tbody>
                            </table>
    </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">i9b Form Bulk Data Upload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #                      - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">i9b Form Bulk Data Upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=1001</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>i9a Form Data</td>
                                <td><span style="color: #f00; text-align: left;">
        [
            {
            "uid": "uid-8723ff0c2a",
            "lgaid": "100",
            "wardid": "1000",
            "dpid": "DP identifier",
            "comid": "256",
            "userid": "3",
            "latitude": "8.4921",
            "longitude": "7.5463",
            "sp": "Mrs. Grace Danjuma",
            "aa": "No",
            "ab": "Comment: the net has finished",
            "ba": "No",
            "bb": "The team device has not been charged",
            "ca": "No",
            "cb": "Comment",
            "da": "Yes",
            "db": "Comment",
            "ea": "No",
            "eb": "Comment: One Person is missing",
            "fa": "No",
            "fb": "They are not in their aprons",
            "ga": "Yes",
            "gb": "Comment",
            "ha": "Yes",
            "hb": "Comment",
            "ia": "Yes",
            "ib": "Comment",
            "ja": "No",
            "jb": "Comment",
            "ka": "No",
            "kb": "Comment",
            "la": "No",
            "lb": "Comment",
            "ma": "No",
            "mb": "Comment",
            "na": "No",
            "nb": "Comment",
            "oa": "No",
            "ob": "Comment",
            "domain": "demo.ipolongo.org",
            "app_version": "pwa-1.0.1",
            "capture_date": "2025-04-24T10:40:00Z"
            },
            {
            "uid": "uid-5479be88a1",
            "lgaid": "100",
            "wardid": "1000",
            "dpid": "DP identifier",
            "comid": "256",
            "userid": "3",
            "latitude": "8.5290",
            "longitude": "7.5600",
            "sp": "Mr. Samuel Okeke",
            "aa": "No",
            "ab": "Comment: the net has finished",
            "ba": "No",
            "bb": "The team device has not been charged",
            "ca": "No",
            "cb": "Comment",
            "da": "Yes",
            "db": "Comment",
            "ea": "No",
            "eb": "Comment: One Person is missing",
            "fa": "No",
            "fb": "They are not in their aprons",
            "ga": "Yes",
            "gb": "Comment",
            "ha": "Yes",
            "hb": "Comment",
            "ia": "Yes",
            "ib": "Comment",
            "ja": "No",
            "jb": "Comment",
            "ka": "No",
            "kb": "Comment",
            "la": "No",
            "lb": "Comment",
            "ma": "No",
            "mb": "Comment",
            "na": "No",
            "nb": "Comment",
            "oa": "No",
            "ob": "Comment",
            "domain": "demo.ipolongo.org",
            "app_version": "pwa-1.0.1",
            "capture_date": "2025-04-24T10:40:00Z"
            }
        ]</span></td><td>
        An array of Drug Left on the device <span style="color: #f00; text-align: left;">
        array(
            array(
                "uid" => "uid-8723ff0c2a",
                "lgaid" => "100",
                "wardid" => "1000",
                "dpid" => "DP identifier",
                "comid" => "256",
                "userid" => "3",
                "latitude" => "8.4921",
                "longitude" => "7.5463",
                "sp" => "Mrs. Grace Danjuma",
                "aa" => "No",
                "ab" => "Comment: the net has finished",
                "ba" => "No",
                "bb" => "The team device has not been charged",
                "ca" => "No",
                "cb" => "Comment",
                "da" => "Yes",
                "db" => "Comment",
                "ea" => "No",
                "eb" => "Comment: One Person is missing",
                "fa" => "No",
                "fb" => "They are not in their aprons",
                "ga" => "Yes",
                "gb" => "Comment",
                "ha" => "Yes",
                "hb" => "Comment",
                "ia" => "Yes",
                "ib" => "Comment",
                "ja" => "No",
                "jb" => "Comment",
                "ka" => "No",
                "kb" => "Comment",
                "la" => "No",
                "lb" => "Comment",
                "ma" => "No",
                "mb" => "Comment",
                "na" => "No",
                "nb" => "Comment",
                "oa" => "No",
                "ob" => "Comment",
                "domain" => "demo.ipolongo.org",
                "app_version" => "pwa-1.0.1",
                "capture_date" => "2025-04-24T10:40:00Z"
            ),
            array(
                "uid" => "uid-5479be88a1",
                "lgaid" => "100",
                "wardid" => "1000",
                "dpid" => "DP identifier",
                "comid" => "256",
                "userid" => "3",
                "latitude" => "8.5290",
                "longitude" => "7.5600",
                "sp" => "Mr. Samuel Okeke",
                "aa" => "No",
                "ab" => "Comment: the net has finished",
                "ba" => "No",
                "bb" => "The team device has not been charged",
                "ca" => "No",
                "cb" => "Comment",
                "da" => "Yes",
                "db" => "Comment",
                "ea" => "No",
                "eb" => "Comment: One Person is missing",
                "fa" => "No",
                "fb" => "They are not in their aprons",
                "ga" => "Yes",
                "gb" => "Comment",
                "ha" => "Yes",
                "hb" => "Comment",
                "ia" => "Yes",
                "ib" => "Comment",
                "ja" => "No",
                "jb" => "Comment",
                "ka" => "No",
                "kb" => "Comment",
                "la" => "No",
                "lb" => "Comment",
                "ma" => "No",
                "mb" => "Comment",
                "na" => "No",
                "nb" => "Comment",
                "oa" => "No",
                "ob" => "Comment",
                "domain" => "demo.ipolongo.org",
                "app_version" => "pwa-1.0.1",
                "capture_date" => "2025-04-24T10:40:00Z"
            )
        )                                </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                <thead>
                    <tr>
                    <th>Field Name</th>
                    <th>Description</th>
                    <th>Data Type</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                    <td>uid</td>
                    <td>Client-side generated form-item unique identifier</td>
                    <td>String</td>
                    </tr>
                    <tr>
                    <td>lgaid</td>
                    <td>LGA identifier</td>
                    <td>Integer</td>
                    </tr>
                    <tr>
                    <td>wardid</td>
                    <td>Ward identifier</td>
                    <td>Integer</td>
                    </tr>
                    <tr>
                    <td>dpid</td>
                    <td>DP identifier</td>
                    <td>Integer</td>
                    </tr>
                    <tr>
                    <td>comid</td>
                    <td>Community identifier</td>
                    <td>Integer</td>
                    </tr>
                    <tr>
                    <td>userid</td>
                    <td>Server generated user identifier</td>
                    <td>Integer</td>
                    </tr>
                    <tr>
                    <td>latitude</td>
                    <td>Geographic coordinate latitude</td>
                    <td>Float</td>
                    </tr>
                    <tr>
                    <td>longitude</td>
                    <td>Geographic coordinate longitude</td>
                    <td>Float</td>
                    </tr>
                    <tr>
                    <td>sp</td>
                    <td>Name of the supervisor</td>
                    <td>String</td>
                    </tr>
                    <tr>
                    <td>aa</td>
                    <td>Does the distribution site have enough ITNs to complete the day\'s distribution?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>ab</td>
                    <td>Additional information regarding the availability of ITNs</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>ba</td>
                    <td>Is there a device adequately provisioned for distribution?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>bb</td>
                    <td>Additional information regarding device provisioning</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>ca</td>
                    <td>Is there a well-organized waiting area for beneficiaries?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>cb</td>
                    <td>Additional information about the waiting area</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>da</td>
                    <td>Does the site have a net properly hanging for beneficiaries to see?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>db</td>
                    <td>Additional information about the net hanging</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>ea</td>
                    <td>Are all team members present at the distribution site?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>eb</td>
                    <td>Additional information about team presence</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>fa</td>
                    <td>Are all team members correctly identified?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>fb</td>
                    <td>Additional information about team identification</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>ga</td>
                    <td>Is the flow of people well-organized at the distribution point?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>gb</td>
                    <td>Additional information about the flow of people</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>ha</td>
                    <td>Are crowd control personnel present at the distribution point?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>hb</td>
                    <td>Additional information about crowd control personnel</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>ia</td>
                    <td>Do team members provide key messages (purpose, use, care of the ITN)?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>ib</td>
                    <td>Additional information about providing key messages</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>ja</td>
                    <td>Do teams follow the distribution instructions (max 4 Net cards, 4 ITNs)?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>jb</td>
                    <td>Additional information about respecting distribution instructions</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>ka</td>
                    <td>Are ITNs removed from the plastic packaging before distribution?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>kb</td>
                    <td>Additional information about ITN packaging</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>la</td>
                    <td>Are Net cards being put in the Net card bag?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>lb</td>
                    <td>Additional information about Net card handling</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>ma</td>
                    <td>Is the distribution device used properly to scan Net cards?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>mb</td>
                    <td>Additional information about the device usage</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>na</td>
                    <td>Is the inventory control card (ICC) properly filled?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>nb</td>
                    <td>Additional information about the ICC</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>oa</td>
                    <td>Is waste being managed correctly at the distribution point?</td>
                    <td>Boolean</td>
                    </tr>
                    <tr>
                    <td>ob</td>
                    <td>Additional information about waste management</td>
                    <td>String (255 chars max)</td>
                    </tr>
                    <tr>
                    <td>domain</td>
                    <td>Domain name where the app is executed</td>
                    <td>String</td>
                    </tr>
                    <tr>
                    <td>app_version</td>
                    <td>Version of the currently executing app (e.g., pwa-1.0.1)</td>
                    <td>String</td>
                    </tr>
                    <tr>
                    <td>capture_date</td>
                    <td>Client-side form capture date</td>
                    <td>Date</td>
                    </tr>
                </tbody>
                </table>

    </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">i9c Form Bulk Data Upload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #                      - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">i9acForm Bulk Data Upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 80%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=1002</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>i9c Form Data</td>
                                <td><span style="color: #f00; text-align: left;">
        [
            {
                "uid": "uid-abc123xyz",
                "lgaid": "120",
                "wardid": "3021",
                "userid": "3",
                "latitude": "7.4156",
                "longitude": "3.8974",
                "aa": "Mr. Musa Ibrahim",
                "ab": "Yes",
                "ac": "Yes",
                "ad": "Yes",
                "ae: "3",
                "af": "Yes",
                "ag": "Not yet installed",
                "ah": "Too small for the sleeping space",
                "domain": "live.ipolongo.org",
                "app_version": "pwa-1.0.1",
                "capture_date": "2025-04-24T09:45:00Z"
            },
            {
                "uid": "uid-def456uvw",
                "lgaid": "120",
                "wardid": "3021",
                "userid": "3",
                "latitude": "7.4210",
                "longitude": "3.9011",
                "aa": "Mrs. Fatima Lawal",
                "ab": "Yes",
                "ac": "No",
                "ad": "No",
                "ae": "0",
                "af": "N/A",
                "ag": "Waiting for room renovation",
                "ah": "Children complain it’s hot inside",
                "domain": "live.ipolongo.org",
                "app_version": "pwa-1.0.1",
                "capture_date": "2025-04-24T10:20:00Z"
            }
            ]</span>                      
                                </td>
                                <td>
                        An array of Drug Left on the device
                                <span style="color: #f00; text-align: left;">
                array(
                    array(\'uid\' => \'uid-abc123xyz\', \'lgaid\' => \'120\', \'wardid\' => \'3021\',\'userid\' => \'3\',\'latitude\' => \'7.4156\',\'longitude\' => \'3.8974\',\'aa\' => \'Mr. Musa Ibrahim\',\'ab\' => \'Yes\',\'ac\' => \'Yes\', \'ad\' => \'Yes\', \'ae\' => \'3\', \'af\' => \'Yes\', \'ag\' => \'Not yet installed\', \'ah\' => \'Too small for the sleeping space\', \'domain\' => \'live.ipolongo.org\', \'app_version\' => \'pwa-1.0.1\',\'capture_date\' => \'2025-04-24T09:45:00Z\'),
                    array(\'uid\' => \'uid-def456uvw\',\'lgaid\' => \'120\', \'wardid\' => \'3021\', \'userid\' => \'3\', \'latitude\' => \'7.4210\', \'longitude\' => \'3.9011\', \'aa\' => \'Mrs. Fatima Lawal\', \'ab\' => \'Yes\', \'ac\' => \'No\', \'ad\' => \'No\', \'ae\' => \'0\', \'af\' => \'N/A\', \'ag\' => \'Waiting for room renovation\', \'ah\' => \'Children complain it’s hot inside\', \'domain\' => \'live.ipolongo.org\', \'app_version\' => \'pwa-1.0.1\', \'capture_date\' => \'2025-04-24T10:20:00Z\')
                );
                                </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>uid</td>
                                    <td>Client-side generated form-item unique identifier</td>
                                </tr>
                                <tr>
                                    <td>lgaid</td>
                                    <td>LGA identifier</td>
                                </tr>
                                <tr>
                                    <td>wardid</td>
                                    <td>Ward identifier</td>
                                </tr>
                                <tr>
                                    <td>comid</td>
                                    <td>Community identifier</td>
                                </tr>
                                <tr>
                                    <td>userid</td>
                                    <td>Server generated user identifier</td>
                                </tr>
                                <tr>
                                    <td>latitude</td>
                                    <td>Latitude of the geographical coordinates</td>
                                </tr>
                                <tr>
                                    <td>longitude</td>
                                    <td>Longitude of the geographical coordinates</td>
                                </tr>
                                <tr>
                                    <td>aa</td>
                                    <td>Name of Head of Household</td>
                                </tr>
                                <tr>
                                    <td>ab</td>
                                    <td>Did your household receive Net cards for the ITN distribution that is taking place? Yes/No</td>
                                </tr>
                                <tr>
                                    <td>ac</td>
                                    <td>Did anyone from your household take the Net cards to the distribution point written on the Net card? Yes/No</td>
                                </tr>
                                <tr>
                                    <td>ad</td>
                                    <td>Did you receive a ITN when you took your Net cards to the distribution site? (Yes/No)</td>
                                </tr>
                                <tr>
                                    <td>ae</td>
                                    <td>How many ITNs did you receive? (Ask to see the nets) </td>
                                </tr>
                                <tr>
                                    <td>af</td>
                                    <td>If hanging, did you air the nets? (Yes/No)</td>
                                </tr>
                                <tr>
                                    <td>ag</td>
                                    <td>If nets not hanging, why</td>
                                </tr>
                                <tr>
                                    <td>ah</td>
                                    <td>Do you have any problems with using the net? (Y/N)</td>
                                </tr>
                                <tr>
                                    <td>domain</td>
                                    <td>Domain name where the app is executed</td>
                                </tr>
                                <tr>
                                    <td>app_version</td>
                                    <td>Version of the currently executing app</td>
                                </tr>
                                <tr>
                                    <td>capture_date</td>
                                    <td>Client-side form capture date</td>
                                </tr>
                            </tbody>
                        </table>


    </pre>';

    echo '<pre>
                    #   <b style="color: #FF5722">End Process Form Bulk Data Upload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #                      - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">End Process Bulk Data Upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 80%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=1003</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>End Process Form Data</td>
                                <td><span style="color: #f00; text-align: left;">
                [
                    {
                        "uid": "uid-abc123xyz",
                        "lgaid": "101",
                        "wardid": "2002",
                        "comid": "356",
                        "userid": "7",
                        "latitude": "8.5120",
                        "longitude": "7.5432",
                        "aa": "3",
                        "ab": "1",
                        "ac": "2",
                        "ad": "Yes",
                        "ae": "Yes",
                        "af": "3",
                        "ag": "3",
                        "ah": "2",
                        "ai": "2",
                        "aj": "1",
                        "ak": "1",
                        "al": "Community health volunteer",
                        "am": "Weather too hot",
                        "domain": "demo.ipolongo.org",
                        "app_version": "pwa-1.0.1",
                        "capture_date": "2025-04-24T15:30:00Z"
                    },
                    {
                        "uid": "uid-def456ijk",
                        "lgaid": "101",
                        "wardid": "2002",
                        "comid": "456",
                        "userid": "7",
                        "latitude": "8.5432",
                        "longitude": "7.5876",
                        "aa": "4",
                        "ab": "2",
                        "ac": "3",
                        "ad": "No",
                        "ae": "Yes",
                        "af": "4",
                        "ag": "4",
                        "ah": "3",
                        "ai": "3",
                        "aj": "2",
                        "ak": "2",
                        "al": "Health center staff",
                        "am": "Net not available",
                        "domain": "demo.ipolongo.org",
                        "app_version": "pwa-1.0.1",
                        "capture_date": "2025-04-24T16:00:00Z"
                    }
                    ]</span>                      
                                </td>
                                <td>
        An array of End Process bulk data <span style="color: #f00; text-align: left;">
        array(
            array("uid" => "uid-abc123xyz", "lgaid" => "101", "wardid" => "2002", "comid" => "356", "userid" => "7", "latitude" => "8.5120", "longitude" => "7.5432", "aa" => "3", "ab" => "1", "ac" => "2", "ad" => "Yes", "ae" => "Yes", "af" => "3", "ag" => "3", "ah" => "2", "ai" => "2", "aj" => "1", "ak" => "1", "al" => "Community health volunteer", "am" => "Weather too hot", "domain" => "demo.ipolongo.org", "app_version" => "pwa-1.0.1", "capture_date" => "2025-04-24T15:30:00Z"), 
            array("uid" => "uid-hij789lmn", "lgaid" => "101", "wardid" => "2002", "comid" => "356", "userid" => "6", "latitude" => "8.6789", "longitude" => "7.4321", "aa" => "2", "ab" => "3", "ac" => "1", "ad" => "Yes", "ae" => "No", "af" => "2", "ag" => "2", "ah" => "1", "ai" => "1", "aj" => "1", "ak" => "1", "al" => "Local volunteers", "am" => "Other household members did not receive ITN", "domain" => "demo.ipolongo.org", "app_version" => "pwa-1.0.1", "capture_date" => "2025-04-24T17:15:00Z")
        );                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Description</th>
                                    <th>Data Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>uid</td>
                                    <td>Client-side generated form-item unique identifier</td>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <td>lgaid</td>
                                    <td>LGA identifier</td>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <td>wardid</td>
                                    <td>Ward identifier</td>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <td>comid</td>
                                    <td>Community identifier</td>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <td>userid</td>
                                    <td>Server generated user identifier</td>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <td>latitude</td>
                                    <td>Latitude of the geographical coordinates</td>
                                    <td>Decimal</td>
                                </tr>
                                <tr>
                                    <td>longitude</td>
                                    <td>Longitude of the geographical coordinates</td>
                                    <td>Decimal</td>
                                </tr>
                                <tr>
                                    <td>aa</td>
                                    <td>Number of children under 5 years</td>
                                    <td>Integer</td>
                                </tr>
                                <tr>
                                    <td>ab</td>
                                    <td>Number of pregnant women</td>
                                    <td>Integer</td>
                                </tr>
                                <tr>
                                    <td>ac</td>
                                    <td>Number of others</td>
                                    <td>Integer</td>
                                </tr>
                                <tr>
                                    <td>ad</td>
                                    <td>Net card issued out to the household by mobilization team</td>
                                    <td>Boolean</td>
                                </tr>
                                <tr>
                                    <td>ae</td>
                                    <td>Net card redeemed by the household</td>
                                    <td>Boolean</td>
                                </tr>
                                <tr>
                                    <td>af</td>
                                    <td>Total number of LLINs (Long Lasting Insecticide Nets) received by the household</td>
                                    <td>Integer</td>
                                </tr>
                                <tr>
                                    <td>ag</td>
                                    <td>Number of LLINs present in the household</td>
                                    <td>Integer</td>
                                </tr>
                                <tr>
                                    <td>ah</td>
                                    <td>Number of LLINs hanging over sleep areas</td>
                                    <td>Integer</td>
                                </tr>
                                <tr>
                                    <td>ai</td>
                                    <td>Number of children under 5 years that slept inside LLIN last night</td>
                                    <td>Integer</td>
                                </tr>
                                <tr>
                                    <td>aj</td>
                                    <td>Number of pregnant women that slept inside LLIN last night</td>
                                    <td>Integer</td>
                                </tr>
                                <tr>
                                    <td>ak</td>
                                    <td>Number of others that slept inside LLIN last night</td>
                                    <td>Integer</td>
                                </tr>
                                <tr>
                                    <td>al</td>
                                    <td>Source of information (e.g., community health volunteer, local volunteers)</td>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <td>am</td>
                                    <td>Reasons for non-use of LLIN</td>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <td>domain</td>
                                    <td>Domain name where the app is executed</td>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <td>app_version</td>
                                    <td>Version of the currently executing app</td>
                                    <td>String</td>
                                </tr>
                                <tr>
                                    <td>capture_date</td>
                                    <td>Client-side form capture date</td>
                                    <td>Datetime</td>
                                </tr>
                            </tbody>
                        </table>


    </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">5% Revisit Form Bulk Data Upload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #                      - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">5% Revisit Bulk Data Upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 80%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=1004</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>5% Revist Form Data</td>
                                <td><span style="color: #f00; text-align: left;">
                [
                    {
                        "uid": "uid-12345abcde",
                        "lgaid": "100",
                        "wardid": "1000",
                        "dpid": "DP001",
                        "comid": "256",
                        "userid": "3",
                        "latitude": "8.4921",
                        "longitude": "7.5463",
                        "aa": "Danjuma",
                        "ab": "Grace",
                        "ac": "Female",
                        "ad": "Amina",
                        "ae": "+2347012345678",
                        "af": "6",
                        "ag": "3",
                        "ah": "2",
                        "ai": "3",
                        "aj": "2",
                        "etoken_serial": "E1234567890",
                        "etoken_uuid": "uuid-xyz-001",
                        "domain": "demo.ipolongo.org",
                        "app_version": "pwa-1.0.1",
                        "capture_date": "2025-04-24T10:40:00Z"
                    },
                    {
                        "uid": "uid-12345abcde",
                        "lgaid": "100",
                        "wardid": "1000",
                        "dpid": "DP001",
                        "comid": "256",
                        "userid": "3",
                        "latitude": "8.4921",
                        "longitude": "7.5463",
                        "aa": "Danjuma",
                        "ab": "Grace",
                        "ac": "Female",
                        "ad": "Amina",
                        "ae": "+2347012345678",
                        "af": "6",
                        "ag": "3",
                        "ah": "2",
                        "ai": "3",
                        "aj": "2",
                        "etoken_serial": "E1234567890",
                        "etoken_uuid": "uuid-xyz-001",
                        "domain": "demo.ipolongo.org",
                        "app_version": "pwa-1.0.1",
                        "capture_date": "2025-04-24T10:40:00Z"
                    }
                ]</span>                      
                                </td>
                                <td>
        An array of 5% bulk data Upload <span style="color: #f00; text-align: left;">
        array(
            array("uid" => "uid-12345abcde", "lgaid" => "100", "wardid" => "1000", "dpid" => "DP001", "comid" => "256", "userid" => "3", "latitude" => "8.4921", "longitude" => "7.5463", "aa" => "Danjuma", "ab" => "Grace", "ac" => "Female", "ad" => "Amina", "ae" => "+2347012345678", "af" => "6", "ag" => "3", "ah" => "2", "ai" => "3", "aj" => "2", "etoken_serial" => "E1234567890", "etoken_uuid" => "uuid-xyz-001", "domain" => "demo.ipolongo.org", "app_version" => "pwa-1.0.1", "capture_date" => "2025-04-24T10:40:00Z"),
            array("uid" => "uid-12345abcde", "lgaid" => "100", "wardid" => "1000", "dpid" => "DP001", "comid" => "256", "userid" => "3", "latitude" => "8.4921", "longitude" => "7.5463", "aa" => "Danjuma", "ab" => "Grace", "ac" => "Female", "ad" => "Amina", "ae" => "+2347012345678", "af" => "6", "ag" => "3", "ah" => "2", "ai" => "3", "aj" => "2", "etoken_serial" => "E1234567890", "etoken_uuid" => "uuid-xyz-001", "domain" => "demo.ipolongo.org", "app_version" => "pwa-1.0.1", "capture_date" => "2025-04-24T10:40:00Z")
        )
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <thead>
                            <tr>
                            <th>Field Name</th>
                            <th>Description</th>
                            <th>Data Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <td>uid</td>
                            <td>(info) Client-side generated form-item unique identifier</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>lgaid</td>
                            <td>(info) LGA identifier</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>wardid</td>
                            <td>(info) Ward identifier</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>dpid</td>
                            <td>(info) DP identifier</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>comid</td>
                            <td>(info) Community identifier</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>userid</td>
                            <td>(info) Server generated user identifier</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>latitude</td>
                            <td>(info) Geo-coord. latitude</td>
                            <td>Float</td>
                            </tr>
                            <tr>
                            <td>longitude</td>
                            <td>(info) Geo-coord. longitude</td>
                            <td>Float</td>
                            </tr>
                            <tr>
                            <td>aa</td>
                            <td>Last Name of household head (What is the last name of the head of household?)</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>ab</td>
                            <td>First Name of household head (What is the first name of the head of household?)</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>ac</td>
                            <td>Gender (Please select gender of head of household)</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>ad</td>
                            <td>Name of Household Head’s Mother (Please fill in the first name of the head of household’s mother)</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>ae</td>
                            <td>Household Phone Number (What is the mobile number of the head of household)</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>af</td>
                            <td>Household Number of Family Members (How many people live together regularly in the household)</td>
                            <td>Integer</td>
                            </tr>
                            <tr>
                            <td>ag</td>
                            <td>Household Number of Sleeping Spaces (How many sleeping spaces are there in the household?)</td>
                            <td>Integer</td>
                            </tr>
                            <tr>
                            <td>ah</td>
                            <td>Number of Adult Females (How many adult females are there in the household?)</td>
                            <td>Integer</td>
                            </tr>
                            <tr>
                            <td>ai</td>
                            <td>Number of Adult Males (How many adult males are there in the household?)</td>
                            <td>Integer</td>
                            </tr>
                            <tr>
                            <td>aj</td>
                            <td>Number of Children (How many children are there in the household?)</td>
                            <td>Integer</td>
                            </tr>
                            <tr>
                            <td>etoken_serial</td>
                            <td>(info) Etoken serial</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>etoken_uuid</td>
                            <td>(info) Etoken UUID</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>domain</td>
                            <td>Domain name only where the app is executed</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>app_version</td>
                            <td>Version of the currently executing app (pwa-1.0.1)</td>
                            <td>String</td>
                            </tr>
                            <tr>
                            <td>capture_date</td>
                            <td>(info) Client side form capture date</td>
                            <td>DateTime</td>
                            </tr>
                        </tbody>
                        </table>


    </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">5% Revisit Supervisor Form</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #                      - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">5% Revisit Supervisor Data Upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 80%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=1007</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>i9c Form Data</td>
                                <td><span style="color: #f00; text-align: left;">
        [
            {
                "uid": "123e4567-e89b-12d3-a456-426614174000",
                "wardid": 1007,
                "lgaid": 526,
                "dpid": 2027,
                "comid": 3001,
                "userid": 21,
                "visit_date": "2022-06-01",   
                "latitude": "9.0765",
                "longitude": "7.3986",
                "name_of_collector": "John Doe Something something  
                "aa": "Yes", //Confirm if the community has been mobilized
                "ab": "Yes", //Did the 5% data collector visit this community?
                "ac": "Yes", //Is the household marked as having been visited by a 5% data collector? (Note that this is filled-in based on observation by supervisor) (RVT 1-10) 
                "ad": "Olaide Saka", //Name of HH Head [ Text ]
                "ae": "Yes", //Was the Household Registered and issued token slip(s)? 
                "af": "Yes", //Did the 5% data collector adhere to the HHs randomization plan?
                "comments": "This is a comment for the revisit form data point",
                "etoken_serial": "DK83932",
                "app_version": "v0.0.01",
                "domain": "demo.ipolongo.org"
            }
             
            ]</span>                      
                                </td>
                                <td>
                        An array of Drug Left on the device
                                <span style="color: #f00; text-align: left;">
                            array(
                                array(
                                    \'uid\' => \'123e4567-e89b-12d3-a456-426614174000\', \'wardid\' => 1007, \'lgaid\' => 526, \'dpid\' => 2027,
                                    \'comid\' => 3001, \'userid\' => 21, \'visit_date\' => \'2022-06-01\', \'latitude\' => \'9.0765\', \'longitude\' => \'7.3986\',
                                    \'name_of_collector\' => \'John Doe\', \'aa\' => \'Yes\', \'ab\' => \'Yes\', \'ac\' => \'Yes\',\'ad\' => \'Mdkamil Abdulazeez\',
                                    \'ae\' => \'Yes\', \'af\' => \'Yes\',\'comments\' => \'This is a comment for the revisit form data point\',
                                    \'etoken_serial\' => \'DK83932\', \'app_version\' => \'v0.0.01\', \'domain\' => \'5% Revisit\'
                                    ))
                                </span>
                                </td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>uid</td>
                                    <td>Client-side generated form-item unique identifier</td>
                                </tr>
                                <tr>
                                    <td>lgaid</td>
                                    <td>LGA identifier</td>
                                </tr>
                                <tr>
                                    <td>wardid</td>
                                    <td>Ward identifier</td>
                                </tr>
                                <tr>
                                    <td>comid</td>
                                    <td>Community identifier</td>
                                </tr>
                                <tr>
                                    <td>userid</td>
                                    <td>Server generated user identifier</td>
                                </tr>
                                <tr>
                                    <td>latitude</td>
                                    <td>Latitude of the geographical coordinates</td>
                                </tr>
                                <tr>
                                    <td>longitude</td>
                                    <td>Longitude of the geographical coordinates</td>
                                </tr>
                                <tr>
                                    <td>aa</td>
                                    <td>Name of Head of Household</td>
                                </tr>
                                <tr>
                                    <td>ab</td>
                                    <td>Did your household receive Net cards for the ITN distribution that is taking place? Yes/No</td>
                                </tr>
                                <tr>
                                    <td>ac</td>
                                    <td>Did anyone from your household take the Net cards to the distribution point written on the Net card? Yes/No</td>
                                </tr>
                                <tr>
                                    <td>ad</td>
                                    <td>Did you receive a ITN when you took your Net cards to the distribution site? (Yes/No)</td>
                                </tr>
                                <tr>
                                    <td>ae</td>
                                    <td>How many ITNs did you receive? (Ask to see the nets) </td>
                                </tr>
                                <tr>
                                    <td>af</td>
                                    <td>If hanging, did you air the nets? (Yes/No)</td>
                                </tr>
                                <tr>
                                    <td>ag</td>
                                    <td>If nets not hanging, why</td>
                                </tr>
                                <tr>
                                    <td>ah</td>
                                    <td>Do you have any problems with using the net? (Y/N)</td>
                                </tr>
                                <tr>
                                    <td>domain</td>
                                    <td>Domain name where the app is executed</td>
                                </tr>
                                <tr>
                                    <td>app_version</td>
                                    <td>Version of the currently executing app</td>
                                </tr>
                                <tr>
                                    <td>visit_date</td>
                                    <td>Client-side form capture date</td>
                                </tr>
                            </tbody>
                        </table>


    </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">CDD Monitoring Form Bulk Data Upload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #                      - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">CDD Monitoring Bulk Data Upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 80%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=1005</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>CDD Monitoring Form Data</td>
                                <td><span style="color: #f00; text-align: left;">
        [
            {
                    "uid":"",
                    "lgaid":"",
                    "wardid":"",
                    "dpid":"",
                    "periodid":"",
                    "userid":"",
                    "day":"",
                    "latitude":"",
                    "longitude":"",
                    "aa":"",
                    "ab":"",
                    "ba":"",
                    "bb":"",
                    "ca":"",
                    "cb":"",
                    "da":"",
                    "db":"",
                    "ea":"",
                    "eb":"",
                    "fa":"",
                    "fb":"",
                    "ga":"",
                    "gb":"",
                    "ha":"",
                    "hb":"",
                    "ia":"",
                    "ib":"",
                    "ja":"",
                    "jb":"",
                    "ka":"",
                    "kb":"",
                    "la":"",
                    "lb":"",
                    "ma":"",
                    "mb":"",
                    "na":"",
                    "nb":"",
                    "oa":"",
                    "ob":"", 
                    "pa":"", 
                    "pb":"", 
                    "q":"", 
                    "r":"", 
                    "s":"",
                    "domain":"", 
                    "app_version":"", 
                    "capture_date"
            }
        ]</span>                      
                                </td>
                                <td>
        An array of CDD Monitoring Form Data Upload <span style="color: #f00; text-align: left;">
        array(
            array("uid"=>"", "lgaid"=>"", "wardid"=>"", "dpid"=>"", "periodid"=>"", "userid"=>"", "day"=>"",
            "latitude"=>"", "longitude"=>"", "aa"=>"", "ab"=>"", "ba"=>"", "bb"=>"", "ca"=>"", "cb"=>"",
            "da"=>"", "db"=>"", "ea"=>"", "eb"=>"", "fa"=>"", "fb"=>"", "ga"=>"", "gb"=>"", "ha"=>"",
            "hb"=>"", "ia"=>"", "ib"=>"", "ja"=>"", "jb"=>"", "ka"=>"", "kb"=>"", "la"=>"", "lb"=>"",
            "ma"=>"", "mb"=>"", "na"=>"", "nb"=>"", "oa"=>"", "ob"=>"", "pa"=>"", "pb"=>"", "q"=>"", 
            "r"=>"", "s"=>"", "domain"=>"", "app_version"=>"", "capture_date"),
        )
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <thead>
                            <tr>
                            <th>Field Name</th>
                            <th>Description</th>
                            <th>Data Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>uid</td>
                                <td>(info) Client-side generated form-item unique identifier</td>
                                <td>String</td>
                            </tr>
                            <tr>
                                <td>lgaid</td>
                                <td>(info) LGA identifier</td>
                                <td>String</td>
                            </tr>
                        </tbody>
                        </table>
    </pre>';


    echo '<pre>
                    #   <b style="color: #FF5722">HFW Monitoring Form Bulk Data Upload</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #                      - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">HFW Monitoring Bulk Data Upload (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 80%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=1006</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                            <tr>
                                <td>2</td>HFW Monitoring Form Data</td>
                                <td><span style="color: #f00; text-align: left;">
                [
            {
           "uid": "","lgaid": "","wardid": "","dpid": "","periodid": "","userid": "","day": "","latitude": "","longitude": "",
            "aa": "","ab": "","ba": "","bb": "","ca": "","cb": "","da": "","db": "","ea": "","eb": "","fa": "","fb": "","ga": "","gb": "",
            "ha": "","hb": "","ia": "","ib": "","ja": "","jb": "","ka": "","kb": "","la": "","lb": "",
            "m1a": "", "m1b": "", "m2a": "", "m2b": "", "m3a": "", "m3b": "", "m4a": "", "m4b": "",
            "n1a": "", "n1b": "", "n2a": "", "n2b": "", "n3a": "", "n3b": "", "n4a": "", "n4b": "",
            "n5a": "", "n5b": "", "n6a": "", "n6b": "", "o1a": "", "o1b": "",  "o2a": "", "o2b": "",
            "o3a": "", "o3b": "", "pa": "", "pb": "", "q1a": "", "q1b": "", "q2a": "","q2b": "", "ra": "",
            "rb": "", "s": "", "t": "", "v": "", "domain" : "", "app_version":"", "capture_date":""
            }
                ]</span>                      
                                </td>
                                <td>
        An array of CDD Monitoring Form Data Upload <span style="color: #f00; text-align: left;">
        array(
            array("uid"=> "","lgaid"=> "","wardid"=> "","dpid"=> "","periodid"=> "","userid"=> "","day"=> "","latitude"=> "","longitude"=> "",
            "aa"=> "","ab"=> "","ba"=> "","bb"=> "","ca"=> "","cb"=> "","da"=> "","db"=> "","ea"=> "","eb"=> "","fa"=> "","fb"=> "","ga"=> "","gb"=> "",
            "ha"=> "","hb"=> "","ia"=> "","ib"=> "","ja"=> "","jb"=> "","ka"=> "","kb"=> "","la"=> "","lb"=> "",
            "m1a"=> "", "m1b"=> "", "m2a"=> "", "m2b"=> "", "m3a"=> "", "m3b"=> "", "m4a"=> "", "m4b"=> "",
            "n1a"=> "", "n1b"=> "", "n2a"=> "", "n2b"=> "", "n3a"=> "", "n3b"=> "", "n4a"=> "", "n4b"=> "",
            "n5a"=> "", "n5b"=> "", "n6a"=> "", "n6b"=> "", "o1a"=> "", "o1b"=> "",  "o2a"=> "", "o2b"=> "",
            "o3a"=> "", "o3b"=> "", "pa"=> "", "pb"=> "", "q1a"=> "", "q1b"=> "", "q2a"=> "","q2b"=> "", "ra"=> "",
            "rb"=> "", "s"=> "", "t"=> "", "v"=> "", "domain" => "", "app_version"=>"", "capture_date"=>""),
        )
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <thead>
                            <tr>
                            <th>Field Name</th>
                            <th>Description</th>
                            <th>Data Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>uid</td>
                                <td>(info) Client-side generated form-item unique identifier</td>
                                <td>String</td>
                            </tr>
                            <tr>
                                <td>lgaid</td>
                                <td>(info) LGA identifier</td>
                                <td>String</td>
                            </tr>
                        </tbody>
                        </table>
    </pre>';



    echo '<pre>
                <h1 style="color: #7367f0"> General API: STARTS</h1>
                <hr style="height: 10px; background-color: #7367f0">
            </pre> ';

    echo '<pre>
                    #   <b style="color: #FF5722">Get Commodity Master List</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get Commodity List (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen006</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login, to be passed via header</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>commodity_id</td>
                                <td>Unique Id of Commmodity</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>name</td>
                                <td>The title or name of Commodity</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>description</td>
                                <td>The description or details of Commodity</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>com_value</td>
                                <td>The Value or Quantity of the Commodity</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>min_age</td>
                                <td>The Minimumm Child Age that can take this Drug (In Months)</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>max_age</td>
                                <td>The Maximum Child Age that can take this Drug (In Months)</td>
                            </tr>
                        </table>
                </pre>';
    echo '<pre>
                    #   <b style="color: #FF5722">Get Reason Master list</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get Reason list (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen007</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>reason</td>
                                <td>Reason</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>category</td>
                                <td>Category</td>
                            </tr>
                        </table>
                </pre>';
    echo '<pre>
                    #   <b style="color: #FF5722">Get Active Period Master</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get Active Period (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen008</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>periodid</td>
                                <td>Active Period Unique ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>title</td>
                                <td>Active Period Title</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>start_date</td>
                                <td>Active Period Start Date</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>end_date</td>
                                <td>Active Period End Date</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>extension_age</td>
                                <td>Age of Drug Use Extension</td>
                            </tr>
                        </table>
                </pre>';

    echo '<pre>

                    #   <b style="color: #FF5722">Get MasterHousehold using dpid</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get MasterHousehold using dpid (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen009</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"dpid": 3001}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>hhid</td>
                                <td>Unique Household ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>dpid</td>
                                <td>Health Facility or DP ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>hh_token</td>
                                <td>Household e-token Serial</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>hoh_name</td>
                                <td>Household Head Name</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>hoh_phone</td>
                                <td>Household Head Phone Number</td>
                            </tr>
                        </table>

                </pre>';

    echo '<pre>

                    #   <b style="color: #FF5722">Get MasterChild using dpid</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get MasterChild using dpid (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen010</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"dpid": 3001}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>child_id</td>
                                <td>Child Unique ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>hh_token</td>
                                <td>Household e-token Serial</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>beneficiary_id</td>
                                <td>Unique Beneficiary e-Token ID</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>dpid</td>
                                <td>Health Facility or DP ID</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>name</td>
                                <td>Child or Beneficiary Name</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>gender</td>
                                <td>Child or Beneficiary Gender</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>dob</td>
                                <td>Child or Beneficiary Date of Birth</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>last_visit_periodid</td>
                                <td>Last Visit Period ID or Cycle ID</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>last_visit_period</td>
                                <td>Last Visit Period Cycle</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>last_visit_date</td>
                                <td>Last Visit Date</td>
                            </tr>
                        </table>

                </pre>';

    echo '<pre>

                    #   <b style="color: #FF5722">Get CDD Lead Master List using dpid</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get CDD Lead Master List using dpid (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen011</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{"dpid": 3001}</b> - body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>userid</td>
                                <td>CDD Lead Unique User ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>loginid</td>
                                <td>CDD Lead Login ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>first</td>
                                <td>CDD Lead First Name</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>middle</td>
                                <td>CDD Lead Middle Name</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>last</td>
                                <td>CDD Lead Last Name</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>phone</td>
                                <td>CDD Lead Phone Number</td>
                            </tr>
                        </table>

                </pre>';

    echo '<pre>

                    #   <b style="color: #FF5722">Get Referrer Master Lists using the DP ID and Period ID</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get Referrer Master Lists using the DP ID and Period ID (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen012</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{
                                    "dpid": 3001,
                                    "periodid": 3
                                }</b>      -    body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>adm_id</td>
                                <td>Drug Administration Unique ID</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>dpid</td>
                                <td>Health Facility or DP ID</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>periodid</td>
                                <td>Current Visit of Cycle ID</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>name</td>
                                <td>Full name of the beneficiary or child that was reffered</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>beneficiary_id</td>
                                <td>Beneficiary or the Child Unique ID</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>gender</td>
                                <td>Beneficiary Gender</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>dob</td>
                                <td>Beneficiary Date of Birth</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>collected_date</td>
                                <td>Visit Date</td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>not_eligible_reason</td>
                                <td>Non Eligibility Reason</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>referrer_cdd</td>
                                <td>Name of CDD Lead that reffered the child or beneficiary</td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>referrer_cdd_loginid</td>
                                <td>CDD Lead Login Id</td>
                            </tr>
                        </table>

                </pre>';

    echo '<pre>

                    #   <b style="color: #FF5722">Get Combine Geo Location using lgaid, geo_level, geo_leve_id</b>
                    #   Result Code Documentation (result_code)
                    #   result_code - 200 : Success
                    #   result_code - 401 : Error (Unauthorized User/Unauthorized)
                    #               - 400 : Error
                    #   message - : success
                    #   <span style="color: #6A1B9A">Get Combine Geo Location using lgaid, geo_level, geo_leve_id (API in the body)</span>
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;"><span style="color: #000">' . $server_claim . '?qid=gen013</span>  (POST Request in the body)</th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>jwt</td>
                                <td><span style="color: #f00; text-align: left;">{Get value after login}</span></td>
                                <td>Generated token after successful login</td>
                            </tr>
                        </table>
                                <b style="color:#7367f0">{
                                    "lgaid": 3001,
                                    "geo_level": "lga",
                                    "geo_level_id: 1000
                                }</b>      -    body data
                        <table class="table table-bordered table-striped mt-0 pt-0 mb-0"  style="color: #7367f0; width: 60%; margin-left: 90px;">
                        <tr>
                            <th colspan="4"><b style="color: #f00; text-align: left;">Data Result: </b></th>
                        </tr>
                            <tr class="table-primary">
                                <th width="40px">#</th>
                                <th>KEY</th>
                                <th>DESCRIPTION</th>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>lgaid</td>
                                <td>LGA ID, if the user is at LGA Level, else it can be empty</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>geo_level</td>
                                <td>User Geo Leve. Required</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>geo_level_id</td>
                                <td>Geo Level ID. Required</td>
                            </tr>
                        </table>

                </pre>';


    ?>
</body>

</html>