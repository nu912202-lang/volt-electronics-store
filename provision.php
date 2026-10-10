<?php
// Run only from the OpenServer command line: php provision.php
if (PHP_SAPI !== 'cli') exit;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host=getenv('VOLT_DB_HOST') ?: 'MySQL-8.4';
$connectionHost=$host;
$connectionPort=3306;
if(preg_match('/^([^:]+):(\d+)$/',$host,$match)) { $connectionHost=$match[1]; $connectionPort=(int)$match[2]; }
$root=new mysqli($connectionHost,'root',getenv('VOLT_DB_ROOT_PASSWORD') ?: '', '', $connectionPort);
$root->set_charset('utf8mb4');
$db='volt_electronics';
$password=bin2hex(random_bytes(20));
if(file_exists(__DIR__.'/wordpress/wp-config.php')) {
    fwrite(STDERR,"wp-config.php already exists; refusing to overwrite.\n"); exit(1);
}
$exists=$root->query("SHOW DATABASES LIKE 'volt_electronics'");
if($exists->num_rows){fwrite(STDERR,"Database already exists; refusing to change it.\n");exit(1);}
$root->query("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$root->query("CREATE USER 'volt_student'@'%' IDENTIFIED BY '$password'");
$root->query("GRANT ALL PRIVILEGES ON `$db`.* TO 'volt_student'@'%'");
$config="<?php\n";
foreach(['DB_NAME'=>$db,'DB_USER'=>'volt_student','DB_PASSWORD'=>$password,'DB_HOST'=>$host,'DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>''] as $key=>$value)$config.='define('.var_export($key,true).', '.var_export($value,true).");\n";
foreach(['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $key)$config.='define('.var_export($key,true).', '.var_export(bin2hex(random_bytes(32)),true).");\n";
$config.="\$table_prefix = 'volt_';\ndefine('WP_DEBUG', false);\ndefine('DISALLOW_FILE_EDIT', true);\nif (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');\nrequire_once ABSPATH . 'wp-settings.php';\n";
if(!file_put_contents(__DIR__.'/wordpress/wp-config.php',$config))throw new RuntimeException('Cannot write config');
echo "Database and wp-config.php created.\n";
