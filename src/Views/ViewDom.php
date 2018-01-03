<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 * @link http://www.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Chupoo\Views;

class ViewDom
{
	private $dom;
	private $content;
	private $scripts = array();
	private $styles = array();
	private $config = array();
	private $data = array();

	public $doCloning = true;

	public function __construct($content, array $data = array(), array $config = array())
	{
		$this->content = $content;
		$this->data = $data;
		$this->config = $config;
	}

	public function getHtml()
	{
		if (empty($this->content)) return;

		$body = $this->dom->getElementsByTagName('body')->item(0);
		$children = $body->childNodes;
		$content = '';
		$i = 0;
		if ($children) {
			foreach ($children as $child) {
				$content .= $child->ownerDocument->saveHTML($child);
			}
		}
		return html_entity_decode($content);
	}

	public function getData()
	{
		return $this->data;
	}

	public function getScripts()
	{
		return $this->scripts;
	}

	public function getStyles()
	{
		return $this->styles;
	}

	public function getContent($name)
	{
		$node = $this->dom->getElementById($name);
		if ($node) {
			$children = $node->childNodes;
			$content = '';
			foreach ($children as $child) {
				$content .= $child->ownerDocument->saveHTML($child);
			}
			return $content;
		}
		return $this->content;
	}

	public function parse()
	{
		if (empty($this->content)) return;

		$content = mb_convert_encoding($this->content, 'HTML-ENTITIES', 'UTF-8');

		$this->dom = new \DomDocument();
		@$this->dom->loadHTML($content);

		$this->applyVisibility();

		foreach ($this->data as $key => $value) {
			if (is_array($value)) {
				$this->parseToElement($key, $value);
			} else {
				$xpath = new \DOMXPath($this->dom);
				$results = $xpath->query("//*[@data-php-id='" . $key . "']");

				if ($results->length > 0) {
					// Get HTML
					$node = $results->item(0);
					$node->nodeValue = $value;
				}
			}
		}

		$this->applyUrl();
		$this->separateStyle();
		$this->separateScript();
	}

	private function parseToElement($key, $value)
	{
		$xpath = new \DOMXPath($this->dom);
		$results = $xpath->query("//*[@data-php-class='" . $key . "']");

		if ($results->length > 0) {
			// Get HTML
			$node = $results->item(0);
			$parent = $node->parentNode;
			// Apply data
			foreach ($value as $key2 => $value2) {

				$node->setAttribute('id', $key . $key2);
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

				unset($this->data[$key]);
				@$clone = $node->cloneNode(true);
				$parent->appendChild($clone);
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
						$node->setAttribute($key2, $value2);
					}
				}
			}
		}
	}

	private function parseToNode($id, $key, $value)
	{
		$xpath = new \DOMXPath($this->dom);
		$results = $xpath->query("//*[@data-php-class='" . $key . "']");

		if ($results->length > 0) {
			$node = $results->item(0);
			$node->setAttribute('rel', $id);
			$node->setAttribute('id', $key . $id);

			if (is_array($value)) {
				foreach ($value as $key2 => $value2) {
					if ($key2 === 0) {
						$node->nodeValue = $value2;
					} else {
						$node->setAttribute($key2, $value2);
					}
				}
			} else {
				@$node->nodeValue = $value;
			}
		}
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

	public function separateScript()
	{
		if (empty($this->content)) return;

		$items = array();
		$nodes = $this->dom->getElementsByTagName('script');
		foreach ($nodes as $node) {
			$this->scripts[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}
	}

	public function separateStyle()
	{
		if (empty($this->content)) return;

		$items = array();
		$nodes = $this->dom->getElementsByTagName('link');
		foreach ($nodes as $node) {
			$this->styles[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}

		$items = array();
		$nodes = $this->dom->getElementsByTagName('style');
		foreach ($nodes as $node) {
			$this->styles[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
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
}