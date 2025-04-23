<?php

namespace Anviz\Cloudkit\lib;

class AnvizConstants
{
    public const KEY = 'AnvizDevelopOpenKey';

    // General Commands
    public const CMD_LOGIN = 9001;                      // Login
    public const CMD_NOCOMMAND = 9002;                  // Heartbeat
    public const CMD_FORBIDDEN = 9003;                  // Disable connection
    public const CMD_REGESTER = 9004;                   // Register
    public const CMD_ERROR = 9005;                      // The command error
    public const CMD_GETRECORDUSERFPCOUNT = 9007;       // Get User & Record & FP number in device
    public const CMD_ENROLLCARD = 9008;                 // Remote registration card
    public const CMD_SETADMINPASSWORD = 9009;           // Modify the super admin password in device

    // Network Config
    public const CMD_GETNETWORK = 1003;                 // Get network parameters
    public const CMD_SETDATETIME = 1004;                // Config network parameters

    // Employee Data
    public const CMD_GETALLEMPLOYEE = 2001;             // Download all employees (excluding FP templates & attendance)
    public const CMD_PUTALLEMPLOYEE = 2101;             // Upload employee data in bulk
    public const CMD_GETONEEMPLOYEE = 2002;             // Download specified employee info
    public const CMD_PUTONEEMPLOYEE = 2102;             // Upload specified employee info
    public const CMD_DELETEALLEMPLOYEE = 2021;          // Clear all employees (and their FP templates)
    public const CMD_DELETEONEEMPLOYEE = 2022;          // Delete specified employee

    // Fingerprint Templates
    public const CMD_GETALLFINGER = 2031;               // Download all fingerprint templates
    public const CMD_PUTALLFINGER = 2131;               // Upload fingerprint templates in bulk
    public const CMD_GETONEFINGER = 2032;               // Download templates of specified employee
    public const CMD_PUTONEFINGER = 2132;               // Upload template of specified employee
    public const CMD_DELETEALLFINGER = 2041;            // Clear all fingerprint templates
    public const CMD_DELETEONEFINGER = 2042;            // Delete specified templates
    public const CMD_ENROLLFINGER = 2033;               // Remote registration fingerprint

    // Attendance Records
    public const CMD_GETALLRECORD = 3001;               // Download all attendance records
    public const CMD_GETNEWRECORD = 3002;               // Download new attendance records

    // Temperature Records
    public const CMD_GETNEWTEMPRECORD = 3005;           // Download new temperature records
    public const CMD_GETTEMPRECORDPIC = 3006;           // Download temperature pictures

    // Mask & Temp Config
    public const CMD_GETMASKTEMP = 1012;                // Get temperature measurement config
    public const CMD_SETMASKTEMP = 1112;                // Set temperature measurement config

    // Access Control
    public const CMD_OPENDOOR = 4003;                   // Open door
}
