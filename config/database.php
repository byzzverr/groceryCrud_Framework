<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


$active_group = 'default';
$active_record = TRUE;

$db['default'] = array(
        'dsn'   => '',
        // 'hostname' => '41.185.26.138',
        // 'username' => 'spazappoffice',
        // 'password' => 'Ch3wb4cc4!',

        // 'database' => 'spaza',
        'hostname' => 'localhost',
        'username' => 'root',
        //'password' => 'fahq&FU2',
        'password' => '',
        //'database' => 'supps365',
        //'database' => 'insurapp',
        'database' => 'stokvel',
        //'database' => 'spaza',

        'dbdriver' => 'mysqli',
        'dbprefix' => '',
        'pconnect' => FALSE,
        'db_debug' => TRUE,
        'cache_on' => FALSE,
        'cachedir' => '',
        'char_set' => 'utf8',
        'dbcollat' => 'utf8_general_ci',
        'swap_pre' => '',
        'encrypt' => FALSE,
        'compress' => FALSE,
        'stricton' => FALSE,
        'failover' => array(),
        'save_queries' => TRUE);


$db['insurapp'] = array(
        'dsn'   => '',
        'hostname' => '41.185.26.138',
        'username' => 'spazappoffice',
        'password' => 'Ch3wb4cc4!',
        'database' => 'spaza',

        'hostname' => 'localhost',
        'username' => 'root',
        'password' => 'fahq&FU2',
        'password' => '',
        'database' => 'supps365',
        'database' => 'spaza',
        'database' => 'insurapp',

        'dbdriver' => 'mysqli',
        'dbprefix' => '',
        'pconnect' => FALSE,
        'db_debug' => TRUE,
        'cache_on' => FALSE,
        'cachedir' => '',
        'char_set' => 'utf8',
        'dbcollat' => 'utf8_general_ci',
        'swap_pre' => '',
        'encrypt' => FALSE,
        'compress' => FALSE,
        'stricton' => FALSE,
        'failover' => array(),
        'save_queries' => TRUE);

