<?php
/*
   应用程序可以定义以下常量:
    __X_IN_FRAME__     该值只能为true,
    __X_SHOW_ERROR__   是否显示错误异常和trace信息
    __X_APP_ROOT__     设置用户应用程序所在目录,默认为web访问根目录
                       true 将在页面中输出，但是不将错误信息记录到日志中
                       false 将不在页面中显示，但是会将错日志写入日志文件中
                       null  将屏蔽所有错误信息的显示，并且不记录到日志文件中
    __X_EXCEPTION_LEVEL__ 设置异常等级, 
                          0 为所有信息，
                          1 将不抛出notice信息，
                          2 将不抛出Warning和notice信息
    __X_APP_DATA_DIR_NAME__ 数据目录名__X_APP_ROOT__目录下面

    __X_APP_DATA_DIR__ 数据存储路径,本值如果设置，__X_APP_DATA_DIR_NAME__将无效

   然后在WEB目录中的index.php文件中包含本文件,该文件应当是唯一的脚本执行文件
   在nginx 可以如下配置，指定所有PHP都将SCRIPT_FILENAME指定为该文件
        fastcgi_param  SCRIPT_FILENAME    WEB目录下的/demo/index.php;
   该index.php文件定义类似下列代码:
    [CODE]
        define('__X_IN_FRAME__', true);
        define('__X_SHOW_ERROR__',true);
        define('__X_APP_ROOT__', dirname(__FILE__).'/mysite');
        include_once(dirname(dirname(__FILE__)) . '/fw-2.2/__init__.php');
    [/CODE]
*/

/******用户定义常量检查开始********************/
defined('__X_IN_FRAME__') ||  define('__X_IN_FRAME__', true);
defined('__X_SHOW_ERROR__') || define('__X_SHOW_ERROR__',true); 
defined('__X_EXCEPTION_LEVEL__') || define('__X_EXCEPTION_LEVEL__',2);
defined('__X_APP_DATA_DIR_NAME__') || define('__X_APP_DATA_DIR_NAME__','var');
defined('__X_APP_DATA_DIR__') || define('__X_APP_DATA_DIR__',__X_APP_ROOT__.'/'.__X_APP_DATA_DIR_NAME__);

/******用户定义常量结束********************/

define('__X_FRAMEWORK_ROOT__', __DIR__); //不要修改本常量
set_include_path(get_include_path(). PATH_SEPARATOR . __DIR__);
$start_time = microtime(true);
clearstatcache();
if(PHP_SAPI ==  'cli' && !isset($_SERVER['argv'])) {
    $_SERVER['argc'] = $argc;
    $_SERVER['argv'] = $argv;
}
include_once('XFunction.php');
spl_autoload_register('XAutoload');
set_error_handler('error2debug');
register_shutdown_function('XExitAlert');
include_once('XDataStruct.php');
try {
    $_X_APP_RUNING =  new XScheduler();
} catch(XException $e) {
    echo $e->getXDebugTraceAsString();
}
exit;