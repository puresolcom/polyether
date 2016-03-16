<?php
namespace Polyether\User;


use Backend;
use Polyether\User\Repositories\UserRepository;

class UserGate
{
    protected $userRepository;

    public function __construct( UserRepository $userRepository )
    {
        $this->userRepository = $userRepository;
    }

    public function create( $userArr )
    {
        return $this->userRepository->create( $userArr );
    }

    public function onBoot()
    {
        $this->_registerMenuPages();
    }

    private function _registerMenuPages()
    {
        \Plugin::add_action( 'init_backend_menu', function() {
            $slug = 'users';
            $title = 'Users';
            $permissions = [ 'create_users', 'view_users', 'edit_users', 'delete_users ' ];
            $link = route( 'user_manage' );
            $icon = 'fa fa-user';

            $parent_slug = Backend::registerMenuPage( $slug, $title, $permissions, $link, $icon, 20 );
        } );
    }
}