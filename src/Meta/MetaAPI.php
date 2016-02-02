<?php

namespace Polyether\Meta;

use Polyether\Meta\Repositories\UserMetaRepository;
use Polyether\Meta\Repositories\PostMetaRepository;
use Polyether\Support\EtherError;
use Plugin;

class MetaAPI {

    protected $userMetaRepository;
    protected $postMetaRepository;

    public function __construct(UserMetaRepository $userMetaRepository, PostMetaRepository $postMetaRepository) {
        $this->userMetaRepository = $userMetaRepository;
        $this->postMetaRepository = $postMetaRepository;
    }

}
