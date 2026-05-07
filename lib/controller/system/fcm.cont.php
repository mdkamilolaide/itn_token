<?php
namespace System;
include_once('lib/common.php');
#   
#
class Fcm{
    #
    #
    #
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    private $serverKey = '92941267104';
    #
    public function __construct(){
            
    }

    function sendFCMDataMessage($deviceToken, $data, $category, $note=""){
        // Firebase Cloud Messaging (FCM) API URL
        $url = $this->fcmUrl;

        // Server key from the Firebase console
        $serverKey = $this->serverKey;

        // Create the message payload
        $fields = [
            'to' => $deviceToken,  // The target device token
            'data' => array('category'=>$category,'data'=>$data)      // Custom data message payload
        ];

        // HTTP request headers
        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json'
        ];

        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);    // change to true when needed
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        // Execute the request and capture the response
        $result = curl_exec($ch);
        $date = getNowDbDate();
        $fcm_file_name = "fcm_log.txt";
        // Check if there was an error
        if ($result === FALSE) {
            #   Write to file 
            $error_message = curl_error($ch);
            $error_to_write = "ERROR: $error_message\r\nData:".json_encode($data)."\r\nDate: $date\r\n\r\n";
            WriteToFile($fcm_file_name, $error_to_write);
        }else{
            #   Log success also
            $message = "Success: Message send successfully\r\nCategory:$category\r\nData:".json_encode($data)."\r\nNote:$note\r\nDate: $date\r\n\r\n";
            WriteToFile($fcm_file_name, $message);
        }

        // Close cURL resource
        curl_close($ch);

        // Return the result of the request (FCM's response)
        return $result;
    }
}
?>