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
	protected $removeSuffix = false;
	
	public function __construct($destination, int $statusCode, bool $removeSuffix)
	{
		$this->destination = $destination;
		$this->statusCode = $statusCode;
		$this->removeSuffix = $removeSuffix;
	}
	
	public function getDestination() { return $this->destination; }
	public function getStatusCode() { return $this->statusCode; }
	public function shouldRemoveSuffix() { return $this->removeSuffix; }
}


/**
 * Class RedirectPlugin
 * @package Grav\Plugin
 */
class RedirectPlugin extends Plugin
{
	protected $redirects = null;
	
	
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
		$this->redirects = array();
		foreach ($this->config->get('plugins.redirect.redirects') as $rdr)
		{
			$this->redirects[$rdr["path"]] = new Redirection(
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
		if (array_key_exists($pathWithLanguage, $this->redirects))
		{
			$rdr = $this->redirects[$pathWithLanguage];
			$dst = $rdr->getDestination();
			$statusCode = $rdr->getStatusCode();
			$this->grav->redirectLangSafe($dst, $statusCode);
			$event->stopPropagation();
			return;
		}
		
		// Try with redirects as prefixes.
		foreach ($this->redirects as $rdrPath => $rdr)
		{
			// Allow matching at path component boundary only.
			if ('/' != substr($rdrPath, -1))
				$rdrPath .= '/';
			
			$rdrLen = strlen($rdrPath);
			if ($rdrLen < strlen($pathWithLanguage))
			{
				if (0 === strcmp($rdrPath, substr($pathWithLanguage, 0, $rdrLen)))
				{
					$dst = $rdr->getDestination();
					if (!$rdr->shouldRemoveSuffix())
						$dst = substr_replace($pathWithLanguage, $dst, 0, $rdrLen);
					
					$statusCode = $rdr->getStatusCode();
					$this->grav->redirectLangSafe($dst, $statusCode);
					$event->stopPropagation();
					return;
				}
			}
		}
	}
}
