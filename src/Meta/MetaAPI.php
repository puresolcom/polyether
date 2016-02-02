<?php

namespace Polyether\Meta;

use Polyether\App\Repositories\UserMetaRepository;
use Polyether\App\Repositories\PostMetaRepository;
use Polyether\App\Libraries\EtherError;
use Plugin;

class MetaAPI {

    protected $userMetaRepository;
    protected $postMetaRepository;

    public function __construct(UserMetaRepository $userMetaRepository, PostMetaRepository $postMetaRepository) {
        $this->userMetaRepository = $userMetaRepository;
        $this->postMetaRepository = $postMetaRepository;
    }

}
