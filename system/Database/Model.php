<?php
/**
 * Model - A simple Database Model.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Mini\Database;

use Mini\Database\Connection;
use Mini\Database\ConnectionResolverInterface as Resolver;
use Mini\Database\Query\Builder as QueryBuilder;
use Mini\Database\Builder;
use Mini\Support\Str;


class Model
{
    /**
     * The Database Connection name.
     *
     * @var string
     */
    protected $connection = null;

    /**
     * The Database Connection instance.
     *
     * @var string
     */
    protected $db = null;

    /**
     * The table associated with the Model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the Model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The number of Records to return for pagination.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * The connection resolver instance.
     *
     * @var \Mini\Database\ConnectionResolverInterface
     */
    protected static $resolver;


    /**
     * Create a new Model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct($connection = null)
    {
        if (! is_null($connection)) {
            $this->connection = $connection;
        }

        // Setup the Database Connection instance.
        $this->db = $this->getConnection();
    }

    /**
     * Get all of the Records from the database.
     *
     * @param  array  $columns
     * @return array
     */
    public function all($columns = array('*'))
    {
        return $this->newQuery()->get($columns);
    }

    /**
     * Find a Record by its primary key.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return Model
     */
    public function find($id, $columns = array('*'))
    {
        return $this->newQuery()->find($id, $columns);
    }

    /**
     * Find Records by their primary key.
     *
     * @param  array  $ids
     * @param  array  $columns
     * @return Model
     */
    public function findMany($ids, $columns = array('*'))
    {
        return $this->newQuery()->findMany($ids, $columns);
    }

    /**
     * Insert a new Record and get the value of the primary key.
     *
     * @param  array   $values
     * @return int
     */
    public function insert(array $values)
    {
        return $this->newQuery()->insertGetId($values);
    }

    /**
     * Update the Model in the database.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @return mixed
     */
    public function update($id, array $attributes = array())
    {
        return $this->newQuery()
            ->where($this->getKeyName(), $id)
            ->update($attributes);
    }

    /**
     * Delete the Record from the database.
     *
     * @return bool|null
     */
    public function delete($id)
    {
        $this->newQuery()
            ->where($this->getKeyName(), $id)
            ->delete();

        return true;
    }

    /**
     * Get the Table for the Model.
     *
     * @return string
     */
    public function getTable()
    {
        if (isset($this->table)) return $this->table;

        return str_replace('\\', '', Str::snake(class_basename($this)));
    }

    /**
     * Get the Primary Key for the Model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Get the number of models to return per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the number of models to return per page.
     *
     * @param  int   $perPage
     * @return void
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
    }

    /**
     * Get the database Connection instance.
     *
     * @return \Mini\Database\Connection
     */
    public function getConnection()
    {
        return $this->resolveConnection($this->connection);
    }

    /**
     * Get the current Connection name for the Model.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Set the Connection associated with the Model.
     *
     * @param  string  $name
     * @return \Mini\Database\Model
     */
    public function setConnection($name)
    {
        $this->connection = $name;

        // Setup the Database Connection instance.
        $this->db = $this->getConnection();

        return $this;
    }

    /**
     * Resolve a connection instance.
     *
     * @param  string  $connection
     * @return \Mini\Database\Connection
     */
    public function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Mini\Database\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the connection resolver instance.
     *
     * @param  \Mini\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the connection resolver for models.
     *
     * @return void
     */
    public static function unsetConnectionResolver()
    {
        static::$resolver = null;
    }

    /**
     * Get a new Query for the Model's table.
     *
     * @return \Mini\Database\Query
     */
    public function newQuery()
    {
        $query = $this->newBaseQueryBuilder();

        $builder = $this->newBuilder($query);

        return $builder->setModel($this);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Mini\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        $grammar = $connection->getQueryGrammar();

        $processor = $connection->getPostProcessor();

        return new QueryBuilder($connection, $grammar, $processor);
    }

    /**
     * Create a new ORM query builder for the Model.
     *
     * @param  \Mini\Database\Query\Builder $query
     * @return \Mini\Database\Query|static
     */
    public function newBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $query = $this->newQuery();

        return call_user_func_array(array($query, $method), $parameters);
    }
}