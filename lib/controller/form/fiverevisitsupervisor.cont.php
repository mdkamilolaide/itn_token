<?php
namespace Form;
use DbHelper;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
#
class FiveRevisitSupervisor
{
    private $db;

    public function __construct()
    {
        $this->db = GetMysqlDatabase();
    }

    public function BulkSave($bulk_data){
        /*
        sample data structure
        $data = [
            'uid' => '123e4567-e89b-12d3-a456-426614174000',
            'wardid' => 1007,
            'lgaid' => 526,
            'dpid' => 2027,
            'comid' => 3001,
            'userid' => 21,
            'visit_date' => '2022-06-01',   //
            'latitude' => '9.0765',
            'longitude' => '7.3986',
            'name_of_collector' => 'John Doe Something something [ Name of 5% Data Collector]', //[ Name of 5% Data Collector]
            'aa' => 'Confirm if the community has been mobilized [ Yes | No ]',
            'ab' => 'Did the 5% data collector visit this community? [ Yes | No ]',
            'ac' => 'Is the household marked as having been visited by a 5% data collector? (Note that this is filled-in based on observation by supervisor) (RVT 1-10) [ Yes | No ]',
            'ad' => 'Name of HH Head [ Text ]',
            'ae' => 'Was the Household Registered and issued token slip(s)? [ Yes | No ]',
            'af' => 'Did the 5% data collector adhere to the HHs randomization plan? [ Yes | No ]',
            'comments' => 'This is a comment for the revisit form data point',
            'etoken_serial' => 'DK83932',
            'app_version' => 'v0.0.01',
            'domain' => '5% Revisit'
        ];
    */
        $id_list = array();     #   List of inserted IDs to be returned
        $date = getNowDbDate();
        if (count($bulk_data) < 1) {
            $this->db->ErrorMessage = "Invalid bulk data";
            return $id_list;
        }
        #else
        #   init transaction
        $this->db->beginTransaction();
        $counter = 0;
        foreach ($bulk_data as $a) {
            $etoken_serial = array_key_exists("etoken_serial", $a) ? $a['etoken_serial'] : '';
            $query = "INSERT INTO `mo_form_five_revisit_supervisor` 
            (`uid`,`wardid`,`dpid`,`lgaid`,`comid`,`userid`,`latitude`,`longitude`,`visit_date`,`name_of_collector`,
            `aa`,`ab`,`ac`,`ad`,`ae`,`af`,`ag`,`comment`,`etoken_serial`,`domain`,`app_version`,`created`) VALUES 
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $this->db->executeTransaction($query, array($a['uid'], $a['wardid'], $a['dpid'], $a['lgaid'], $a['comid'], $a['userid'], $a['latitude'], 
            $a['longitude'], $a['visit_date'], $a['name_of_collector'], $a['aa'], $a['ab'], $a['ac'], $a['ad'], $a['ae'], $a['af'], $a['ag'], 
            $a['comment'], $etoken_serial, $a['domain'], $a['app_version'], $date));
            $id = $this->db->executeTransactionLastId();
            if ($id) {
                $id_list[] = array('uid' => $a['uid'], 'id' => $id);
                $counter++;
            } else {
                # its duplicates and unable to save
                $id_list[] = array('uid' => $a['uid'], 'id' => 0);
            }
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if (strlen($error_message) > 0) {
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\n5% Revisit Form error, message: $error_message\r\nData:" . json_encode($bulk_data) . "\r\n$date\r\n";
                WriteToFile($error_file_name, $error_to_write);
            }
        }
        #   Complete transaction
        $this->db->commitTransaction();
        #   Return array list
        return $id_list;
    }
}