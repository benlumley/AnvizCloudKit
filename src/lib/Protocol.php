<?php
namespace Anviz\Cloudkit\lib;
use Illuminate\Support\Facades\Config;

/**
 * File Name: Protocol.php
 * Created by Jacobs <jacobs@anviz.com>.
 * Date: 2016-3-22
 * Time: 9:44
 * Description:
 */

class Protocol
{
    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : explodeCommand
     *
     * @param $token
     * @param $data
     *
     * @return bool
     * @Description:
     */
    public static function explodeCommand($token, $data)
    {
        if (empty($token) || empty($data)) {
            return false;
        }

        $sha1 = substr(sha1(Config::get('app.anviz.key') . $token), 16, 8);

        $data = base64_decode($data);

        $data                = Tools::decrypt3DES($data, $sha1);

        if (strlen($data) < 56) {
            return false;
        }

        $result["device_id"] = trim(substr($data, 0, 32));
        $result["id"]        = trim(substr($data, 32, 8));
        $result["command"]   = trim(substr($data, 40, 4));
        $result["length"]    = trim(substr($data, 48, 8));
        $result["content"]   = @str_pad(substr($data, 56), $result['length'], ' ', STR_PAD_RIGHT);

        return $result;
    }

    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : RegisterDevice
     *
     * @param string $data
     *
     * @return array|bool
     * @Description:
     */
    public static function RegisterDevice($data = '')
    {
        if (empty($data)) {
            return false;
        }

        $data = base64_decode($data);

        $result = array();
        /** Serial number */
        $result["serial_number"] = trim(substr($data, 0, 20));
        /** Deivce model */
        $result["model"] = trim(substr($data, 20, 20));
        /** Firmware */
        $result["firmware"] = trim(substr($data, 40, 20));
        /** Communication protocol version */
        $result["protocol"] = trim(substr($data, 60, 20));

        return $result;
    }

    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : LoginDevice
     *
     * @param string $content
     *
     * @return array|bool
     * @Description:
     */
    public static function LoginDevice($content = '')
    {
        if (empty($content)) {
            return false;
        }

        $result = array();

        $result['username']  = trim(substr($content, 0, 20));
        $result['dpassword'] = trim(substr($content, 20, 20));

        return $result;
    }

    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : NetworkDevice
     *
     * @param string $content
     *
     * @return array|bool
     * @Description:
     */
    public static function NetworkDevice($content = '')
    {
        if (empty($content)) {
            return false;
        }

        if (strlen($content) < 28) {
            return [];
        }

        $result              = array();
        $result['internet']  = ord($content[0]);
        $result['ipaddress'] = ord($content[1]) . "." . ord($content[2]) . "." . ord($content[3]) . "." . ord($content[4]);
        $result['netmask']   = ord($content[5]) . "." . ord($content[6]) . "." . ord($content[7]) . "." . ord($content[8]);
        $result['mac']       = strtoupper(str_pad(sprintf("%x", ord($content[9])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[10])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[11])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[12])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[13])), 2, "0", STR_PAD_LEFT)) . '-'
            . strtoupper(str_pad(sprintf("%x", ord($content[14])), 2, "0", STR_PAD_LEFT));
        $result['gateway'] = ord($content[15]) . "." . ord($content[16]) . "." . ord($content[17]) . "." . ord($content[18]);
        /*$result['serverip'] = ord($content[19]) . "." . ord($content[20]) . "." . ord($content[21]) . "." . ord($content[22]);
        $result['remote'] = ord($content[23]);
        $result['port'] = (ord($content[24]) << 8) + ord($content[25]);
        $result['comm_method'] = ord($content[26]);*/
        $result['dhcp'] = ord($content[27]);

        return $result;
    }

    public static function RecordUserFPCountDevice($content = '')
    {
        if (empty($content)) {
            return false;
        }

        $result           = array();
        $result['record'] = substr($content, 0, 8);
        $result['user']   = substr($content, 8, 8);
        $result['fp']     = substr($content, 16, 8);

        return $result;
    }

    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : EmployeeDevice
     *
     * @param string $content
     *
     * @return array|bool
     * @Description:
     */
    public static function EmployeeDevice($content = '')
    {
        if (empty($content)) {
            return false;
        }

        /**
         * The length of each employee information is 40
         * if the length of data can not be 40 whole, it's dirty data
         */

        if (strlen($content) % 40 != 0) {
            return false;
        }

        $result = array();

        /** the total of employee in this acquisition */
        $count = strlen($content) / 40;
        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 40, 40);

            $record = array();
            /** ID On Device */
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);

            if (ord($row[5]) == 0xFF and ord($row[6]) == 0xFF and ord($row[7]) == 0xFF) {
                $record['passd'] = '';
            } else {
                /** The length of password */
                $passlen = intval(ord($row[5]) >> 4);
                /** Attendance Password */
                $record['passd'] = ((ord($row[5]) & 0x0F) << 16) + (ord($row[6]) << 8) + ord($row[7]);
                $record['passd'] = str_pad($record['passd'], $passlen, '0', STR_PAD_LEFT);
            }

            /** Card number */
            if (ord($row[8]) == 0xFF and ord($row[9]) == 0xFF and ord($row[10]) == 0xFF and ord($row[11]) == 0xFF) {
                $record['cardid'] = '';
            } else {
                $record['cardid'] = (ord($row[8]) << 24) + (ord($row[9]) << 16) + (ord($row[10]) << 8) + ord($row[11]);
            }

            /** Last Name */
            $record['name'] = '';
            for ($_i = 0; $_i < 10; $_i++) {
                $temp = (ord($row[$_i * 2 + 13]) << 8) + ord($row[$_i * 2 + 12]);
                if (empty($temp)) {
                    continue;
                }
                $record['name'] .= Tools::uni2utf8($temp);
            }
            $record['name'] = empty($record['name']) ? $record['idd'] : $record['name'];

            /** Department ID */
            //$record['deptid'] = ord($row[32]);

            /** Group ID */
            $record['group_id'] = ord($row[33]);

            /** The sign of the finger had been register */
            $record['fingersign'] = (ord($row[35]) << 8) + ord($row[36]);

            /** Whether administrator */
            $record['is_admin'] = ord($row[37]);

            $result[$i] = $record;
        }

        return $result;
    }

    /**
     * Parse employee data pushed FROM the device via actionReport
     * (CMD_PUTONEEMPLOYEE / CMD_PUTALLEMPLOYEE).
     *
     * This handles data from the firmware's formatDataToCloud function
     * (Dlg_UserEdit / Dlg_UserRegUser), which has two known differences
     * from the standard EmployeeDevice format used by CMD_GET* responses:
     *
     *  - Password bytes are rotated: [LOW, HIGH, MID] instead of [HIGH, MID, LOW].
     *    This is a firmware bug in formatDataToCloud (see FW-001).
     *  - Name field may contain UTF-8 bytes instead of UTF-16LE code-units,
     *    so we try UTF-16LE first then fall back to UTF-8.
     *
     * @param string $content
     * @return array|bool
     */
    public static function EmployeeDeviceReport($content = '')
    {
        if (empty($content)) {
            return false;
        }

        // Records are still 40 bytes each
        if (strlen($content) % 40 != 0) {
            return false;
        }

        $result = array();
        $count = strlen($content) / 40;

        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 40, 40);

            $record = array();

            // IDD: 5 bytes big-endian — always reliable
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);

            // Raw hex for debugging
            $record['raw_hex'] = bin2hex($row);

            // Best-effort name extraction: try UTF-16LE first, fall back to
            // treating the 20-byte field as UTF-8 if UTF-16LE yields nothing
            $nameField = substr($row, 12, 20);
            $name = '';

            // Try UTF-16LE (standard cloud protocol encoding)
            for ($_i = 0; $_i < 10; $_i++) {
                $temp = (ord($nameField[$_i * 2 + 1]) << 8) + ord($nameField[$_i * 2]);
                if (empty($temp)) {
                    continue;
                }
                $name .= Tools::uni2utf8($temp);
            }

            // If UTF-16LE produced nothing useful, try raw UTF-8
            if (empty($name)) {
                $name = rtrim($nameField, "\0");
                // Validate it's actually UTF-8
                if (!mb_check_encoding($name, 'UTF-8')) {
                    $name = '';
                }
            }

            $record['name'] = empty($name) ? (string) $record['idd'] : $name;

            // Password: firmware bug in formatDataToCloud writes bytes as
            // [LOW, HIGH, MID] instead of standard [HIGH, MID, LOW].
            // See docs/anviz-firmware/findings/FW-001-password-byte-rotation.md
            if (ord($row[5]) == 0xFF && ord($row[6]) == 0xFF && ord($row[7]) == 0xFF) {
                $record['passd'] = '';
            } else {
                // Bytes arrive rotated: [LOW, HIGH, MID] from formatDataToCloud bug.
                // HIGH byte has password length in upper nibble + value bits 19-16.
                // MID byte has value bits 15-8. LOW byte has value bits 7-0.
                $low  = ord($row[5]);
                $high = ord($row[6]);
                $mid  = ord($row[7]);
                $passlen = $high >> 4;
                $passval = (($high & 0x0F) << 16) | ($mid << 8) | $low;
                $record['passd'] = str_pad((string) $passval, $passlen, '0', STR_PAD_LEFT);
            }

            // Card number
            if (ord($row[8]) == 0xFF && ord($row[9]) == 0xFF && ord($row[10]) == 0xFF && ord($row[11]) == 0xFF) {
                $record['cardid'] = '';
            } else {
                $record['cardid'] = (ord($row[8]) << 24) + (ord($row[9]) << 16) + (ord($row[10]) << 8) + ord($row[11]);
            }

            // Group ID
            $record['group_id'] = ord($row[33]);

            // Finger/face registration sign
            $record['fingersign'] = (ord($row[35]) << 8) + ord($row[36]);

            // Admin flag
            $record['is_admin'] = ord($row[37]);

            $result[$i] = $record;
        }

        return $result;
    }

    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : FingerDevice
     *
     * @param string $content
     *
     * @return array|bool
     * @Description:
     */
    public static function FingerDevice($content = '')
    {
        if (empty($content)) {
            return false;
        }

        /**
         * The length of each finger information is 344
         * if the length of data can not be 344 whole, it's dirty data
         */
        if (strlen($content) % 344 != 0) {
            return false;
        }
        $result = array();

        /** the total of finger in this acquisition */
        $count = strlen($content) / 344;
        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 344, 344);

            $record = array();

            /** ID On Device */
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);
            /**
             * 1: Fingerprint
             * 2: Facepass
             */
            $record['sign'] = 1;
            /** The number of finger */
            $record['temp_id'] = ord($row[5]);
            /** the data of finger */
            $record['template'] = substr($row, 6, 338);

            $result[$i] = $record;
        }

        return $result;
    }

    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : FaceDevice
     *
     * @param string $content
     *
     * @return array|bool
     * @Description:
     */
    public static function FaceDevice($content = '',$trans_photo = false)
    {
        if (strlen($content) < 6) {
            return [];
        }

        $record = array();
        /** ID On Device */
        $record['idd'] = (ord($content[0]) << 32) + (ord($content[1]) << 24) + (ord($content[2]) << 16) + (ord($content[3]) << 8) + ord($content[4]);

        /**
         * 1: Fingerprint
         * 2: Facepass
         */
        $record['sign'] = 2;
        $record['temp_id'] = ord($content[5]);
        /** the data of face */
        $record['template'] = substr($content, 6);

        //Encrypt template to picture
        if($trans_photo){

            $hex_data = bin2hex($record['template']);
            $head = substr($hex_data , 0 , 8);
            if($head=='f1f2f2f4'){
                $hex_data = substr($hex_data, 8);
            }

            $bin_data = hex2bin($hex_data);
            $record['template'] =$bin_data;
            file_put_contents('../logs/face_template_'.$record['idd'].'.jpg',$bin_data);
        }



        return $record;

    }

    public static function EnrollFinger($content = '')
    {
        if (empty($content)) {
            return false;
        }

        if (strlen($content) % 344 != 0) {
            return false;
        }
        $result = array();
        $row    = substr($content, 0, 344);

        /** ID On Device */
        $result['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);
        /**
         * 1: Fingerprint
         * 2: Facepass
         */
        $result['sign'] = 1;
        /** The number of finger */
        $result['temp_id'] = ord($row[5]);
        /** the data of finger */
        $result['template'] = substr($row, 6, 338);

        return $result;
    }

    public static function EnrollFace($content = '',$trans_photo = false)
    {
        if (empty($content)) {
            return false;
        }
        $row = $content;

        /** ID On Device */
        $result['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);
        /**
         * 1: Fingerprint
         * 2: Facepass
         */
        $result['sign'] = 2;
        $result['temp_id'] = ord($row[5]);
        $result['template'] = substr($row, 6);


        //Encrypt template to picture
        if($trans_photo){

            $hex_data = bin2hex($result['template']);
            $head = substr($hex_data , 0 , 8);
            if($head=='f1f2f2f4'){
                $hex_data = substr($hex_data, 8);
            }

            $bin_data = hex2bin($hex_data);
            $result['template'] =$bin_data;
        }


        return $result;
    }


    public static function EnrollCardDevice($content = '')
    {
        if (empty($content)) {
            return false;
        }

        $result = array();
        /** ID On Device */
        $result['idd'] = (ord($content[0]) << 32) + (ord($content[1]) << 24) + (ord($content[2]) << 16) + (ord($content[3]) << 8) + ord($content[4]);

        if (ord($content[5]) == 0xFF and ord($content[6]) == 0xFF and ord($content[7]) == 0xFF and ord($content[8]) == 0xFF) {
            $result['cardid'] = '';
        } else {
            $result['cardid'] = (ord($content[5]) << 24) + (ord($content[6]) << 16) + (ord($content[7]) << 8) + ord($content[8]);
        }

        return $result;
    }

    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : RecordDevice
     *
     * @param string $content
     *
     * @return array|bool
     * @Description:
     */
    public static function RecordDevice($content = '')
    {
        if (empty($content)) {
            return false;
        }

        /**
         * The length of each record is 16
         * if the length of data can not be 16 whole, it's dirty data
         */
        if (strlen($content) % 16 != 0) {
            return false;
        }
        $result = array();

        /** the total of records in this acquisition */
        $count = strlen($content) / 16;
        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 16, 16);

            $record = array();

            /** ID On Device */
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);
            /** Check Time */
            $record['checktime'] = (ord($row[5]) << 24) + (ord($row[6]) << 16) + (ord($row[7]) << 8) + ord($row[8]);
            $record['checktime'] = $record['checktime'] + strtotime('2000-01-02 00:00:00');
            /** Check Type */
            $record['checktype'] = ord($row[9]);
            /** Work Type */
            $record['worktype'] = ord($row[10]);

            $result[$i] = $record;
        }

        return $result;
    }

    /**
     * Parse attendance records from CMD_GETRECORDFROMTIME (3004) response.
     *
     * FW-002: The firmware packs each record as 11 bytes via bufToModFillByte.
     * Fields: 5-byte idd + 4-byte checktime + 1-byte checktype + 1-byte worktype.
     * Returns the same array structure as RecordDevice() for callback compatibility.
     *
     * @param  string  $content  Raw decrypted content from explodeCommand
     * @return array|false  Array of records, or false if content doesn't parse as 11-byte records
     */
    public static function RecordDeviceFromTime($content = '')
    {
        if (empty($content)) {
            return false;
        }

        if (strlen($content) % 11 != 0) {
            return false;
        }

        $result = array();
        $count = strlen($content) / 11;
        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 11, 11);

            $record = array();

            /** ID On Device */
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);
            /** Check Time */
            $record['checktime'] = (ord($row[5]) << 24) + (ord($row[6]) << 16) + (ord($row[7]) << 8) + ord($row[8]);
            $record['checktime'] = $record['checktime'] + strtotime('2000-01-02 00:00:00');
            /** Check Type */
            $record['checktype'] = ord($row[9]);
            /** Work Type */
            $record['worktype'] = ord($row[10]);

            $result[$i] = $record;
        }

        return $result;
    }

    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : TemperatureRecordDevice
     *
     * @param string $content
     *
     * @return array|bool
     * @Description:
     */
    public static function TemperatureRecordDevice($content = '')
    {
        if (empty($content)) {
            return false;
        }

        /**
         * The length of each record is 16
         * if the length of data can not be 16 whole, it's dirty data
         */
        if (strlen($content) % 24 != 0) {
            return false;
        }
        $result = array();

        /** the total of records in this acquisition */
        $count = strlen($content) / 24;
        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 24, 24);

            $record = array();

            /** ID On Device */
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);
            /** Check Time */
            $record['checktime'] = (ord($row[5]) << 24) + (ord($row[6]) << 16) + (ord($row[7]) << 8) + ord($row[8]);
            $record['checktime'] = $record['checktime'] + strtotime('2000-01-02 00:00:00');

            /** TemperatureID On Device */
            $record['rid'] = (ord($row[9]) << 56) + (ord($row[10]) << 48) + (ord($row[11]) << 40) + (ord($row[12]) << 32) + (ord($row[13]) << 24) + (ord($row[14]) << 16) + (ord($row[15]) << 8) + ord($row[16]);

            /** Work Type */
