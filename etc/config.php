<?php

$conf['db']['default']['host'] = '127.0.0.1';
$conf['db']['default']['user'] = 'doctrine';
$conf['db']['default']['password'] = 'doctrine';
$conf['db']['default']['database'] = 'doctrine';
$conf['db']['default']['port'] = 3314;
$conf['db']['default']['charset'] = 'utf8';

if (getenv('TRAVIS') === 'true') {
  $conf['db']['default']['user'] = 'root';
  $conf['db']['default']['password'] = '';
  $conf['db']['default']['port'] = NULL;
}

$conf['db']['tests'] = $conf['db']['default'];
