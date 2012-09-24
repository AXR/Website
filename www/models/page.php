<?php

require_once(SHARED . '/lib/php-markdown/markdown.php');

class Page extends ActiveRecord\Model
{
	static $table_name = 'www_pages';

	static $before_save = array('parse_fields', 'encode_fields',
		'before_save');
	static $after_update = array('decode_fields');
	static $after_construct = array('decode_fields');

	static $belongs_to = array(
		array('user', 'primary_key' => 'user_id', 'readonly' => true)
	);

	static $validates_presence_of = array(
		array('title')
	);

	static $ctypes;

	/**
	 * __isset
	 */
	public function __isset ($attribute_name)
	{
		$virtual_attributes = array('fields__merged', 'permalink',
			'ctime_str_Ymd');

		if (in_array($attribute_name, $virtual_attributes))
		{
			return true;
		}

		return parent::__isset($attribute_name);
	}

	/**
	 * Getter for attribute fields__merged
	 *
	 * @return mixed
	 */
	public function get_fields__merged ()
	{
		return (object) array_merge((array) $this->fields,
			(array) $this->fields_parsed);
	}

	/**
	 * Getter for attribute permalink
	 *
	 * @return string
	 */
	public function get_permalink ()
	{
		return !empty($this->url) ? '/' . $this->url : '/page/' . $this->id;
	}

	/**
	 * Getter for attribute ctime_str_Ymd
	 *
	 * @return string
	 */
	public function get_ctime_str_Ymd ()
	{
		return date('Y-m-d', $this->ctime);
	}

	/**
	 * Override attributes()
	 *
	 * @return mixed
	 */
	public function attributes ()
	{
		$attributes = parent::attributes();
		$attributes['permalink'] = $this->permalink;
		$attributes['ctime_str_Ymd'] = $this->ctime_str_Ymd;
		$attributes['user'] = $this->user->attributes();

		return $attributes;
	}

	/**
	 * Validate data
	 */
	public function validate ()
	{
		if (!in_array($this->ctype, array_keys((array) Page::$ctypes)))
		{
			$this->errors->add('ctype', 'Invalid content type');
		}

		$page_by_url = Page::find(array(
			'conditions' => array('url = ?', $this->url)
		));

		if (is_object($page_by_url) && $page_by_url->id != $this->id)
		{
			$this->errors->add('url', 'The URL needs to be unique');
		}

		foreach (Page::$ctypes->{$this->ctype}->fields as $field)
		{
			if (isset($field->required) && $field->required === true &&
				empty($this->fields->{$field->key}))
			{
				$this->errors->add('field_' . $field->key, ' can\'t be empty');
			}
		}
	}

	/**
	 * Run before save
	 */
	public function before_save ()
	{
		if (empty($this->url))
		{
			$this->url = null;
		}

		if ($this->is_new_record())
		{
			$this->user_id = User::current()->id;
			$this->ctime = time();
		}

		$this->mtime = time();
	}

	/**
	 * Encode fields
	 */
	public function encode_fields ()
	{
		$this->fields = json_encode($this->fields);
		$this->fields_parsed = json_encode($this->fields_parsed);
	}

	/**
	 * Decode fields
	 */
	public function decode_fields ()
	{
		$this->fields = json_decode($this->fields);
		$this->fields_parsed = json_decode($this->fields_parsed);

		if (!is_object($this->fields))
		{
			$this->fields = new StdClass();
		}

		if (!is_object($this->fields_parsed))
		{
			$this->fields_parsed = new StdClass();
		}
	}

	/**
	 * Parse fields before saving
	 */
	public function parse_fields ()
	{
		foreach (Page::$ctypes->{$this->ctype}->fields as $field)
		{
			if (!isset($field->filters) || !is_array($field->filters))
			{
				continue;
			}

			$this->fields_parsed->{$field->key} = $this->fields->{$field->key};

			foreach ($field->filters as $filter)
			{
				if (method_exists($this, 'filter_' . $filter))
				{
					$this->fields_parsed->{$field->key} = call_user_func(
						array($this, 'filter_' . $filter),
						$this->fields_parsed->{$field->key});
				}
			}

			if ($this->fields_parsed->{$field->key} ===
				$this->fields->{$field->key})
			{
				unset($this->fields_parsed->{$field->key});
			}
		}

	}

