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
     * holds supported type of cache system
     * 
     * @var type array
     */
    protected static $cache_type=[ 'auto', 'memcache', 'memcached', 'redis', 'file' ];
    
    /**
     * init class and setting 
     * @param type $config to setting initialized class
     */
    private function __construct($config) {}
    
    public static function getDriver($config){
        if ( empty($config) )die( "error:" . "config not provided" );
        
        if( empty($config['type'] )) die("\$config['Type'] is not supplied ");
        
        $type = $config['type'];
        if( ! in_array($config['type'], self::$cache_type ) ) die("Cache type '".$type."' not supported"); 
        
        unset($config['type']);
        if($type=='auto'){
            if( self::is_redis_support() ) {
                return new Cache_redis($config['config']);                
            }
            elseif(self::is_memcache_support() || self::is_memcached_support()){
                return new Cache_memcached($config['config']);                
            }
            else{
                return new Cache_file($config['config']);                
            }
        }
        elseif( ! self::{'is_'.$type.'_support'}() )
        {
                die("Cache type '".$type."' not supported");                
        }
        else {
            $class='Cache_'.$type ;
            return new $class($config['config']);
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
$cache=cache_factory::getDriver(['type' => 'file','config'=>'./cache']);


