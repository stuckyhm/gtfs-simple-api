<?php
/**
 * Create Redbean DAO
 */

class R extends RedBeanPHP\Facade{
    
    static function loadConfig($config){
        
        $conn = $config['connections'][$config['default']];       
        
        switch($conn['driver']){
            case 'mysql':
                self::setup($conn['driver'].':host='.$conn['host'].'; dbname='.$conn['database'], $conn['username'], $conn['password']);
                break;
            case 'sqlite':
                self::setup($conn['driver'].':'.$conn['database']);
                break;
        }
    }
    
}
R::loadConfig(require_once ROOT.'/app/config/database.php');
