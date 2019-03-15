<?php
/*
 * Copyright (c) 2019 Tuukka Norri
 * This code is licensed under MIT license (see LICENSE for details).
 */

namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;


class Redirection
{
	protected $destination = null;
	protected $statusCode = 0;
	
	public function __construct(string $destination, int $statusCode)
	{
		$this->destination = $destination;
		$this->statusCode = $statusCode;
	}
	
	public function getDestination() { return $this->destination; }
	public function getStatusCode() { return $this->statusCode; }
}


class PrefixRedirection extends Redirection
{
	protected $source = null;
	protected $removeSuffix = false;
	
	public function __construct(string $source, string $destination, int $statusCode, bool $removeSuffix)
	{
		parent::__construct($destination, $statusCode);
		$this->source = $source;
		$this->removeSuffix = $removeSuffix;
	}
	
	public function getSource() { return $this->source; }
	public function shouldRemoveSuffix() { return $this->removeSuffix; }
}


/**
 * Class RedirectPlugin
 * @package Grav\Plugin
 */
class RedirectPlugin extends Plugin
{
	protected $exactPaths = null;
	protected $pathPrefixes = null;
	
	
	/**
	 * @return array
	 *
	 * The getSubscribedEvents() gives the core a list of events
	 * that the plugin wants to listen to. The key of each
	 * array section is the event that the plugin listens to
	 * and the value (in the form of an array) contains the
	 * callable (or function) as well as the priority. The
	 * higher the number the higher the priority.
	 */
	public static function getSubscribedEvents()
	{
		return [
			'onPluginsInitialized' => ['onPluginsInitialized', 0]
		];
	}
	
	
	/**
	 * Initialize the plugin.
	 */
	public function onPluginsInitialized()
	{
		// Don’t proceed if we are in the admin plugin.
		if ($this->isAdmin())
			return;
		
		// Get and transform the configuration.
		$this->exactPaths = array();
		$this->pathPrefixes = array();
		
		foreach ($this->config->get('plugins.redirect.exactPaths') as $rdr)
		{
			$this->exactPaths[$rdr["path"]] = new Redirection(
				$rdr["destination"],
				intval($rdr["statusCode"])
			);
		}
		
		foreach ($this->config->get('plugins.redirect.pathPrefixes') as $rdr)
		{
			$this->pathPrefixes[] = new PrefixRedirection(
				$rdr["path"],
				$rdr["destination"],
				intval($rdr["statusCode"]),
				"1" === $rdr["removeSuffix"]
			);
		}
		
		// Enable the main event we are interested in.
		// Run before the error plugin by setting the priority to 1.
		$this->enable([
			'onPageNotFound' => ['onPageNotFound', 1]
		]);
	}
	
	
	/**
	 * In case a page was not found, try to find a redirection target.
	 *
	 * @param Event $e
	 */
	public function onPageNotFound(Event $e)
	{
		$uri = $this->grav['uri'];
		
		// Apparently this is the only way to get the path with the language identifier.
		$pathWithLanguage = $_SERVER['REQUEST_URI'];
		
		// Try with an exact key.
		if (array_key_exists($pathWithLanguage, $this->exactPaths))
		{
			$rdr = $this->exactPaths[$pathWithLanguage];
			$dst = $rdr->getDestination();
			$statusCode = $rdr->getStatusCode();
			$this->grav->redirectLangSafe($dst, $statusCode);
			$event->stopPropagation();
			return;
		}
		
		// Try with the prefixes.
		foreach ($this->pathPrefixes as $rdr)
		{
			// Allow either exact matching or matching at path component boundary only.
			$rdrPath = $rdr->getSource();
			if ($rdrPath === $pathWithLanguage)
			{
				$dst = $rdr->getDestination();
				$statusCode = $rdr->getStatusCode();
				$this->grav->redirectLangSafe($dst, $statusCode);
				$event->stopPropagation();
				return;
			}
			
			// Try with a prefix.
			if ('/' != substr($rdrPath, -1))
				$rdrPath .= '/';
			$rdrLen = strlen($rdrPath);
			
			if ($rdrLen <= strlen($pathWithLanguage) && 0 === strcmp($rdrPath, substr($pathWithLanguage, 0, $rdrLen)))
			{
				$dst = $rdr->getDestination();
				if (!$rdr->shouldRemoveSuffix())
				{
					// Add the slash since we required it in the end of $rdrPath.
					$dst .= '/';
					$dst = substr_replace($pathWithLanguage, $dst, 0, $rdrLen);
				}
				
				$statusCode = $rdr->getStatusCode();
				$this->grav->redirectLangSafe($dst, $statusCode);
				$event->stopPropagation();
				return;
			}
		}
	}
}
