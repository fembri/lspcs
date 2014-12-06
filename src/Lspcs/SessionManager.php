<?php namespace Lspcs;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;

class SessionManager extends Illuminate\Session\SessionManager {
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

}
