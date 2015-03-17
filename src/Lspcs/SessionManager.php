<?php namespace Lspcs;

class SessionManager extends \Illuminate\Session\SessionManager {
	/**
	 * Build the session instance.
	 *
	 * @param  \SessionHandlerInterface  $handler
	 * @return \Lspcs\Store
	 */
	protected function buildSession($handler)
	{
		return new Store($this->app['config']['session.cookie'], $handler);
	}
	
	
	/**
	 * Create an instance of the file session driver.
	 *
	 * @return \Lspcs\Store
	 */
	protected function createNativeDriver()
	{
		$path = $this->app['config']['session.files'];

		return $this->buildSession(new LockableFileSessionHandler(new LockableFileSystem, $path));
	}
}
