<?php
/**
 * Memcached Caching Class
 *
 */
class Cache_memcached extends Cache{

        /**
         * Default host for memcache(d)
         */
        const DEFAULT_HOST      = 'localhost';
        
        /**
         * Default port for memcache(d)
         */
        const DEFAULT_PORT      = 1337;
        
        /**
         * Default weight for memcache(d)
         */
        const DEFAULT_WEIGHT    = 1;
        
	/**
	 * Holds the memcached object
	 *
	 * @var object
	 */
	protected $_memcached;

	/**
	 * Memcached configuration
	 *
	 * @var array
	 */
	protected $_config = array(
			'hostname'	=> self::DEFAULT_HOST,
			'port'		=> 1337,
			'weight'	=> 1
	);

	// ------------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Setup Memcache(d)
	 * @param       array $config ( Optional ) Must have key 'hostname' , 'port' , 'weight'
	 * @return	void
	 */
	public function __construct($config=array())
	{
                if( ! empty($config) ) $this->_config=$config;
                    
		if (class_exists('Memcached', FALSE))
		{
			$this->_memcached = new Memcached();
		}
		elseif (class_exists('Memcache', FALSE))
		{
			$this->_memcached = new Memcache();
		}
		else
		{
			error_log('error'. 'Cache: Failed to create Memcache(d) object; extension not loaded?');
			return;
		}
                    
                $cache_server = $this->_config;    
                    
                if ( ! isset($cache_server['hostname']))
                {
                        error_log('debug'. 'Cache: Memcache(d) configuration "'.$cache_name.'" doesn\'t include a hostname; ignoring.');

                }
                elseif ($cache_server['hostname'][0] === '/')
                {
                        $cache_server['port'] = 0;
                }
                elseif (empty($cache_server['port']))
                {
                        $cache_server['port'] = self::DEFAULT_PORT;
                }

                isset($cache_server['weight']) OR $cache_server['weight'] = self::DEFAULT_WEIGHT;

                if ($this->_memcached instanceof Memcache)
                {
                        // Third parameter is persistence and defaults to TRUE.
                        $this->_memcached->addServer(
                                $cache_server['hostname'],
                                $cache_server['port'],
                                TRUE,
                                $cache_server['weight']
                        );
                }
                elseif ($this->_memcached instanceof Memcached)
                {
                        $this->_memcached->addServer(
                                $cache_server['hostname'],
                                $cache_server['port'],
                                $cache_server['weight']
                        );
                }
                        
                       
	}

	// ------------------------------------------------------------------------

	/**
	 * Fetch from cache
	 *
	 * @param	string	$id	Cache ID
	 * @return	mixed	Data on success, FALSE on failure
	 */
	public function get($id)
	{
		$data = $this->_memcached->get($id);

		return is_array($data) ? $data[0] : $data;
	}

	// ------------------------------------------------------------------------

	/**
	 * Save
	 *
	 * @param	string	$id	Cache ID
	 * @param	mixed	$data	Data being cached
	 * @param	int	$ttl	Time to live
	 * @param	bool	$raw	Whether to store the raw value
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($id, $data, $ttl = 60, $raw = FALSE)
	{
		if ($raw !== TRUE)
		{
			$data = array($data, time(), $ttl);
		}

		if ($this->_memcached instanceof Memcached)
		{
			return $this->_memcached->set($id, $data, $ttl);
		}
		elseif ($this->_memcached instanceof Memcache)
		{
			return $this->_memcached->set($id, $data, 0, $ttl);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from Cache
	 *
	 * @param	mixed	$id	key to be deleted.
	 * @return	bool	true on success, false on failure
	 */
	public function delete($id)
	{
		return $this->_memcached->delete($id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Increment a raw value
	 *
	 * @param	string	$id	Cache ID
	 * @param	int	$offset	Step/value to add
	 * @return	mixed	New value on success or FALSE on failure
	 */
	public function increment($id, $offset = 1)
	{
		return $this->_memcached->increment($id, $offset);
	}

	// ------------------------------------------------------------------------

	/**
	 * Decrement a raw value
	 *
	 * @param	string	$id	Cache ID
	 * @param	int	$offset	Step/value to reduce by
	 * @return	mixed	New value on success or FALSE on failure
	 */
	public function decrement($id, $offset = 1)
	{
		return $this->_memcached->decrement($id, $offset);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the Cache
	 *
	 * @return	bool	false on failure/true on success
	 */
	public function clean()
	{
		return $this->_memcached->flush();
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @return	mixed	array on success, false on failure
	 */
	public function cache_info()
	{
		return $this->_memcached->getStats();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	mixed	$id	key to get cache metadata on
	 * @return	mixed	FALSE on failure, array on success.
	 */
	public function get_metadata($id)
	{
		$stored = $this->_memcached->get($id);

		if (count($stored) !== 3)
		{
			return FALSE;
		}

		list($data, $time, $ttl) = $stored;

		return array(
			'expire'	=> $time + $ttl,
			'mtime'		=> $time,
			'data'		=> $data
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Is supported
	 *
	 * Returns FALSE if memcached is not supported on the system.
	 * If it is, we setup the memcached object & return TRUE
	 *
	 * @return	bool
	 */
	public function is_supported()
	{
		return (extension_loaded('memcached') OR extension_loaded('memcache'));
	}

	// ------------------------------------------------------------------------

	/**
	 * Class destructor
	 *
	 * Closes the connection to Memcache(d) if present.
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if ($this->_memcached instanceof Memcache)
		{
			$this->_memcached->close();
		}
		elseif ($this->_memcached instanceof Memcached && method_exists($this->_memcached, 'quit'))
		{
			$this->_memcached->quit();
		}
	}
}
