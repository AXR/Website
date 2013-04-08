<?php

namespace WWW;

require_once(SHARED . '/lib/axr/controller.php');

class Controller extends \AXR\Controller
{
	public function __construct ()
	{
		parent::__construct();

		$that = $this;

		$this->view->{'g/html_head'} = function () use ($that)
		{
			return $that->rsrc->get_styles_html();
		};

		$this->view->{'g/html_bottom'} = function () use ($that)
		{
			return $that->rsrc->get_scripts_html();
		};

		// Load some default resources
		$this->rsrc->load_bundle('css/bundle_shared.css');
		$this->rsrc->load_bundle('css/bundle_rainbow.css');
		$this->rsrc->load_bundle('css/bundle_www.css');
		$this->rsrc->load_script('https://cdnjs.cloudflare.com/ajax/libs/zepto/1.0rc1/zepto.min.js');
		$this->rsrc->load_bundle('js/bundle_shared.js');
		$this->rsrc->load_bundle('js/bundle_www.js');
	}
}
