<?php
/**
 * Profile - A Controller for managing the Users Profile.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 */

namespace Backend\Controllers\Admin;

use Mini\Support\Facades\Auth;
use Mini\Support\Facades\Hash;
use Mini\Support\Facades\Input;
use Mini\Support\Facades\Redirect;
use Mini\Support\Facades\Validator;

use Backend\Controllers\BaseController;
use Backend\Models\User;


class Profile extends BaseController
{

	protected function validator(array $data, User $user)
	{
		// Prepare the Validation Rules, Messages and Attributes.
		$rules = array(
			'current_password'	  => 'required|valid_password',
			'password'			  => 'required|strong_password',
			'password_confirmation' => 'required|same:password',
		);

		$messages = array(
			'valid_password'  => __d('backend', 'The :attribute field is invalid.'),
			'strong_password' => __d('backend', 'The :attribute field is not strong enough.'),
		);

		$attributes = array(
			'current_password'	  => __d('backend', 'Current Password'),
			'password'			  => __d('backend', 'New Password'),
			'password_confirmation' => __d('backend', 'Password confirmation'),
		);

		// Add the custom Validation Rule commands.
		Validator::extend('valid_password', function($attribute, $value, $parameters) use ($user)
		{
			return Hash::check($value, $user->password);
		});

		Validator::extend('strong_password', function($attribute, $value, $parameters)
		{
			$pattern = "/(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/";

			return (preg_match($pattern, $value) === 1);
		});

		return Validator::make($data, $rules, $messages, $attributes);
	}

	public function index()
	{
		$user = Auth::user();

		return $this->getView()
			->shares('title',  __d('backend', 'User Profile'))
			->with('user', $user);
	}

	public function update()
	{
		$user = Auth::user();

		// Retrieve the Input data.
		$input = Input::only('current_password', 'password', 'password_confirmation');

		// Create a Validator instance.
		$validator = $this->validator($input, $user);

		// Validate the Input.
		if ($validator->passes()) {
			$password = $input['password'];

			// Update the password on the User Model instance.
			$user->password = Hash::make($password);

			// Save the User Model instance.
			$user->save();

			// Use a Redirect to avoid the reposting the data.
			$status = __d('backend', 'You have successfully updated your Password.');

			return Redirect::back()->with('success', $status);
		}

		// Collect the Validation errors.
		$status = $validator->errors()->all();

		return Redirect::back()->withErrors($validator->errors());
	}

}
