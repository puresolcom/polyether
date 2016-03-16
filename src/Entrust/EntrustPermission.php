<?php namespace Polyether\Entrust;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Polyether\Entrust
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Polyether\Entrust\Contracts\EntrustPermissionInterface;
use Polyether\Entrust\Traits\EntrustPermissionTrait;

class EntrustPermission extends Model implements EntrustPermissionInterface
{
    use EntrustPermissionTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Creates a new instance of the model.
     *
     * @param array $attributes
     */
    public function __construct( array $attributes = [ ] )
    {
        parent::__construct( $attributes );
        $this->table = Config::get( 'entrust.permissions_table' );
    }

}
