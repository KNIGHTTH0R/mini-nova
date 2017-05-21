<?php
/**
 * Authorize - A Controller for managing the User Authentication.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Backend\Controllers;

use Mini\Foundation\Auth\AuthenticatesUsersTrait;

use Backend\Controllers\BaseController;


class Authorize extends BaseController
{
	use AuthenticatesUsersTrait;

	//
	protected $layout = 'Authorize';

	protected $redirectTo = 'admin/dashboard';
}
