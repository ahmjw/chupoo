<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 * @link http://www.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */

namespace Chupoo\Views;

class LayoutDom
{
	private $dom;
	private $content;
	private $data;
	private $config;
	private $scripts = array();

	public $doCloning = true;

	public function __construct($content, $data, $config)
	{
		$this->content = $content;
		$this->data = $data;
		$this->config = $config;
	}

	public function getHtml()
	{
		if (empty($this->content)) return;

		$content = $this->dom->saveHTML();
		return html_entity_decode($content);
	}

	public function getLayoutData()
	{
		if (empty($this->content)) return;

		$this->dom = new \DomDocument();
		$content = mb_convert_encoding($this->content, 'HTML-ENTITIES', 'UTF-8');
		@$this->dom->loadHTML($content);

		$head = $this->dom->getElementsByTagName('head')->item(0);
		$body = $this->dom->getElementsByTagName('body')->item(0);
		
		return array(
			'head' => $head->ownerDocument->saveHTML($head),
			'body' => $body->ownerDocument->saveHTML($body),
		);
	}

	private function appendHtml(\DOMNode $parent, $content) 
	{
		$temp = new \DOMDocument();
		$content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
		@$temp->loadHTML($content);

		if ($temp->getElementsByTagName('body')->item(0)->childNodes) {
			foreach ($temp->getElementsByTagName('body')->item(0)->childNodes as $node) {
				$node = @$parent->ownerDocument->importNode($node, true);
				$parent->appendChild($node);
			}
		}
	}

