<?php namespace Lspcs;

use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

class Store extends Illuminate\Session\Store {

	/**
	 * {@inheritdoc}
	 */
	public function start()
	{
		$this->loadSession();

		if ( ! $this->has('_token')) $this->regenerateToken();

		return $this->started = true;
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

		$this->handler->write($this->getId(), serialize($this->attributes));

		$this->started = false;
	}


	/**
	 * {@inheritdoc}
	 */
	public function get($name, $default = null)
	{
		return array_get($this->attributes, $name, $default);
	}


	/**
	 * {@inheritdoc}
	 */
	public function set($name, $value)
	{
		array_set($this->attributes, $name, $value);
	}


	/**
	 * Put a key / value pair or array of key / value pairs in the session.
	 *
	 * @param  string|array  $key
	 * @param  mixed|null  	 $value
	 * @return void
	 */
	public function put($key, $value)
	{
		if ( ! is_array($key)) $key = array($key => $value);

		foreach ($key as $arrayKey => $arrayValue)
		{
			$this->set($arrayKey, $arrayValue);
		}
	}
	

	/**
	 * Remove an item from the session.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		array_forget($this->attributes, $key);
	}

}
