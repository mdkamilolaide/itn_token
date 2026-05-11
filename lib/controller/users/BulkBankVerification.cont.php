<?php

namespace Users;

use DbHelper;

/*
     *  USAGE
     *  ++++++
     *  Init object
     *  $vf = new BankVerify($ac_no, $bank_code);
        # Run verification
        # if($vf->Run()){
            # verification successfull
            # get data
            $account_number = $vf->ResponseData['account_number];
            $account_name = $vf->ResponseData['account_name];
          }
          else{
            # account verification failed
            # error message
            echo $vf->ResponseMessage;
          }
     */

class BankVerify
{
    ####
    private $ac_no, $bk_code;
    ####
    public $Response = false;
    public $ResponseData = array();
    public $ResponseMessage = "";
    ####
    public function __construct($account_number, $bank_code)
    {
        $this->ac_no = $account_number;
        $this->bk_code = $bank_code;
    }
    private function CheckError()
    {
        if (strlen($this->ac_no) != 10) {
            $this->ResponseMessage = "Invalid account number, less or greater than 10 char length";
            return true;
        }
        if (strlen($this->bk_code) != 3) {
            $this->ResponseMessage .= " Invalid bank code";
            return true;
        }
    }
    ####
    public function Run()
    {
        if ($this->CheckError()) return;
        global $config_paystack_secret_key;
        if (empty($config_paystack_secret_key)) {
            $this->ResponseMessage = "Paystack API key not configured (\$config_paystack_secret_key in lib/config.php).";
            return;
        }
        #
        $curl = curl_init();
        $params = "https://api.paystack.co/bank/resolve?account_number=" . $this->ac_no . "&bank_code=" . $this->bk_code;
        curl_setopt_array($curl, array(
            CURLOPT_URL => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $config_paystack_secret_key,
                "Cache-Control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->ResponseMessage = "Network Error: " . $err;
            return;
        }
        #   Convert responce to array
        $json = json_decode($response, true);
        $this->Response = $json['status'];
        $this->ResponseMessage = $json['message'];
        if ($json['status']) {
            $this->ResponseData = $json['data']; ##[account_number, account_name, bank_id]
        }
        #
        $this->Response;
    }
}

class BulkBankVerification
{
    private $db;
    #   Limit per time
    public $limit = 2;
    #   Where on the database to start
    public $startPointer = 0;
    #   Get by geo-level
    public $geoLevel = "";
    public $geoLevelId = "";
    #
    #
    #
    public $rep_total_error = 0;
    public $rep_total_success = 0;
    public $rep_total_process = 0;
    public $rep_trans_report = array();
    #
    public function __construct()
    {
        $this->db = GetMysqlDatabase();
    }
    ##
    private function GetNeeded()
    {
        $limit = $this->limit;
        $pointer = $this->startPointer;
        $limiter = ($this->startPointer) ? "LIMIT $pointer,$limit" : "LIMIT $limit";
        return DbHelper::Table("SELECT
            usr_login.loginid,
            usr_finance.userid,
            usr_finance.bank_name,
            usr_finance.bank_code,
            usr_finance.account_no,
            usr_finance.account_name,
            usr_login.geo_level,
            usr_login.geo_level_id,
            CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_finance
            INNER JOIN usr_login ON usr_finance.userid = usr_login.userid
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            WHERE
            usr_finance.is_verified = 0 AND
            usr_finance.account_no IS NOT NULL AND
            usr_finance.bank_code IS NOT NULL
            ORDER BY
            usr_finance.id ASC
            $limiter");
    }
    public function CountNeeded()
    {
        return DbHelper::GetScalar("SELECT COUNT(*)
            FROM
            usr_finance
            WHERE
            usr_finance.is_verified = 0 AND
            usr_finance.account_no IS NOT NULL AND
            usr_finance.bank_code IS NOT NULL");
    }
    private function GetUnverified()
    {
        $limit = $this->limit;
        $pointer = $this->startPointer;
        $limiter = ($this->startPointer) ? "LIMIT $pointer,$limit" : "LIMIT $limit";
        return DbHelper::Table("SELECT
            usr_login.loginid,
            usr_finance.userid,
            usr_finance.bank_name,
            usr_finance.bank_code,
            usr_finance.account_no,
            usr_finance.account_name,
            usr_login.geo_level,
            usr_login.geo_level_id
            FROM
            usr_finance
            INNER JOIN usr_login ON usr_finance.userid = usr_login.userid
            WHERE
            usr_finance.verification_status != 'success' AND
            usr_finance.account_no IS NOT NULL AND
            usr_finance.bank_code IS NOT NULL
            ORDER BY
            usr_finance.id ASC
            $limiter");
    }
    #   Verfy bank name and username
    private function VerifyName($source, $target)
    {
        $source_name = $source;
        $target_name = $target;
        #      Turn source into array
        if ($source_name) {
            $source_array = explode(" ", $source_name);
            #count to know list
            if (count($source_array)) {
                #begin comparing
                $success_counter = 0;
                foreach ($source_array as $name) {
                    # find name in target
                    if (strpos(strtolower($target_name), strtolower($name)) !== false) {
                        $success_counter++;
                    }
                }
                #
                #   if success counter is more than 2
                if ($success_counter >= 2) {
                    return true;
                }
            }
        }
        return false;
    }
    public function CountUnverified()
    {
        return DbHelper::GetScalar("SELECT COUNT(*)
            FROM
            usr_finance
            WHERE
            usr_finance.verification_status != 'success' AND
            usr_finance.account_no IS NOT NULL AND
            usr_finance.bank_code IS NOT NULL");
    }
    private function GetGeoLocation($geoLevel, $geoId)
    {
        $limit = $this->limit;
        $pointer = $this->startPointer;
        $limiter = ($this->startPointer) ? "LIMIT $pointer,$limit" : "LIMIT $limit";
        return DbHelper::Table("SELECT
            usr_login.loginid,
            usr_finance.userid,
            usr_finance.bank_name,
            usr_finance.bank_code,
            usr_finance.account_no,
            usr_finance.account_name,
            usr_login.geo_level,
            usr_login.geo_level_id
            FROM
            usr_finance
            INNER JOIN usr_login ON usr_finance.userid = usr_login.userid
            WHERE
            usr_finance.is_verified = 0 AND
            usr_finance.account_no IS NOT NULL AND
            usr_finance.bank_code IS NOT NULL AND
            usr_login.geo_level = '$geoLevel' AND 
            usr_login.geo_level_id = $geoId
            ORDER BY
            usr_finance.id ASC
            $limiter");
    }
    public function CountGeoLocation($geoLevel, $geoId)
    {
        return DbHelper::GetScalar("SELECT COUNT(*)
            FROM
            usr_finance
            WHERE
            usr_finance.is_verified = 0 AND
            usr_finance.account_no IS NOT NULL AND
            usr_finance.bank_code IS NOT NULL AND
            usr_login.geo_level = '$geoLevel' AND 
            usr_login.geo_level_id = $geoId");
    }
    private function GetNeededTemp()
    {
        $limit = $this->limit;
        $pointer = $this->startPointer;
        $limiter = ($this->startPointer) ? "LIMIT $pointer,$limit" : "LIMIT $limit";
        return DbHelper::Table("SELECT
            usr_temp.id,
            usr_temp.Account_Name,
            usr_temp.BANK,
            usr_temp.`CODE`,
            usr_temp.ACCOUNT_NUMBER
            FROM
            usr_temp
            WHERE
            usr_temp.is_verified = 0 AND
            usr_temp.ACCOUNT_NUMBER IS NOT NULL AND
            usr_temp.`CODE` IS NOT NULL
            ORDER BY
            usr_temp.id ASC
            $limiter");
    }
    public function CountNeededTemp()
    {
        return DbHelper::GetScalar("SELECT COUNT(*)
            FROM
            usr_temp
            WHERE
            usr_temp.is_verified = 0 AND
            usr_temp.ACCOUNT_NUMBER IS NOT NULL AND
            usr_temp.`CODE` IS NOT NULL");
    }
    public function GetStatus()
    {
        return DbHelper::Table("SELECT
            usr_finance.verification_status AS `status`,
            Count(usr_finance.id) AS total
            FROM
            usr_finance
            GROUP BY
            usr_finance.verification_status");
    }
    ##
    public function Run($type = "")
    {
        #   Get list

        $data = array();     #   create data holder variable
        #   define type of verification to pick the right one
        #   type = 'unverified' is to pick all wheather verified before or not
        #   empty = to pick only the previously unverified
        if ($type == 'unverified') {
            $data = $this->GetUnverified();
        } elseif ($type == 'geo-level') {
            $data = $this->GetGeoLocation($this->geoLevel, $this->geoLevelId);
        } else {
            $data = $this->GetNeeded();
        }
        #
        if (count($data)) {
            # Start transactions
            $this->db->beginTransaction();
            $err_counter = 0;
            $suc_counter = 0;
            #   [loginid,userid, bank_name. bank_code, account_no, account_name,geo_level,geo_level_id, fullname]
            $report = array();
            foreach ($data as $r) {
                #   
                $vf = new BankVerify($r['account_no'], $r['bank_code']);
                $vf->Run();
                if ($vf->Response) {
                    #   run successful
                    $message = $vf->ResponseMessage;
                    $account_number = $vf->ResponseData['account_number'];
                    $account_name = $vf->ResponseData['account_name'];
                    $date = getNowDbDate();
                    #   Verify Name
                    $verification_status = $this->VerifyName($r['fullname'], $account_name) ? 'success' : 'warning';
                    #   Write success into the database
                    $s_query = "UPDATE `usr_finance` SET `is_verified`=1, 
                        `verification_count`=`verification_count`+1, 
                        `verification_message`=?, 
                        `verified_account_name`=?, 
                        `verification_status`=?, 
                        `last_verified_date`=?
                        WHERE `userid`=?";
                    #   Execute
                    $this->db->executeTransaction($s_query, array($message, $account_name, $verification_status, $date, $r['userid']));
                    #   print success
                    $report[] = array(
                        'userid' => $r['userid'],
                        'loginid' => $r['loginid'],
                        'bank' => $r['bank_name'],
                        'status' => 'success',
                        'account_name' => $account_name,
                        'account_number' => $account_number,
                        'message' => $message,
                        'geo-level' => $r['geo_level'],
                        'geo_level_id' => $r['geo_level_id']
                    );
                    $suc_counter++;
                } else {
                    #   run failed | write failure into the database
                    $message = $vf->ResponseMessage;
                    $date = getNowDbDate();
                    $e_query = "UPDATE `usr_finance` SET `is_verified`=1, 
                        `verification_count`=`verification_count`+1, 
                        `verification_message`=?,
                        `verification_status`='failed', 
                        `last_verified_date`=?
                        WHERE `userid`=?";
                    $this->db->executeTransaction($e_query, array($message, $date, $r['userid']));
                    #   print failure
                    $report[] = array(
                        'userid' => $r['userid'],
                        'loginid' => $r['loginid'],
                        'bank' => $r['bank_name'],
                        'status' => 'success',
                        'account_name' => 'NaN',
                        'account_number' => 'NaN',
                        'message' => $message,
                        'geo-level' => $r['geo_level'],
                        'geo_level_id' => $r['geo_level_id']
                    );
                    $err_counter++;
                }
            }
            $this->rep_trans_report = $report;
            $this->rep_total_error = $err_counter;
            $this->rep_total_success = $suc_counter;
            $this->rep_total_process = count($data);
            #
            #   Complete 
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
        }
    }
    ##
    public function RunTemp($type = "")
    {
        #   Get list
        #   
        $data = array();     #   create data holder variable
        #   define type of verification to pick the right one
        #   type = 'unverified' is to pick all wheather verified before or not
        #   empty = to pick only the previously unverified
        if ($type == 'unverified') {
            //$data = $this->GetUnverified();
        } elseif ($type == 'geo-level') {
            //$data = $this->GetGeoLocation($this->geoLevel, $this->geoLevelId);
        } else {
            $data = $this->GetNeededTemp();
        }
        #
        if (count($data)) {
            # Start transactions
            $this->db->beginTransaction();
            $err_counter = 0;
            $suc_counter = 0;
            #   [loginid,userid, bank_name. bank_code, account_no, account_name,geo_level,geo_level_id]
            $report = array();
            foreach ($data as $r) {
                #   
                $vf = new BankVerify($r['ACCOUNT_NUMBER'], $r['CODE']);
                $vf->Run();
                if ($vf->Response) {
                    #   run successful
                    $message = $vf->ResponseMessage;
                    $account_number = $vf->ResponseData['account_number'];
                    $account_name = $vf->ResponseData['account_name'];
                    $date = getNowDbDate();
                    #   Write success into the database
                    $s_query = "UPDATE `usr_temp` SET `is_verified`=1, 
                        `verification_count`=`verification_count`+1, 
                        `verification_message`=?, 
                        `verified_bank_name`=?, 
                        `verification_status`='success', 
                        `last_verified_date`=?
                        WHERE `id`=?";
                    #   Execute
                    $this->db->executeTransaction($s_query, array($message, $account_name, $date, $r['id']));
                    #   print success
                    $report[] = array(
                        'id' => $r['id'],
                        'Account_Name' => $r['Account_Name'],
                        'bank' => $r['BANK'],
                        'status' => 'success',
                        'account_name' => $account_name,
                        'account_number' => $account_number,
                        'message' => $message
                    );
                    $suc_counter++;
                } else {
                    #   run failed | write failure into the database
                    $message = $vf->ResponseMessage;
                    $date = getNowDbDate();
                    $e_query = "UPDATE `usr_temp` SET `is_verified`=1, 
                        `verification_count`=`verification_count`+1, 
                        `verification_message`=?,
                        `verification_status`='failed', 
                        `last_verified_date`=?
                        WHERE `id`=?";
                    $this->db->executeTransaction($e_query, array($message, $date, $r['id']));
                    #   print failure
                    $report[] = array(
                        'id' => $r['id'],
                        'Account_Name' => $r['Account_Name'],
                        'bank' => $r['BANK'],
                        'status' => 'failed',
                        'account_name' => 'NaN',
                        'account_number' => 'NaN',
                        'message' => $message
                    );
                    $err_counter++;
                }
            }
            $this->rep_trans_report = $report;
            $this->rep_total_error = $err_counter;
            $this->rep_total_success = $suc_counter;
            $this->rep_total_process = count($data);
            #
            #   Complete 
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
        }
    }
}
