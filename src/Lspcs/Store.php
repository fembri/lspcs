<?php namespace Lspcs;

use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

class Store extends \Illuminate\Session\Store {

	protected $persistDataReliability = false;
	protected $transactionId = null;
	
	/**
	 * {@inheritdoc}
	 */
	public function start()
	{
		$this->loadSession();

		if ( ! $this->has('_token')) $this->regenerateToken();

		return $this->started = true;
	}
	
	public function persistentMode($activate = true)
	{
		$this->persistDataReliability = $activate === true;
	}
	
	public function putAttributes($attributes)
	{
		$this->attributes = $attributes;
	}
	
	public function setAttributes($key, $value)
	{
		array_set($this->attributes, $key, $value);
	}
	
	public function forgetAttributes($key)
	{
		array_forget($this->attributes, $key);
	}

	/**
	 * Load the session data from the handler.
	 *
	 * @return void
	 */
	protected function loadSession()
	{
		$this->attributes = $this->readFromHandler();

		foreach (array_merge($this->bags, array($this->metaBag)) as $bag)
		{
			$this->initializeLocalBag($bag);

			$bag->initialize($this->bagData[$bag->getStorageKey()]);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function save()
	{
		$this->addBagDataToSession();

		$this->ageFlashData();
		
		$this->saveToHandler();

		$this->started = false;
	}


	/**
	 * {@inheritdoc}
	 */
	public function get($name, $default = null)
	{
		if ($this->persistDataReliability)
		{
			$this->attributes = $this->readFromHandler();
		}
		return array_get($this->attributes, $name, $default);
	}


	/**
	 * {@inheritdoc}
	 */
	public function set($name, $value, $directCall = true)
	{
		if ($this->persistDataReliability && $directCall) {
			$fileSystem = $this;
			$this->handler->transaction(function() use ($fileSystem, $name, $value)
			{
				$fileSystem->putAttributes($fileSystem->readFromHandler());
				$fileSystem->setAttributes($name, $value);
				$fileSystem->saveToHandler();
			});
		} else 
			$this->setAttributes($name, $value);
	}


	/**
	 * Put a key / value pair or array of key / value pairs in the session.
	 *
	 * @param  string|array  $key
	 * @param  mixed|null  	 $value
	 * @return void
	 */
	public function put($key, $value = null)
	{
		if ( ! is_array($key)) $key = array($key => $value);
		if ($this->persistDataReliability) {
			$fileSystem = $this;
			$this->handler->transaction(function() use ($fileSystem, $key)
			{
				$fileSystem->putAttributes($fileSystem->readFromHandler());
			
				foreach ($key as $arrayKey => $arrayValue)
				{
					$fileSystem->set($arrayKey, $arrayValue, false);
				}
				
				$fileSystem->saveToHandler();
			});
		} else {
			foreach ($key as $arrayKey => $arrayValue)
			{
				$this->set($arrayKey, $arrayValue, false);
			}
		}
	}
	
	public function getAttributes($key= null)
	{
		if (!$key) return $this->attributes;
		return array_get($this->attributes, $key, null);
	}
	
	public function transaction($callback)
	{
		$transactionId = md5(rand(0, 10)*rand());
		if (!$this->transactionId) {
			$this->transactionId = $transactionId;
			$this->handler->lock($this->getId());
			$this->putAttributes($this->readFromHandler());
		}
		
		$return = call_user_func($callback);
		
		if ($this->transactionId == $transactionId) {
			$this->transactionId = null;
			$this->saveToHandler();
			$this->handler->lock($this->getId(), false);
		}
		
		return $return;
	}
	

	/**
	 * Remove an item from the session.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		if ($this->persistDataReliability) {
			$fileSystem = $this;
			$this->handler->transaction(function() use ($fileSystem, $key)
			{
				$fileSystem->putAttributes($fileSystem->readFromHandler());
				
				$fileSystem->forgetAttributes($key);
				
				$fileSystem->saveToHandler();
			});
		} else 
			$this->forgetAttributes($key);
	}

	public function saveToHandler()
	{
		$this->handler->write($this->getId(), serialize($this->attributes));
	}
}
