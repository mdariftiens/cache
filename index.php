<?php
/**
 * Our system 
 */
require_once 'functions.php';

spl_autoload_register(function($name){
    
    $path='./Cache_classes/'.$name.".php";
    
    if(file_exists($path))
        require_once $path;
    else        
        require_once ($name.".php");
});



/**
 *  auto => will select best caching system of your server. :) 
 *  Priority: 
 *      Redis
 *      Memcached/memcache
 *      File
 */
$config=[
    'type'      => 'memcache', # ,memcache/memcached , reids, file, auto
    'default'   => [    'hostname'		=> 'localhost',
                        'port'		=> 1337,
                        'weight'	=> 1
                   ],
    

];

class cache_factory{
    /**
     * holds supported Cache type
     * 
     * @var type array
     */
    protected static $cache_type = [ 'auto', 'memcache', 'memcached', 'redis', 'file' ];
    
    /**
     * init class and setting 
     * @param type $config to setting initialized class
     */
    private function __construct() {}
    
    public static function getDriver($type,array $config){
        if ( empty($type) || empty($config))die( "error:" . "Proper parameter not provided" );
        
        if( ! in_array($type, self::$cache_type ) ) die("Cache type '".$type."' not supported"); 
        
        $type= strtolower($type);
        
        if($type=='auto'){
            if( self::is_redis_support() ) {
                return new Cache_redis($config);                
            }
            elseif(self::is_memcache_support() || self::is_memcached_support()){
                return new Cache_memcached($config);                
            }
            else{
                return new Cache_file($config);                
            }
        }
        elseif( ! self::{'is_'.$type.'_support'}() )
        {
                die("Cache type '".$type."' not supported");                
        }
        else {
            $class='Cache_'.$type ;
            return new $class($config);
        }
        
    }
    
    protected static function is_redis_support(){
        return class_exists('Redis',FALSE);
    }
    
    protected static function is_memcache_support(){
        return class_exists('Memcache',FALSE);
    }
    
    protected static function is_memcached_support(){
        return class_exists('Memcached',FALSE);
    }    
    
    protected static function is_file_support(){
        return true;
    }    
    
}

#new Cache_redis(['config'=>array()]);
$cache=cache_factory::getDriver('file',['path'=>'./cache']);


