<?php namespace Lspcs;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemIterator;
use Illuminate\Filesystem\FileNotFoundException;
use Symfony\Component\Finder\Finder;

class LockableFilesystem extends Filesystem {
	
	protected $locked = false;
	protected $handler = array();
	
	public function __construct()
	{		
		$fileSystem = $this;
		register_shutdown_function(function() use ($fileSystem)
		{
			if (!$fileSystem->handler) return;
			
			foreach($fileSystem->handler as $handler) {
				flock($handler, LOCK_UN);
				fclose($handler);
			}
		});
	}

	public function lock($path, $status)
	{
		if ($handler = $this->getHandler($path))
		{
			if ($status)
			{
				flock($handler, LOCK_EX);
			} else {
				flock($handler, LOCK_UN);
			}
		}
	}

	public function getHandler($path)
	{
		if (!isset($this->handler[$path]) || !$this->handler[$path])
		{
			$this->handler[$path] = fopen($path, "a+");
		}
		return $this->handler[$path];
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param  string  $path
	 * @return string
	 *
	 * @throws FileNotFoundException
	 */
	public function get($path)
	{
		if ($handler = $this->getHandler($path)) 
		{
			rewind($handler);
			$data = "";
			$size = filesize($path);
			while(!feof($handler))
				$data .= fgets($handler, $size ? $size : 8192);
			return $data;
		}
		throw new FileNotFoundException("File does not exist at path {$path}");
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param  string  $path
	 * @param  string  $contents
	 * @return int
	 */
	public function put($path, $contents, $lock = false)
	{
		if ($handler = $this->getHandler($path)) 
		{
			ftruncate($handler, 0);
			rewind($handler);
			fwrite($handler, $contents);
		}
	}

	/**
	 * Prepend to a file.
	 *
	 * @param  string  $path
	 * @param  string  $data
	 * @return int
	 */
	public function prepend($path, $data)
	{
		if ($this->exists($path))
		{
			return $this->put($path, $data.$this->get($path));
		}
		else
		{
			return $this->put($path, $data);
		}
	}

	/**
	 * Append to a file.
	 *
	 * @param  string  $path
	 * @param  string  $data
	 * @return int
	 */
	public function append($path, $data)
	{
		return file_put_contents($path, $data, FILE_APPEND);
	}
}
