<?php
namespace Polyether\User;


use Polyether\User\Repositories\UserRepository;

class UserGate
{
    protected $userRepository;

    public function __construct ( UserRepository $userRepository )
    {
        $this->userRepository = $userRepository;
    }

    public function create ( $userArr )
    {
        return $this->userRepository->create( $userArr );
    }
}