<?php

namespace App\Models;

use Mini\Database\ORM\Model as BaseModel;


class Option extends BaseModel
{
	protected $table = 'options';

	protected $primaryKey = 'id';

	protected $fillable = array('group', 'item', 'value');

	public $timestamps = false;


	public function getValueAttribute($value)
	{
		return $this->maybeDecode($value);
	}

	public function setValueAttribute($value)
	{
		$this->attributes['value'] = $this->maybeEncode($value);
	}

	public static function set($key, $value)
	{
		list($group, $item) = array_pad(explode('.', $key, 2), 2, null);

		// Prepare the record variables.
		$attributes = array(
			'group' => $group,
			'item'  => $item
		);

		$values = array(
			'value' => $value
		);

		return static::updateOrCreate($attributes, $values);
	}

	/**
	 * Decode value only if it was encoded to JSON.
	 *
	 * @param  string  $original
	 * @param  bool  $assoc
	 * @return mixed
	 */
	protected function maybeDecode($original, $assoc = true)
	{
		if (is_numeric($original)) return $original;

		$data = json_decode($original, $assoc);

		return (is_object($data) || is_array($data)) ? $data : $original;
	}

	/**
	 * Encode data to JSON, if needed.
	 *
	 * @param  mixed  $data
	 * @return mixed
	 */
	protected function maybeEncode($data)
	{
		if (is_array($data) || is_object($data)) {
			return json_encode($data);
		}

		return $data;
	}
}