	public function parse()
	{
		if (empty($this->content)) return;

		$this->dom = new \DomDocument();
		// $content = mb_convert_encoding($this->content, 'HTML-ENTITIES', 'UTF-8');
		$content = $this->content;
		@$this->dom->loadHTML($content);

		$this->applyVisibility();

		// Layout importing
		$nodes = $this->dom->getElementsByTagName('c.import');
		foreach ($nodes as $node) {
			$name = Controller::getInstance()->config['layout_dir'] . DIRECTORY_SEPARATOR . $node->getAttribute('name') . '.html';
			if (file_exists($name)) {
				$content = file_get_contents($name);
				$dom = new View($content, [], $this->config);
				$dom->parse();
				$element = $this->dom->createTextNode($dom->getHtml()); 
				$parent = $node->parentNode;
				$parent->insertBefore($element, $node);
			}
			$parent->removeChild($node);
		}

		// Yield content
		$nodes = $this->dom->getElementsByTagName('c.content');
		$node = $nodes->item(0);
		if ($node) {
			$name = $this->config['module_path'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 
				$this->data->name . '.html';
			if (file_exists($name)) {
				$content = file_get_contents($name);
				$dom = new ViewDom($content, $this->data->data, $this->config);
				$dom->parse();
				$element = $this->dom->createTextNode($dom->getHtml());
				$parent = $node->parentNode;
				$parent->insertBefore($element, $node);
				$parent->removeChild($node);

				$head = $this->dom->getElementsByTagName('head')->item(0);
				$body = $this->dom->getElementsByTagName('body')->item(0);

				// Apply styles
				foreach ($dom->getStyles() as $node) {
					$imported_node = $this->dom->importNode($node, true);
					$head->appendChild($imported_node);
				}

				// Apply scripts		
				foreach ($this->scripts as $node) {
					$imported_node = $this->dom->importNode($node, true);
					$body->appendChild($imported_node);
				}

				// Apply scripts		
				foreach ($dom->getScripts() as $node) {
					$imported_node = $this->dom->importNode($node, true);
					$body->appendChild($imported_node);
				}
			}
		}

		// // Apply variables
		// foreach ($this->data['data'] as $key => $value) {
		// 	if (is_array($value)) {
		// 		$this->parseToElement($key, $value);
		// 	} else {
		// 		$node = $this->dom->getElementById($key);
		// 		@$node->nodeValue = $value;
		// 	}
		// }

		$this->applyUrl();
	}

	private function parseToElement($key, $value)
	{
		$xpath = new \DOMXPath($this->dom);
		$results = $xpath->query("//*[@c." . $key . "]");

		if ($results->length > 0) {
			// Get HTML
			$node = $results->item(0);
			$parent = $node->parentNode;
			// Apply data
			foreach ($value as $key2 => $value2) {
				@$node->setAttribute('id', $key . $key2);
				$node->setAttribute('rel', $key2);

				if (isset($value2[0]) && is_array($value2[0])) {
					foreach ($value2[0] as $key3 => $value3) {
						$node->setAttribute($key3, $value3);
					}
				}
				
				if (is_array($value2)) {
					foreach ($value2 as $key3 => $value3) {
						$this->parseToNode($key2, $key3, $value3);
					}
				} else {
					$node->nodeValue = $value2;
				}

				if ($this->doCloning) {
					@$clone = $node->cloneNode(true);
					$parent->appendChild($clone);
				}
			}

			if ($this->doCloning) {
				$parent->removeChild($node);
			}
		} else {
			$node = $this->dom->getElementById($key);
			if ($node && is_array($value)) {
				foreach ($value as $key2 => $value2) {
					if (is_numeric($key2)) {
						$node->nodeValue = $value2;
					} else{
						@$node->setAttribute($key2, $value2);
					}
				}
			}
		}
	}

	private function parseToNode($id, $key, $value)
	{
		$xpath = new \DOMXPath($this->dom);
		$results = $xpath->query("//*[@class='" . $key . "']");

		if ($results->length > 0) {
			$node = $results->item(0);
			$node->setAttribute('rel', $id);
			@$node->setAttribute('id', $key . $id);

			if (is_array($value)) {
				foreach ($value as $key2 => $value2) {
					if ($key2 === 0) {
						@$node->nodeValue = $value2;
					} else {
						$node->setAttribute($key2, $value2);
					}
				}
			} else {
				$node->nodeValue = $value;
			}
		}
	}

	private function applyVisibility()
	{
		$xpath = new \DOMXPath($this->dom);

		// Visible
		$results = $xpath->query("//*[@data-vkey]");
		if ($results->length > 0) {
			foreach ($results as $node) {
				$key = $node->getAttribute('data-vkey');
				if (!isset($this->data[$key]) || (isset($this->data[$key]) && !(bool)$this->data[$key]))
					$node->setAttribute('style', 'display: none;');
			}
		}
		$results = $xpath->query("//*[@data-vattr]");
		if ($results->length > 0) {
			foreach ($results as $node) {
				if ($node->hasAttribute('data-vattr')) {
					$value = $node->getAttribute('data-vattr');
					if (!$node->hasAttribute($value) || ($node->hasAttribute($value) && !(bool)$node->getAttribute($value))) {
						$node->setAttribute('style', 'display: none;');
					}
				}
			}
		}

		// Hidden
		$results = $xpath->query("//*[@data-hkey]");
		if ($results->length > 0) {
			foreach ($results as $node) {
				$key = $node->getAttribute('data-hkey');
				if (isset($this->data[$key]))
					$node->setAttribute('style', 'display: none;');
			}
		}
		$results = $xpath->query("//*[@data-hattr]");
		if ($results->length > 0) {
			foreach ($results as $node) {
				if ($node->hasAttribute('data-hattr')) {
					$value = $node->getAttribute('data-hattr');
					if ($node->hasAttribute($value)) {
						$node->setAttribute('style', 'display: none;');
					}
				}
			}
		}
	}

	private function replaceNode()
	{
	}

	private function applyUrl()
	{
		// CSS
		$nodes = $this->dom->getElementsByTagName('link');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('href');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->config['layout_url'] . '/' . trim($url, ':');
				$node->setAttribute('href', $url);
			}
		}
		// JS
		$nodes = $this->dom->getElementsByTagName('script');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('src');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->config['layout_url'] . '/' . trim($url, ':');
				$node->setAttribute('src', $url);
			}
		}
		// Image
		$nodes = $this->dom->getElementsByTagName('img');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('src');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->config['layout_url'] . '/' . trim($url, ':');
				$node->setAttribute('src', $url);
			}
		}
		// Anchor
		$nodes = $this->dom->getElementsByTagName('a');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('href');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->config['base_url'] . '/' . trim($url, ':');
				$node->setAttribute('href', $url);
			}
		}
	}
}