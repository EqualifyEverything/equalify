<?php

class Routing {
	private $routesByPath;

	public function __construct($filepath) {
		$contents = file_get_contents($filepath);
		if (!$contents) {
			throw new Exception('No routing.json file');
		}

		$routes = json_decode($contents, TRUE);
		if (!$routes || empty($routes['routes'])) {
			throw new Exception('No routes in the routing.json file');
		}

		$this->routesByPath = [];
		foreach ($routes['routes'] as $route) {
			$this->routesByPath[$route['route']['path']] = $route;
		}
	}

	public function getRoute($path) {
		return !empty($this->routesByPath[$path]) ? $this->routesByPath[$path] : NULL;
	}
}
