<?php

$conf['db']['default']['host'] = '127.0.0.1';
$conf['db']['default']['user'] = 'doctrine';
$conf['db']['default']['password'] = '0ry8xd1fz9ubr5';
$conf['db']['default']['database'] = 'doctrine';
$conf['db']['default']['port'] = NULL;
$conf['db']['default']['charset'] = 'utf8';

if (getenv('TRAVIS') === 'true') {
  $conf['db']['default']['user'] = 'root';
  $conf['db']['default']['password'] = '';
}

$conf['db']['tests'] = $conf['db']['default'];
$conf['db']['tests']['database'] = 'doctrine_tests';
