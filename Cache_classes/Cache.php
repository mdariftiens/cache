<?php
/**
 * Driver Class
 *
 */

abstract class Cache{    
    abstract public function save($id, $data, $ttl = 60);
    abstract public function get( $id );
    abstract public function get_metadata( $id );
    abstract public function clean();
    abstract public function delete( $id );
    abstract public function is_supported();    
}
// END Driver CLASS