//            $record['worktype'] = ord($row[17]);
            /** Temperature */
            $record['temperature'] = (ord($row[18]) << 8) + ord($row[19]);
            $record['temperature'] = $record['temperature']/10;

            /** Temperature */
            $record['mask'] = ord($row[20]);
            /** Check Type */
            $record['checktype'] = ord($row[21]);

            $result[$i] = $record;
        }

        return $result;
    }


    public static function getTemperaturePic($data)
    {
        if (empty($data)) {
            return false;
        }
        $pack =  pack("N", intval($data['rid'] / 0x00FFFFFFFF)) . pack("N", $data['rid']& 0x00FFFFFFFF);


        return $pack;
    }


    public static function TemperaturePic($data = '')
    {
        if (empty($data)) {
            return false;
        }
        $record['rid'] = (ord($data[0]) << 56) + (ord($data[1]) << 48) + (ord($data[2]) << 40) + (ord($data[3]) << 32) + (ord($data[4]) << 24) + (ord($data[5]) << 16) + (ord($data[6]) << 8) + ord($data[7]);
        $record['pic'] = substr($data, 8);

        return $record;
    }

    public static function RecordImport($content = '')
    {
        if (empty($content)) {
            return false;
        }

        /**
         * The length of each record is 16
         * if the length of data can not be 16 whole, it's dirty data
         */
        if ((strlen($content) - 3) % 14 != 0) {
            return false;
        }
        $result = array();

        $count = (ord($content[0]) << 16) + (ord($content[1]) << 8) + ord($content[2]);
        for ($i = 0; $i < $count; $i++) {
            $row = substr($content, $i * 14 + 3, 14);

            $record = array();

            /** ID On Device */
            $record['idd'] = (ord($row[0]) << 32) + (ord($row[1]) << 24) + (ord($row[2]) << 16) + (ord($row[3]) << 8) + ord($row[4]);
            /** Check Time */
            $record['checktime'] = (ord($row[5]) << 24) + (ord($row[6]) << 16) + (ord($row[7]) << 8) + ord($row[8]);
            $record['checktime'] = $record['checktime'] + strtotime('2000-01-02 00:00:00');

            $result[$i] = $record;
        }

        return $result;
    }

    public static function setDeviceDateTime($data = array())
    {
        if (empty($data)) {
            return false;
        }

        $year   = empty($data['year']) ? date('Y') : $data['year'];
        $month  = empty($data['month']) ? date('m') : $data['month'];
        $day    = empty($data['day']) ? date('d') : $data['day'];
        $hour   = empty($data['hour']) ? 0 : $data['hour'];
        $minute = empty($data['minute']) ? 0 : $data['minute'];
        $second = empty($data['second']) ? 0 : $data['second'];

        if ($year >= 2000) {
            $year = $year - 2000;
        }

        $pack = '';

        $pack .= pack('C', $year);
        $pack .= pack('C', $month);
        $pack .= pack('C', $day);
        $pack .= pack('C', $hour);
        $pack .= pack('C', $minute);
        $pack .= pack('C', $second);

        return $pack;
    }

    public static function getAllEmployee($data = array())
    {
        $start = empty($data['start']) ? 0 : $data['start'];
        $limit = empty($data['limit']) ? 100 : $data['limit'];

        $pack = '';
        $pack .= str_pad($start, 8, '0', STR_PAD_LEFT);
        $pack .= str_pad($limit, 8, '0', STR_PAD_LEFT);

        return $pack;
    }

    public static function getEmployee($idd)
    {
        $pack = '';


        $pack .= pack("C", intval($idd / 0x00FFFFFFFF)) . pack("N", $idd & 0x00FFFFFFFF);


//        $pack .= str_pad($idd, 16, '0', STR_PAD_LEFT);

        return $pack;
    }

    public static function setEmployee($employee)
    {
        if (empty($employee)) {
            return false;
        }

        if (!isset($employee['idd'])) {
            return false;
        }

        $idd           = $employee['idd'];
        $passd         = isset($employee['passd']) ? $employee['passd'] : '';
        $cardid        = isset($employee['cardid']) ? $employee['cardid'] : '';
        $name          = isset($employee['name']) ? $employee['name'] : $idd;
        $deptid        = isset($employee['deptid']) ? $employee['deptid'] : 0;
        $is_admin      = isset($employee['is_admin']) ? $employee['is_admin'] : 64;
        $group_id      = isset($employee['group_id']) ? $employee['group_id'] : 0;
        $identity_type = isset($employee['identity_type']) ? $employee['identity_type'] : 6;
        $fingersign    = 0;

        $pack = '';

        //IDD
        $pack = pack("C", intval($idd / 0x00FFFFFFFF)) . pack("N", $idd & 0x00FFFFFFFF);

        //密码：5~7
        if ($passd === '' || $passd === null) {
            $pack .= substr(pack('N', 0xFF), 0, 3);
        } elseif (!is_numeric($passd) || intval($passd) < 0 || intval($passd) > 999999) {
            // Non-numeric or out-of-range passwords cannot be encoded in the 20-bit format;
            // treat as "no password" rather than silently encoding garbage
            $pack .= substr(pack('N', 0xFF), 0, 3);
        } else {
            $passlen = strlen($passd);
            $passd = intval($passd);
            $length = $passlen << 4;
            $length = intval($length) + intval($passd >> 16);
            $pack .= substr(pack('n', $length), 1, 1) . substr(pack('N', $passd), 2, 2);
        }

        //Cardid
        if ($cardid === '' || $cardid === null || $cardid === false) {
            $pack .= pack('N', 0xFFFFFFFF);
        } else {
            $pack .= pack('N', $cardid);
        }

        //Name
        if (mb_strlen($name) > 10) {
            $name = mb_substr($name, 0, 10);
        }
        $pack .= str_pad(Tools::utf82uni($name), 20, pack('v', 0x00), STR_PAD_RIGHT);

        //Department ID
        $pack .= pack('C', $deptid);

        //Group ID
        $pack .= pack('C', $group_id);

        //
        $pack .= pack('C', $identity_type);

        //Finger sign
        $pack .= pack('n', $fingersign);

        //Is admin
        $pack .= pack('C', $is_admin);

        //
        $pack .= pack('C', 0x20);
        //
        $pack .= pack('C', 0x20);

        return $pack;
    }

    public static function delEmployee($idd)
    {
        if (empty($idd)) {
            return false;
        }

        $pack = '';

        //IDD
        $pack = pack("C", intval($idd / 0x00FFFFFFFF)) . pack("N", $idd & 0x00FFFFFFFF);

        return $pack;
    }

    public static function getAllFinger($data = array())
    {
        $start = empty($data['start']) ? 0 : $data['start'];
        $limit = empty($data['limit']) ? 100 : $data['limit'];

        $pack = '';
        $pack .= str_pad($start, 8, '0', STR_PAD_LEFT);
        $pack .= str_pad($limit, 8, '0', STR_PAD_LEFT);

        return $pack;
    }

    public static function getFinger($idd)
    {
        if (empty($idd)) {
            return false;
        }

        $pack = pack("C", intval($idd / 0x00FFFFFFFF)) . pack("N", $idd & 0x00FFFFFFFF);

        return $pack;

    }

    public static function setFinger($finger)
    {
        if (empty($finger) || empty($finger['idd']) || empty($finger['template'])) {
            return false;
        }

        $idd  = $finger['idd'];
        $sign = empty($finger['sign']) ? 0 : $finger['sign'];
        $fp   = $finger['template'];

        $pack = '';
        $pack .= pack("C", intval($idd / 0x00FFFFFFFF)) . pack("N", $idd & 0x00FFFFFFFF);
        $pack .= pack('C', $sign);
        $pack .= $fp;

        return $pack;
    }

    public static function getFace($idd)
    {
        if (empty($idd)) {
            return false;
        }

        $pack = pack("C", intval($idd / 0x00FFFFFFFF)) . pack("N", $idd & 0x00FFFFFFFF);

        return $pack;

    }

    public static function setFace($face)
    {
        if (empty($face) || empty($face['idd']) || empty($face['template'])) {
            return false;
        }

        $idd  = $face['idd'];
        $sign = 20;
        $fp   = $face['template'];

        $pack = '';
        $pack .= pack("C", intval($idd / 0x00FFFFFFFF)) . pack("N", $idd & 0x00FFFFFFFF);
        $pack .= pack('C', $sign);
        $pack .= $fp;

        return $pack;
    }

    public static function setEnrollFinger($finger)
    {
        if (empty($finger) || empty($finger['idd'])) {
            return false;
        }

        $idd    = $finger['idd'];
        $sign   = empty($finger['sign']) ? 0 : $finger['sign'];
        $signed = empty($finger['signed']) ? 0 : $finger['signed'];

        $pack = '';
        $pack .= str_pad($idd, 8, '0', STR_PAD_LEFT);
        $pack .= str_pad($sign, 8, '0', STR_PAD_LEFT);
        $pack .= pack('n', $signed);

        return $pack;
    }


    public static function setEnrollCard($idd)
    {
        if (empty($idd)) {
            return false;
        }

        $pack = '';
        $pack .= str_pad($idd, 8, '0', STR_PAD_LEFT);

        return $pack;
    }

    public static function deleteFinger($finger)
    {
        if (empty($finger) || empty($finger['idd'])) {
            return false;
        }

        $idd  = $finger['idd'];
        $sign = empty($finger['sign']) ? 0 : $finger['sign'];

        $pack = '';
        $pack .= pack("C", intval($idd / 0x00FFFFFFFF)) . pack("N", $idd & 0x00FFFFFFFF);
        $pack .= str_pad($sign, 5, '0', STR_PAD_LEFT);

        return $pack;
    }

    public static function getAllRecord($data)
    {
        $start = empty($data['start']) ? 0 : $data['start'];
        $limit = empty($data['limit']) ? 100 : $data['limit'];

        $pack = '';
        $pack .= str_pad($start, 8, '0', STR_PAD_LEFT);
        $pack .= str_pad($limit, 8, '0', STR_PAD_LEFT);

        return $pack;
    }

    /**
     * Build the payload for CMD_GETRECORDFROMTIME (3004).
     *
     * Firmware handler: CC_GetReocrdFromTime (FW-002).
     * Payload: 5-byte person_id (big-endian idd, 0xFF×5 = all)
     *        + 4-byte start_time (big-endian uint32, seconds since Anviz epoch, 0xFF×4 = no filter)
     *        + 4-byte end_time   (same format)
     *
     * Anviz epoch: 2000-01-02 00:00:00 UTC.
     *
     * @param  array{person_id?: int|null, start_time?: int|null, end_time?: int|null}  $data
     * @return string  13-byte binary payload
     */
    public static function getRecordFromTime(array $data): string
    {
        $epoch = strtotime('2000-01-02 00:00:00');

        // Person ID: 5-byte big-endian, or 0xFF×5 for "all"
        if (isset($data['person_id']) && $data['person_id'] !== null) {
            $id = (int) $data['person_id'];
            $pack = chr(($id >> 32) & 0xFF)
                  . chr(($id >> 24) & 0xFF)
                  . chr(($id >> 16) & 0xFF)
                  . chr(($id >> 8) & 0xFF)
                  . chr($id & 0xFF);
        } else {
            $pack = str_repeat("\xFF", 5);
        }

        // Start time: 4-byte big-endian seconds since epoch, or 0xFF×4
        if (isset($data['start_time']) && $data['start_time'] !== null) {
            $seconds = (int) $data['start_time'] - $epoch;
            $pack .= chr(($seconds >> 24) & 0xFF)
                   . chr(($seconds >> 16) & 0xFF)
                   . chr(($seconds >> 8) & 0xFF)
                   . chr($seconds & 0xFF);
        } else {
            $pack .= str_repeat("\xFF", 4);
        }

        // End time: same format
        if (isset($data['end_time']) && $data['end_time'] !== null) {
            $seconds = (int) $data['end_time'] - $epoch;
            $pack .= chr(($seconds >> 24) & 0xFF)
                   . chr(($seconds >> 16) & 0xFF)
                   . chr(($seconds >> 8) & 0xFF)
                   . chr($seconds & 0xFF);
        } else {
            $pack .= str_repeat("\xFF", 4);
        }

        return $pack;
    }


    public static function getTempRecords($data = array())
    {
        if (empty($data))
            return false;

        if (!isset($data['start']))
            return false;

        $rid = $data['start']?1:$data['start'];

        $num = $data['limit']?100:$data['limit'];


        $pack =  pack("N", intval($rid / 0x00FFFFFFFF)) . pack("N", $rid& 0x00FFFFFFFF);

        $pack .= pack('n', $num);

        return $pack;
    }

    public static function setMaskTemperatureConfig(array $data = []){
        if(empty($data)){
            return false;
        }

        $mask_detection = $data['mask_detection'];
        $mask_alarm = $data['mask_alarm'];

        $work_mode = $data['work_mode'];
        $relay_output = $data['relay_output'];
        $temp_unit = $data['temp_unit'];
        $fever_threshold = $data['fever_threshold'];
        $fever_alarm = $data['fever_alarm'];
        $temp_opendoor = $data['temp_opendoor'];
        $temp_access = $data['temp_access'];

        $pack = '';
        $pack .= pack('C', $mask_detection);
        $pack .= pack('C', $mask_alarm);

        $pack .= pack('C', $work_mode);
        $pack .= pack('C', $relay_output);
        $pack .= pack('C', $temp_unit);

        $pack .= pack('n', $fever_threshold);

        $pack .= pack('C', $fever_alarm);
        $pack .= pack('C', $temp_opendoor);
        $pack .= pack('C', $temp_access);




        return $pack;
    }



    public static function getMaskTemperatureConfig($data = '')
    {
        if (empty($data)) {
            return false;
        }
        $record = array();
        $record['mask_detection'] = ord($data[0]);
        $record['mask_alarm'] = ord($data[1]);

        $record['work_mode'] = ord($data[2]);
        $record['relay_output'] = ord($data[3]);
        $record['temp_unit'] = ord($data[4]);

        $record['fever_threshold'] = (ord($data[5]) << 8) + ord($data[6]);

        $record['fever_alarm'] = ord($data[7]);
        $record['temp_opendoor'] = ord($data[8]);
        $record['temp_access'] = ord($data[9]);
        return $record;
    }

    public static function setSuperAdminPassword($password)
    {
        $passlen = strlen($password);

        $pack = '';
        if($passlen == 0){
            $pack .= substr(pack('N', 0xFF), 0, 3);
        }else{
            $length = $passlen << 4;
            $length = intval($length) + intval($password >> 16);
            $pack .= substr(pack('n', $length), 1, 1) . substr(pack('N', $password), 2, 2);
        }

        $pack = str_pad($pack, 8, 0, STR_PAD_RIGHT);

        return $pack;
    }

    public static function showRegister($device_id, $nexttime = 60)
    {
        return Tools::R(self::joinCommand('11111111', $device_id, '11111111', AnvizConstants::CMD_REGESTER, $nexttime, ''));
    }

    public static function showError($sha1, $device_uuid, $command = '', $nexttime = 60)
    {
        return Tools::R(self::joinCommand($sha1, $device_uuid, '11111111', AnvizConstants::CMD_ERROR, $nexttime, $command));
    }

    public static function showForbidden($nexttime = 60)
    {
        return Tools::R(self::joinCommand('11111111', '22222222', '22222222', AnvizConstants::CMD_FORBIDDEN, $nexttime, ''));
    }

    public static function showNocommand($token, $device_id, $nexttime = 60)
    {
        return Protocol::joinCommand($token, $device_id, '11111111', AnvizConstants::CMD_NOCOMMAND, $nexttime);
    }

    /**
     * @Created    by Jacobs <jacobs@anviz.com>
     * @Name       : joinCommand
     *
     * @param        $sha1         Token value
     * @param        $device_id
     * @param        $id
     * @param        $command
     * @param        $nexttime
     * @param int    $length
     * @param string $content
     *
     * @return bool|string
     * @Description:
     */
    public static function joinCommand($token, $device_id, $id, $command, $nexttime, $content = "")
    {

        if (empty($token) || empty($device_id) || empty($id) || empty($command)) {
            return false;
        }

        $sha1 = substr(sha1(Config::get('app.anviz.key') . $token), 16, 8);

        $id = empty($id) ? '11111111' : str_pad($id, 8, ' ', STR_PAD_LEFT);

        $command = str_pad($command, 4, ' ', STR_PAD_LEFT);
        /** Next heartbeat packet send interval time */
        /** gSOAP nexttime interval in seconds */
        $nextime = str_pad($nexttime, 4, 0, STR_PAD_LEFT);

        $length = strlen($content);
        $length = str_pad($length, 8, 0x00, STR_PAD_LEFT);

        $device_id = str_pad($device_id, 32, 0x00, STR_PAD_LEFT);

        $string = $device_id . $id . $command . $nextime . $length . $content;

        switch ($command) {
            case AnvizConstants::CMD_REGESTER:
            case AnvizConstants::CMD_FORBIDDEN:
                return $string;

            default:
                return Tools::encrypt3DES($string, $sha1);
        }
    }

    public static function dataIsFace($content=''){
        if (strlen($content) < 6) {
            return false;
        }

        /**
         * If temp_id = 20,the device is face type.(0~9 means finger type)
         */
        $temp_id = ord($content[5]);
        if ($temp_id == 20) {
            return true;
        }
        return false;
    }

    /**
     * @Created by Jacobs <jacobs@anviz.com>
     * @Name: deviceIsFace
     * @param string $firmware
     * @return bool
     * @Description:
     */
    public static function deviceIsFace($firmware = ''){
        $firmware_type = self::getFirmwareType($firmware);

        if(empty($firmware_type)){
            return false;
        }

        if(in_array($firmware_type, array(72,74,76,78,80))){
            return true;
        }
        if(in_array(substr($firmware_type, 0, 3), array('72_', '74_', '76_', '78_','80_'))){
            return true;
        }

        return false;
    }

    public static function getFirmwareType($firmware = '')
    {
        if(empty($firmware))
        {
            return 0;
        }

        if (substr($firmware, 0, 1) == 'V') {
            $firmware = substr($firmware, 1, strlen($firmware) - 1);
        }

        $_temp = explode('.', $firmware);
        if (count($_temp) < 3) {
            return 0;
        }

        $type = intval($_temp[1]);
        if (strpos($_temp[2], '_') !== false) {
            $pos = strpos($_temp[2], '_');
            $type .= substr($_temp[2], $pos, strlen($_temp[2]) - $pos);
        } else {

        }
        return $type;
    }
}
