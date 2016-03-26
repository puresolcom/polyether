<?php

namespace Polyether\Meta;

use Polyether\Meta\Repositories\PostMetaRepository;
use Polyether\Meta\Repositories\UserMetaRepository;

class MetaAPI
{

    protected $userMetaRepository;
    protected $postMetaRepository;

    public function __construct(UserMetaRepository $userMetaRepository, PostMetaRepository $postMetaRepository)
    {
        $this->userMetaRepository = $userMetaRepository;
        $this->postMetaRepository = $postMetaRepository;
    }

    public function get($metaType, $objectId, $metaKey = '', $single) { }

}
