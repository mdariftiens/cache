<?php
/**
 * Redis Caching Class
 *
 */
class Cache_redis extends Cache
{
	/**
	 * Default config
	 *
	 * @static
	 * @var	array
	 */
	protected  $_default_config = array(
		'host' => '127.0.0.1',
		'password' => NULL,
		'port' => 6379,
		'timeout' => 0,
		'database' => 0
	);

	/**
	 * Redis connection
	 *
	 * @var	Redis
	 */
	protected $_redis;

	// ------------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Setup Redis
	 *
	 * Loads Redis config file if present. Will halt execution
	 * if a Redis connection can't be established.
	 *
	 * @return	void
	 * @see		Redis::connect()
	 */
	public function __construct($config)
	{

                if( empty($config) || ! is_array($config))    $config = $this->_default_config;
		
		$this->_redis = new Redis();

		try
		{
			if ( ! $this->_redis->connect($config['host'], ($config['host'][0] === '/' ? 0 : $config['port']), $config['timeout']))
			{
				error_log('error'. 'Cache: Redis connection failed. Check your configuration.');
			}

			if (isset($config['password']) && ! $this->_redis->auth($config['password']))
			{
				error_log('error'. 'Cache: Redis authentication failed.');
			}

			if (isset($config['database']) && $config['database'] > 0 && ! $this->_redis->select($config['database']))
			{
				error_log('error'. 'Cache: Redis select database failed.');
			}
		}
		catch (RedisException $e)
		{
			error_log('error'. 'Cache: Redis connection refused ('.$e->getMessage().')');
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache
	 *
	 * @param	string	$key	Cache ID
	 * @return	mixed
	 */
	public function get($key)
	{
		$data = $this->_redis->hMGet($key, array('__type', '__value'));

		if ( ! isset($data['__type'], $data['__value']) OR $data['__value'] === FALSE)
		{
			return FALSE;
		}

		switch ($data['__type'])
		{
			case 'array':
			case 'object':
				return unserialize($data['__value']);
			case 'boolean':
			case 'integer':
			case 'double': // Yes, 'double' is returned and NOT 'float'
			case 'string':
			case 'NULL':
				return settype($data['__value'], $data['__type'])
					? $data['__value']
					: FALSE;
			case 'resource':
			default:
				return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Save cache
	 *
	 * @param	string	$id	Cache ID
	 * @param	mixed	$data	Data to save
	 * @param	int	$ttl	Time to live in seconds
	 * @param	bool	$raw	Whether to store the raw value (unused)
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($id, $data, $ttl = 60, $raw = FALSE)
	{
		switch ($data_type = gettype($data))
		{
			case 'array':
			case 'object':
				$data = serialize($data);
				break;
			case 'boolean':
			case 'integer':
			case 'double': // 'double' for 'Float'
			case 'string':
			case 'NULL':
				break;
			case 'resource':
			default:
				return FALSE;
		}

		if ( ! $this->_redis->hMSet($id, array('__type' => $data_type, '__value' => $data)))
		{
			return FALSE;
		}
		elseif ($ttl)
		{
			$this->_redis->expireAt($id, time() + $ttl);
		}

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from cache
	 *
	 * @param	string	$key	Cache key
	 * @return	bool
	 */
	public function delete($key)
	{
		return ($this->_redis->delete($key) === 1);
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
		return $this->_redis->hIncrBy($id, 'data', $offset);
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
		return $this->_redis->hIncrBy($id, 'data', -$offset);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean cache
	 *
	 * @return	bool
	 * @see		Redis::flushDB()
	 */
	public function clean()
	{
		return $this->_redis->flushDB();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache driver info
	 *
	 * @param	string	$type	Not supported in Redis.
	 *				Only included in order to offer a
	 *				consistent cache API.
	 * @return	array
	 * @see		Redis::info()
	 */
	public function cache_info($type = NULL)
	{
		return $this->_redis->info();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache metadata
	 *
	 * @param	string	$key	Cache key
	 * @return	array
	 */
	public function get_metadata($key)
	{
		$value = $this->get($key);

		if ($value !== FALSE)
		{
			return array(
				'expire' => time() + $this->_redis->ttl($key),
				'data' => $value
			);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if Redis driver is supported
	 *
	 * @return	bool
	 */
	public function is_supported()
	{
		return extension_loaded('redis');
	}

	// ------------------------------------------------------------------------

	/**
	 * Class destructor
	 *
	 * Closes the connection to Redis if present.
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if ($this->_redis)
		{
			$this->_redis->close();
		}
	}
}


/*

https://www.linuxtweaks.in/install-phpredis-redis-server-in-centos/
https://anton.logvinenko.name/en/blog/how-to-install-redis-and-redis-php-client.html

sudo service apache2 restart

php -r "if (new Redis() == true){ echo \"OK \r\n\"; }"

*/

