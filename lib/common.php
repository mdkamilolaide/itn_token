
<?php
/*
     *
     *		Cleavey Control Library
     *
     *      Version 2.5.16  Updated January 28, 2022
     *
     *
     */
date_default_timezone_set('Africa/Lagos');
function CleanData($string)
{
    $string = isset($_REQUEST[$string]) ? $_REQUEST[$string] : '';
    $string = htmlentities($string);
    $bad_chars = array("{", "}", "(", ")", ";", "<", ">", "/", "$");
    $string = str_ireplace($bad_chars, "", $string);
    $string = trim(strip_tags($string));
    return $string;
}
function CleanDataS($string)
{
    $string = isset($_REQUEST[$string]) ? $_REQUEST[$string] : '';
    $bad_chars = array("{", "}", ";", "<", ">", "$");
    $string = str_ireplace($bad_chars, "", $string);
    $string = trim(strip_tags($string));
    return $string;
}
function GenerateCodeNumeric($length)
{
    include_once('password.php');
    $code = new CodeGenerator();
    return $code->GetNumeric($length);
}
function GenerateCodeCapNumeric($length)
{
    include_once('password.php');
    $code = new CodeGenerator();
    return $code->GetCapNumeric($length);
}
function GenerateCodeSmallNumeric($length)
{
    include_once('password.php');
    $code = new CodeGenerator();
    return $code->GetSmallNumeric($length);
}
function GenerateCodeAlphabet($length)
{
    include_once('password.php');
    $code = new CodeGenerator();
    return $code->GetCapString($length);
}
function ttCoder($length)
{
    return GenerateCodeSmallNumeric($length);
}
function generateUUID()
{
    return ttCoder(8) . '-' . ttCoder(4) . '-' . ttCoder(4) . '-' . ttCoder(4) . '-' . ttCoder(12);
}
function generateShortUID()
{
    return ttCoder(4) . '-' . ttCoder(4) . '-' . ttCoder(4);
}
function StringClip($string, $length)
{
    if (strlen($string) < $length) {
        return $string;
    } else {
        return substr($string, 0, $length) . '...';
    }
}
function get_ext($str)
{
    $ext = substr($str, (strpos($str, ".") + 1));
    return $ext;
}
function NumberPadding($val, $total_length)
{
    $val = $val . '';
    $val_len = strlen($val);
    if ($val_len < $total_length) {
        $rem = $total_length - $val_len;
        for ($a = 0; $a < $rem; $a++) {
            $val = '0' . $val;
        }
    }
    return $val;
}
function getUserIP()
{
    $client = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote = $_SERVER['REMOTE_ADDR'];
    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }
    return $ip;
}
function strip($old, $new, $string)
{
    return str_replace($old, $new, $string);
}
function getNowDbDate()
{
    return date('Y-m-d H:i:s', time());
}
function getNowDate()
{
    return date('d-m-Y');
}
function getNowDateTime()
{
    return date('d-m-Y h:i:s A');
}
function DateConvertNgToDb($origDate)
{
    if (strlen($origDate) < 10 || strlen($origDate) > 10) {
        return "";
    }
    $date = str_replace('/', '-', $origDate);
    $newDate = date("Y-m-d", strtotime($date));
    return $newDate;
}
function DateConvertUsToDb($origDate)
{
    if (strlen($origDate) < 10) {
        return "";
    }
    $date = str_replace('/', '-', $origDate);
    $newDate = date("Y-m-d", strtotime($date));
    return $newDate;
}
function TimeConverter($time)
{
    return date("H:i", strtotime($time));
}
function BoolToInt($bool)
{
    if (strtolower($bool) == 'yes') {
        return 1;
    }
    return 0;
}
function IntToBool($int)
{
    if ($int > 0) {
        return 'Yes';
    }
    return 'No';
}
function url_origin($s, $use_forwarded_host = false)
{
    $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on');
    $sp = strtolower($s['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $s['SERVER_PORT'];
    $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
    $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
    $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}
function full_url($s, $use_forwarded_host = false)
{
    return url_origin($s, $use_forwarded_host) . $s['REQUEST_URI'];
}
function error_msg($msg)
{
    echo "<div class='alert alert-danger p-1' role='alert'>
                    $msg
            </div>";
}
function success_msg($msg)
{
    echo "<div class='alert alert-success p-1' role='alert'>
                    $msg
                </p>
            </div>";
}
function error_msg_dismissible($msg)
{
    echo "<div class='alert alert-danger alert-dismissible fade show p-1' role='alert'>
                <i class='mr-50 align-middle feather icon-info'></i>
                $msg
                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                    <span aria-hidden='true'>&times;</span>
                </button>
            </div>";
}
function successr_msg_dismissible($msg)
{
    echo "<div class='alert alert-success alert-dismissible fade show p-1' role='alert'>
                $msg
                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                    <span aria-hidden='true'>&times;</span>
                </button>
            </div>";
}
function GetMaxValueInArray($data, $index)
{
    $initialMax = 0;
    if (is_array($data)) {
        foreach ($data as $r) {
            $val = $r[$index];
            if ($val > $initialMax) {
                $initialMax = $val;
            }
        }
    }
    return $initialMax;
}
function SumValueInArray($data, $index)
{
    $initialMax = 0;
    if (is_array($data)) {
        foreach ($data as $r) {
            $initialMax += $r[$index];
        }
    }
    return $initialMax;
}
function StringToArray($string)
{
    return preg_split('/[\ \n\,]+/', $string);
}
function ArrayToString($array)
{
    $str = "";
    foreach ($array as $r) {
        $str .= $r;
    }
    return $str;
}
function WriteToFile($filename, $strContent)
{
    file_put_contents($filename, $strContent, FILE_APPEND);
}
function ReadFromFile($filename)
{
    return readfile($filename);
}
function myFloatValStr($str)
{
    $intarray = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '.');
    $str_array = str_split($str);
    $str_result = "";
    foreach ($str_array as $v) {
        $str_result .= in_array($v, $intarray) ? $v : '';
    }
    return $str_result;
}
function SeperateToString($string, $delimeter = ",", $quote = "'")
{
    $array = explode($delimeter, $string);
    $output = "";
    $counter = 1;
    foreach ($array as $v) {
        if (count($array) == $counter) {
            $output .= $quote . trim($v) . $quote;
        } else {
            $output .= $quote . trim($v) . $quote . ",";
        }
        $counter++;
    }
    return $output;
}
function ArrayToCsv($array, $delimeter = ",")
{
    if (is_array($array)) {
        if (count($array)) {
            $output = '';
            $counter = 1;
            foreach ($array as $v) {
                if (count($array) == $counter) {
                    $output .= trim($v);
                } else {
                    $output .= trim($v) . ',';
                }
                $counter++;
            }
            return $output;
        }
    }
    return null;
}
function has_role_access($role, $name)
{
    if (strtolower($role) == 'admin') {
        return true;
    }
    $array = explode(",", $role);
    foreach ($array as $item) {
        if (trim($item) == $name) return true;
    }
    return false;
}
function has_web_access($role)
{
    if (strtolower($role) == 'admin') {
        return true;
    }
    $array = explode(",", $role);
    if (trim($array[0]) == 'system') return true;
    elseif (trim($array[0]) == 'users') return true;
    elseif (trim($array[0]) == 'data') return true;
    elseif (trim($array[0]) == 'reporting') return true;
    elseif (trim($array[0]) == 'dashboard') return true;
    elseif (trim($array[0]) == 'alert') return true;
    return false;
}
function CustomDeserializeTwoD($serialData)
{
    $result = array();
    $layOne = explode(',', $serialData);
    if (is_array($layOne) && count($layOne) > 0) {
        foreach ($layOne as $v) {
            $layTwo = explode(':', $v);
            if (is_array($layTwo) && count($layTwo) > 0) {
                $cut = array();
                foreach ($layTwo as $i) {
                    $cut[] = $i;
                }
                $result[] = $cut;
            }
        }
    }
    return $result;
}
function CustomDeserializeIsModule($deserialized, $privilege)
{
    if (is_array($deserialized)) {
        foreach ($deserialized as $a) {
            if (strtolower($a[0]) == strtolower($privilege)) {
                return true;
            }
        }
    }
    return false;
}
function CustomDeserializeGetRoleId($deserialized, $privilege)
{
    if (is_array($deserialized)) {
        foreach ($deserialized as $a) {
            if (strtolower($a[0]) == strtolower($privilege)) {
                return $a[1];
            }
        }
    }
    return false;
}
function CustomDeserializeGetRoleDisplay($deserialized, $privilege)
{
    if (is_array($deserialized)) {
        foreach ($deserialized as $a) {
            if (strtolower($a[0]) == strtolower($privilege)) {
                return $a[2];
            }
        }
    }
    return false;
}
function CustomDeserializeGetRoleList($deserialized, $privilege)
{
    $list = array();
    if (is_array($deserialized)) {
        foreach ($deserialized as $a) {
            if (strtolower($a[0]) == strtolower($privilege)) {
                $list[] = $a;
            }
        }
    }
    return $list;
}
function PadWithLeadingZero($value, $output_length)
{
    return str_pad($value, $output_length, '0', STR_PAD_LEFT);
}
function IsPrivilegeInArray($privilege_array, $privilege)
{
    $answer = false;
    if (is_array($privilege_array)) {
        if (count($privilege_array)) {
            foreach ($privilege_array as $mat) {
                if ($mat['name'] == $privilege) {
                    $answer = true;
                }
            }
        }
    }
    return $answer;
}
function GetPrivilegeInArray($privilege_array, $privilege)
{
    #
    #   Get privilege of a certain item
    #
    if (is_array($privilege_array)) {
        if (count($privilege_array)) {
            foreach ($privilege_array as $mat) {
                if ($mat['name'] == $privilege) {
                    return $mat;
                }
            }
        }
    }
    return null;
}
function IsPlatformInArray($platform_array, $platform)
{
    $answer = false;
    if (is_array($platform_array)) {
        if (count($platform_array)) {
            foreach ($platform_array as $mat) {
                if ($mat['name'] == $platform) {
                    $answer = true;
                }
            }
        }
    }
    return $answer;
}
/**
 * Safely read a value from an array with a default fallback.
 *
 * @param array|mixed $array  Array to read from (will be treated as array when possible)
 * @param string|int $key     Key/index to read
 * @param mixed $default      Value to return when key is missing
 * @return mixed
 */
function GetSafeArrayValue($array, $key, $default = '')
{
    return is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default;
}
function log_system_access()
{
    include_once('lib/mysql.min.php');
    $currentDate = date('Y-m-d');
    $currentHour = date('G');
    $db = GetMysqlDatabase();
    return $db->Execute("INSERT INTO `sys_request_counts` (`date`, `hour`, `count`) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE count = count + 1", array($currentDate, $currentHour));
}

function encryptJsonGCM($dataArray, $password)
{
    $key = hash('sha256', $password, true); // 32 bytes
    // $salt = random_bytes(16); // Non-secret, stored with ciphertext 
    // $key = deriveKey($password, $salt);
    $iv = random_bytes(12); // 12 bytes for GCM
    $plaintext = json_encode($dataArray);


    $tag = '';
    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',
        16
    );

    // Combine iv + ciphertext + tag
    return base64_encode($iv . $ciphertext . $tag);
}
function decryptJsonGCM($base64Cipher, $password)
{
    $decoded = base64_decode($base64Cipher);
    $key = hash('sha256', $password, true);
    // $salt = substr($decoded, 0, 16);
    // $key = deriveKey($password, $salt);


    $iv = substr($decoded, 0, 12);
    $tag = substr($decoded, -16);
    $ciphertext = substr($decoded, 12, -16);

    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return json_decode($plaintext, true);
}
function deriveKey(string $password, string $salt): string
{
    return hash_pbkdf2("sha256", $password, $salt, 100000, 32, true); // 256-bit key 
}

?>