	/**
	 * @param mixed[] $data
	 */
	public function set_attributes (array $data)
	{
		parent::set_attributes(array(
			'title' => array_key_or($data, 'title', null),
			'url' => array_key_or($data, 'url', null),
			'published' => array_key_or($data, 'published', null)
		));

		if (isset(self::$ctypes->{$this->ctype}))
		{
			foreach (self::$ctypes->{$this->ctype}->fields as $field)
			{
				$this->fields->{$field->key} =
					array_key_or($data, 'field_' . $field->key, '');
			}
		}
	}

	/**
	 * Get content type specific fields for displaying in a view
	 *
	 * @return StdClass[]
	 */
	public function ctype_fields_for_view ()
	{
		if (!isset(self::$ctypes->{$this->ctype}))
		{
			return array();
		}

		$ctype = self::$ctypes->{$this->ctype};
		$fields = array();

		foreach ($ctype->fields as $_field)
		{
			$field = clone $_field;
			$fields[] = $field;

			$postKey = 'field_' . $field->key;

			$field->{'typeIs_' . $field->type} = true;

			if (isset($this->fields->{$field->key}))
			{
				$field->_value = $this->fields->{$field->key};
			}
		}

		return $fields;
	}

	/**
	 * Get breadcrumb for the page
	 *
	 * Returned array item:
	 * - string name: Name for the page
	 * - string link: Link for the page (optional)
	 *
	 * @return mixed
	 */
	public function breadcrumb ()
	{
		$breadcrumb = array();

		$breadcrumb[] = array(
			'name' => 'Home',
			'link' => '/'
		);

		if ($this->ctype === 'bpost')
		{
			$breadcrumb[] = array(
				'name' => 'Blog',
				'link' => '/blog'
			);
		}

		$breadcrumb[] = array(
			'name' => $this->title
		);

		return $breadcrumb;
	}

	/**
	 * Check, if the user can view this page
	 *
	 * @return bool
	 */
	public function can_view ()
	{
		if ((int) $this->published === 1)
		{
			return true;
		}

		return User::current()
			->can('/page/view_unpub/' . $this->ctype);
	}

	/**
	 * Check if the user can edit this page
	 *
	 * @return bool
	 */
	public function can_edit ()
	{
		return User::current()
			->can('/page/edit/' . $this->ctype);
	}

	/**
	 * Check if the user can remove this page
	 *
	 * @return bool
	 */
	public function can_rm ()
	{
		return User::current()
			->can('/page/rm/' . $this->ctype);
	}

	/**
	 * Markdown filter
	 *
	 * @param string $data
	 * @return string
	 */
	private function filter_markdown ($data)
	{
		return Markdown($data);
	}
}

Page::$ctypes = (object) array(
	'bpost' => (object) array(
		'name' => 'Blog post',
		'description' => 'This is used for blog posts',
		'view' => ROOT . '/views/page_bpost.html',
		'comments' => true,
		'fields' => array(
			(object) array(
				'key' => 'summary',
				'name' => 'Summary',
				'description' => 'This will be displayed on the blog posts listing page',
				'type' => 'textarea',
				'required' => true,
				'filters' => array('markdown')
			),
			(object) array(
				'key' => 'content',
				'name' => 'Content',
				'type' => 'textarea',
				'required' => true,
				'filters' => array('markdown')
			)
		)
	),
	'page' => (object) array(
		'name' => 'Basic page',
		'description' => 'Basic content type for pages like "About"',
		'view' => ROOT . '/views/page_page.html',
		'fields' => array(
			(object) array(
				'key' => 'content',
				'name' => 'Content',
				'type' => 'textarea',
				'required' => true,
				'filters' => array('markdown')
			)
		)
	)
);
