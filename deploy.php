<?php

namespace Deployer;

require 'recipe/laravel.php';
require 'contrib/rsync.php';

set('application', 'SNAC');
set('ssh_multiplexing', true);
set('repository', 'git@github.com:snac-cooperative/snac.git');

set('rsync_src', function () {
    return __DIR__;
});

add('rsync', [
    'exclude' => [
        '.git',
        '/.env',
        '/vendor/',
        '.github',
        'deploy.php',
    ],
]);

task('deploy:secrets', function () {
    file_put_contents(__DIR__ . '/.env', getenv('DOT_ENV'));
    upload('.env', get('deploy_path') . '/shared');
});

host('snaccooperative.org')
  ->set('hostname', 'snaccooperative.org')
  ->set('labels', ['env' => 'production', 'stage' => 'production'])
  ->set('remote_user', 'snacworker')
  ->set('branch', 'master')
  ->set('deploy_path', '/lv2/snac');

host('snac-dev.iath.virginia.edu')
  ->set('hostname', 'snac-dev.iath.virginia.edu')
  ->set('labels', ['env' => 'development', 'stage' => 'development'])
  ->set('remote_user', 'snacworker')
  ->set('branch', 'development')
  ->set('deploy_path', '/lv2/snac');

after('deploy:failed', 'deploy:unlock');

desc('Deploy the application');

task('deploy', [
    'deploy:unlock',
    'deploy:prepare',
    'deploy:secrets',
    'deploy:vendors',
    'deploy:symlink',
    'deploy:cleanup',
]);
