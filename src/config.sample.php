<?php

/**
 * Created by Jacobs<jacobs@anviz.com>.
 * Date: 18-5-7
 * Time: 17:16
 * File Name: config.sample.php
 */

$config['db']['dbdriver'] = 'mysqli';
$config['db']['host'] = 'mysql:3306';
$config['db']['dbname'] = 'sdkdemo';
$config['db']['username'] = 'root';
$config['db']['password'] = 'root';

$config['logs']['debug'] = true;
$config['logs']['path'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs';

/**
 * The communication instructions of device
 */

define('KEY', 'AnvizDevelopOpenKey');
define('CMD_LOGIN', 9001); //Login
define('CMD_NOCOMMAND', 9002); //Heartbeat
define('CMD_FORBIDDEN', 9003); //Disable connection
define('CMD_REGESTER', 9004); //Register
define('CMD_ERROR', 9005); //The command error
define('CMD_GETRECORDUSERFPCOUNT', 9007); //Get User & Record & FP number in device

define('CMD_GETNETWORK', 1003); //Get network parameters
define('CMD_SETDATETIME', 1004); //Config network parameters
define('CMD_SETADMINPASSWORD', 9009); //Modify the super admin password in device

define('CMD_GETALLEMPLOYEE', 2001); //Download all employees from device(Does not include fingerprint template and attendance records)
define('CMD_PUTALLEMPLOYEE', 2101); //Upload employee data in bulk
define('CMD_GETONEEMPLOYEE', 2002); //Download the specified employee information
define('CMD_PUTONEEMPLOYEE', 2102); //Upload the specified employee information
define('CMD_DELETEALLEMPLOYEE', 2021); //Clear all employees form deivce(And clear all fingerprint template, but not clear attendance records)
define('CMD_DELETEONEEMPLOYEE', 2022); //Delete the specified employee from device

define('CMD_GETALLFINGER', 2031); //Download all fingerprint templates from device
define('CMD_PUTALLFINGER', 2131); //Upload the fingerprint templates in bulk
define('CMD_GETONEFINGER', 2032); //download templates of the specified employee
define('CMD_PUTONEFINGER', 2132); //Upload template of the specified employee
define('CMD_DELETEALLFINGER', 2041); //Clear all fingerprint templates from device(But not clear employee and attendance records)
define('CMD_DELETEONEFINGER', 2042); //Delete templates of the specified from device
define('CMD_ENROLLFINGER', 2033); //Remote registration fingerprint
define('CMD_ENROLLCARD', 9008); //Remote registration card

define('CMD_GETALLRECORD', 3001); //Download all attendance records form device
define('CMD_GETNEWRECORD', 3002); //Download new attendance records from device

define('CMD_GETNEWTEMPRECORD', 3005); //Download new temperature records from device
define('CMD_GETTEMPRECORDPIC', 3006); //Download temperature picture from device

define('CMD_SETMASKTEMP', 1112);//Set the temperature measurement configuration
define('CMD_GETMASKTEMP', 1012);//Get the temperature measurement configuration

define('CMD_OPENDOOR', 4003);//Open door
