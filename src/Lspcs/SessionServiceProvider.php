<?php namespace Lspcs;

class SessionServiceProvider extends \Illuminate\Session\SessionServiceProvider {

	/**
	 * Register the session manager instance.
	 *
	 * @return void
	 */
	protected function registerSessionManager()
	{
		$this->app->bindShared('session', function($app)
		{
			return new SessionManager($app);
		});
	}

}
