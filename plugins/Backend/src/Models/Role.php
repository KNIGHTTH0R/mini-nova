<?php

namespace Backend\Models;

use Mini\Database\ORM\Model as BaseModel;


class Role extends BaseModel
{
    protected $table = 'roles';

    protected $primaryKey = 'id';

    protected $fillable = array('name', 'slug', 'description');


    public function users()
    {
        return $this->hasMany('Backend\Models\User', 'role_id', 'id');
    }

}
