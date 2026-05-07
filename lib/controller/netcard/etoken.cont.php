<?php

    namespace Netcard;

    use DbHelper;

    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');

    class Etoken
    {
        private $length;
        private $deviceid;
        private $db;
        private $max = 2000;
        #
        private $etokenBatchId;
        private $etokenBatchNo;
        #
        #
        #
        public function __construct($device_id, $length=100)
        {
            if($length < 1)
            {
                $this->length = 0;
            }
            elseif($length > $this->max)
            {
                $this->length = $this->max;
            }
            else
            {
                $this->length = $length;
            }
            $this->deviceid = $device_id;
            #
            $this->db = GetMysqlDatabase();
        }
        public function Generate()
        {
            #   Generate token batch first
            $batch_data = $this->CreateBatch();
            #
            $counter = 0;
            #   
            if($batch_data)
            {
                #   Prepare query
                $query = "INSERT INTO nc_token (`batchid`,`batch_no`,`uuid`, `status`,`status_code`) VALUES (?, ?, ?, ?, ?)";
                $batchid = $batch_data['batchid'];
                $batch_no = $batch_data['batch_no'];
                
                #   Init transaction
                $this->db->beginTransaction();
                for($a=0;$a<$this->length;$a++)
                {
                    $qdata = array($batchid, $batch_no, generateUUID(),'pending',2);
                    $this->db->executeTransaction($query,$qdata);
                    ##  Update the serial number
                    $tokenid = $this->db->executeTransactionLastId();
                    $serial_no = GenerateCodeAlphabet(2).str_pad((string)($tokenid ?? 0), 5, '0', STR_PAD_LEFT);
                    #   Update the serial number
                    $this->db->executeTransaction("UPDATE nc_token SET `serial_no`='$serial_no' WHERE `tokenid`=$tokenid LIMIT 1",array());
                    $counter++;
                }
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
            }
            #   return total data generated
            return $this->getBatch($batchid);
        }
        public function GenerateLite()
        {
            #   Generate token batch first
            $batch_data = $this->CreateBatch();
            #
            $counter = 0;
            #   
            if($batch_data)
            {
                #   Prepare query
                $query = "INSERT INTO nc_token (`uuid`,`status`,`status_code`) VALUES (?, ?, ?)";
                
                $etoken_data = [];
                #   Init transaction
                $this->db->beginTransaction();
                for($a=0;$a<$this->length;$a++)
                {
                    $qdata = array(generateUUID(),'pending',2);
                    $this->db->executeTransaction($query,$qdata);
                    ##  Update the serial number
                    $tokenid = $this->db->executeTransactionLastId();
                    $serial_no = GenerateCodeAlphabet(2).str_pad((string)($tokenid ?? 0), 4, '0', STR_PAD_LEFT);
                    #   Update the serial number
                    $this->db->executeTransaction("UPDATE nc_token SET `serial_no`='$serial_no' WHERE `tokenid`=$tokenid LIMIT 1",array());
                    $etoken_data[] = array(
                        'tokenid'=>$tokenid,
                        'serial_no'=>$serial_no
                    );
                    $counter++;
                }
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
            }
            #   return total data generated
            return $etoken_data;
        }
        public function ChangeLength($new_length)
        {
            if($new_length < 1)
            {
                $this->length = 0;
            }
            elseif($new_length > $this->max)
            {
                $this->length = $this->max;
            }
            else
            {
                $this->length = $new_length;
            }
        }
        public function getThisBatch($batchid)
        {
            return $this->getBatch($batchid);
        }
        public function getLastBatch()
        {
            $batchid = $this->etokenBatchId;
            return $this->getBatch($batchid);
        }
        public static function UpdateTokenUsed($tokenid)
        {
            if(DbHelper::Update('nc_token',
                array(
                    'status'=>'used',
                    'status_code'=>4,
                    'updated'=>getNowDbDate()
                ),'tokenid',$tokenid))
            {
                return true;
            }
            return false;
        }
        public static function UpdateTokenCancel($tokenid)
        {
            if(DbHelper::Update('nc_token',
                array(
                    'status'=>'cancel',
                    'status_code'=>3,
                    'updated'=>getNowDbDate()
                ),'tokenid',$tokenid))
            {
                return true;
            }
            return false;
        }
        
        # count batch

        #
        #   Private methods
        #
        private function CreateBatch()
        {
            $pre = GenerateCodeAlphabet(3);
            $id = DbHelper::Insert('nc_token_batch', array(
                'batch_no'=>$pre,
                'deviceid'=>$this->deviceid,
                'created'=>getNowDbDate()
            ));
            $batch_no = $pre.str_pad((string)($id ?? 0), 3, '0', STR_PAD_LEFT);
            //
            if($id)
            {
                //  Update the batch number
                if(DbHelper::Update('nc_token_batch',array('batch_no'=>$batch_no),'batchid',$id))
                {
                    #   Assign
                    $this->etokenBatchId = $id;
                    $this->etokenBatchNo = $batch_no;
                    #   return
                    $return_data = array('batchid'=>$id,'batch_no'=>$batch_no);
                    return $return_data;
                }
                //  Batch number update failed
                return false;
            }
            else
            {
                return false;
            }
        }
        # Get batch
        private function getBatch($batchid)
        {
            return DbHelper::Table("SELECT
            nc_token.tokenid,
            nc_token.batchid,
            nc_token.batch_no,
            nc_token.uuid,
            nc_token.serial_no
            FROM
            nc_token
            WHERE
            nc_token.batchid = $batchid");
        }
    }

?>