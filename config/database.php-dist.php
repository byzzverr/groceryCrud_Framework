<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
        'dsn'   => '',
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => 'xxx',
        'database' => 'spaza',

        'dbdriver' => 'mysqli',
        'dbprefix' => '',
        'pconnect' => TRUE,
        'db_debug' => TRUE,
        'cache_on' => FALSE,
        'cachedir' => '',
        'char_set' => 'utf8',
        'dbcollat' => 'utf8_general_ci',
        'swap_pre' => '',
        'encrypt' => FALSE,
        'compress' => FALSE,
        'stricton' => FALSE,
        'failover' => array()
);

/*
CREATE USER 'mvnx_rica'@'localhost' IDENTIFIED BY '3rdP4rtyP455!';
GRANT ALL PRIVILEGES ON * . * TO 'mvnx_rica'@'localhost';


$db['insurapp'] = array(
        'dsn'   => '',
        'hostname' => '41.185.26.138',
        'username' => 'spazappoffice',
        'password' => 'Ch3wb4cc4!',
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => 'xxx',
        'database' => 'insurapp',
        'dbdriver' => 'mysqli',
        'dbprefix' => '',
        'pconnect' => TRUE,
        'db_debug' => TRUE,
        'cache_on' => FALSE,
        'cachedir' => '',
        'char_set' => 'utf8',
        'dbcollat' => 'utf8_general_ci',
        'swap_pre' => '',
        'encrypt' => FALSE,
        'compress' => FALSE,
        'stricton' => FALSE,
        'failover' => array()
);*/