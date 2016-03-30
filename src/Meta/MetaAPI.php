<?php

namespace Polyether\Meta;

use Cache;
use Plugin;
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

    public function update($metaType, $objectId, $metaKey, $metaValue, $prevValue = '')
    {
        if ( ! $metaType || ! $metaKey || ! is_numeric($objectId)) {
            return false;
        }

        $objectId = abs((int)$objectId);

        if ( ! $objectId) {
            return false;
        }

        if ( ! $repositoryClassInstance = $this->metaRepoExists($metaType)) {
            return false;
        }

        $column = sanitizeKey($metaType . '_id');

        $metaKey = e($metaKey);
        $passedValue = $metaValue;
        $metaValue = se($metaValue);

        $check = Plugin::apply_filters("update_{$metaType}_metadata", null, $objectId, $metaKey, $metaValue,
            $prevValue);
        if (null !== $check) {
            return (bool)$check;
        }

        // Compare existing value to new value if no prev value given and the key exists only once.
        if (empty($prevValue)) {
            $oldValue = $this->get($metaType, $objectId, $metaKey);
            if (count($oldValue) == 1) {
                if ($oldValue[ 0 ] === $metaValue) {
                    return false;
                }
            }
        }

        $metaIds = $this->{$repositoryClassInstance}->findWhere(['meta_key' => $metaKey, $column => $objectId], ['id']);

        if ( ! count($metaIds)) {
            return $this->add($metaType, $objectId, $metaKey, $passedValue);
        }

        $_metaValue = $metaValue;
        $metaValue = jsonizeMaybe($metaValue);

        $where = [$column => $objectId, 'meta_key' => $metaKey];

        if ( ! empty($prevValue)) {
            $prevValue = unjsonizeMaybe($prevValue);
            $where[ 'meta_value' ] = $prevValue;
        }

        foreach ($metaIds as $metaId) {
            Plugin::do_action("update_{$metaType}_meta", $metaId, $objectId, $metaKey, $_metaValue);
        }

        $result = $this->{$repositoryClassInstance}->updateWhere(['meta_value' => $metaValue], $where);

        if ( ! $result) {
            return false;
        }

        Cache::tags(["{$metaType}_meta"])->forget($objectId);

        foreach ($metaIds as $metaId) {
            Plugin::do_action("updated_{$metaType}_meta", $metaId, $objectId, $metaKey, $_metaValue);
        }

        return true;
    }

    public function metaRepoExists($metaType)
    {
        $repositoryClassInstance = strtolower($metaType) . 'MetaRepository';

        if ( ! property_exists($this, $repositoryClassInstance)) {
            return false;
        }

        return $repositoryClassInstance;
    }

    public function get($metaType, $objectId, $metaKey = '', $single = false)
    {
        if ( ! $metaType || ! is_numeric($objectId)) {
            return false;
        }

        $objectId = abs((int)$objectId);

        if ( ! $objectId) {
            return false;
        }

        $check = Plugin::apply_filters("get_{$metaType}_metadata", null, $objectId, $metaKey, $single);

        if (null !== $check) {
            if ($single && is_array($check)) {
                return $check[ 0 ];
            } else {
                return $check;
            }
        }

        $metaCache = Cache::tags(["{$metaType}_meta"])->get($objectId, false);

        if ( ! $metaCache) {
            $metaCache = $this->updateMetaCache($metaType, [$objectId]);
            $metaCache = $metaCache[ $objectId ];
        }

        if ( ! $metaKey) {
            return $metaCache;
        }


        if (isset($metaCache[ $metaKey ])) {
            if ($single) {
                return unjsonizeMaybe($metaCache[ $metaKey ][ 0 ]);
            } else {
                return array_map('unjsonizeMaybe', $metaCache[ $metaKey ]);
            }
        }

        if ($single) {
            return '';
        } else {
            return [];
        }

    }

    public function add($metaType, $objectId, $metaKey, $metaValue, $unique = false)
    {
        if ( ! $metaType || ! $metaKey || ! is_numeric($objectId)) {
            return false;
        }

        $objectId = abs((int)$objectId);

        if ( ! $objectId) {
            return false;
        }

        if ( ! $repositoryClassInstance = $this->metaRepoExists($metaType)) {
            return false;
        }

        $column = sanitizeKey($metaType . '_id');

        $metaKey = e($metaKey);
        $metaValue = se($metaValue);

        $check = Plugin::apply_filters("add_{$metaType}_metadata", null, $objectId, $metaKey, $metaValue, $unique);
        if (null !== $check) {
            return $check;
        }

        if ($unique && $this->{$repositoryClassInstance}->countWhere([
                'meta_key' => $metaKey,
                $column    => $objectId,
            ])
        ) {
            return false;
        }

        $_metaValue = $metaValue;
        $metaValue = jsonizeMaybe($metaValue);

        Plugin::do_action("add_{$metaType}_meta", $objectId, $metaKey, $_metaValue);

        $result = $this->{$repositoryClassInstance}->create([
            $column      => $objectId,
            'meta_key'   => $metaKey,
            'meta_value' => $metaValue,
        ]);

        if ( ! $result) {
            return false;
        }

        $mid = (int)$result->id;

        Cache::tags(["{$metaType}_meta"])->forget($objectId);

        Plugin::do_action("added_{$metaType}_meta", $mid, $objectId, $metaKey, $_metaValue);

        return $mid;
    }

    public function updateMetaCache($metaType, $objectIds)
    {
        if ( ! $metaType || ! $objectIds) {
            return false;
        }

        if ( ! $repositoryClassInstance = $this->metaRepoExists($metaType)) {
            return false;
        }

        $column = sanitizeKey($metaType . '_id');

        if ( ! is_array($objectIds)) {
            $objectIds = preg_replace('/[^0-9,]/', '', $objectIds);
            $objectIds = explode(',', $objectIds);
        }

        $objectIds = array_map('intval', $objectIds);
        $ids = [];
        $cache = [];

        foreach ($objectIds as $id) {
            $cachedObject = Cache::tags(["{$metaType}_meta"])->get($id, false);

            if (false === $cachedObject) {
                $ids[] = $id;
            } else {
                $cache[ $id ] = $cachedObject;
            }
        }

        if (empty($ids)) {
            return $cache;
        }
        $metaList = $this->{$repositoryClassInstance}->findWhereIn($column, $ids, [$column, 'meta_key', 'meta_value']);

        if ( ! empty($metaList)) {
            foreach ($metaList as $meta) {
                $mpid = intval($meta[ $column ]);
                $mkey = $meta[ 'meta_key' ];
                $mval = $meta[ 'meta_value' ];

                // Force subkeys to be array type:
                if ( ! isset($cache[ $mpid ]) || ! is_array($cache[ $mpid ])) {
                    $cache[ $mpid ] = [];
                }
                if ( ! isset($cache[ $mpid ][ $mkey ]) || ! is_array($cache[ $mpid ][ $mkey ])) {
                    $cache[ $mpid ][ $mkey ] = [];
                }

                // Add a value to the current pid/key:
                $cache[ $mpid ][ $mkey ][] = $mval;
            }
        }

        foreach ($ids as $id) {
            if ( ! isset($cache[ $id ])) {
                $cache[ $id ] = [];
            }
            Cache::tags(["{$metaType}_meta"])->forever($id, $cache[ $id ]);
        }

        return $cache;
    }

    public function delete($metaType, $objectId, $metaKey, $metaValue = '', $deleteAll = false)
    {
        if ( ! $metaType || ! $metaKey || ! is_numeric($objectId) && ! $deleteAll) {
            return false;
        }

        $objectId = abs((int)$objectId);

        if ( ! $objectId && ! $deleteAll) {
            return false;
        }

        if ( ! $repositoryClassInstance = $this->metaRepoExists($metaType)) {
            return false;
        }

        $column = sanitizeKey($metaType . '_id');

        $metaKey = e($metaKey);
        $metaValue = se($metaValue);

        $check = Plugin::apply_filters("delete_{$metaType}_metadata", null, $objectId, $metaKey, $metaValue,
            $deleteAll);
        if (null !== $check) {
            return (bool)$check;
        }

        $_metaValue = $metaValue;
        $metaValue = jsonizeMaybe($metaValue);

        $findQueryClauses = ['meta_key' => $metaKey];

        if ( ! $deleteAll) {
            $findQueryClauses[ $column ] = $objectId;
        }

        if ('' !== $metaValue && null !== $metaValue && false !== $metaValue) {
            $findQueryClauses[ 'meta_value' ] = $metaValue;
        }

        $metaIds = degrade($this->{$repositoryClassInstance}->findWhere($findQueryClauses, ['id']));

        if ( ! count($metaIds)) {
            return false;
        }

        if ($deleteAll) {
            $objectIds = degrade(array_pluck($this->{$repositoryClassInstance}->findWhere(['meta_key' => $metaKey],
                [$column]), $column));
        }

        Plugin::do_action("delete_{$metaType}_meta", $metaIds, $objectId, $metaKey, $_metaValue);

        $delete = $this->{$repositoryClassInstance}->delete($metaIds);

        if ( ! $delete) {
            return false;
        }

        if ($deleteAll) {
            foreach ($objectIds as $oId) {
                Cache::tags(["{$metaType}_meta"])->forget($oId);
            }
        } else {
            Cache::tags(["{$metaType}_meta"])->forget($objectId);
        }

        Plugin::do_action("deleted_{$metaType}_meta", $metaIds, $objectId, $metaKey, $_metaValue);

        return true;
    }

}
