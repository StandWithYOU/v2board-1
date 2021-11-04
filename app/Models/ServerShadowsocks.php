<?php

namespace App\Models;

use App\Utils\CacheKey;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Models\Traits\Serialize;

/**
 * App\Models\ServerShadowsocks
 *
 * @property int $id
 * @property array $group_id
 * @property int|null $parent_id
 * @property array|null $tags
 * @property string $name
 * @property string $rate
 * @property string $host
 * @property int $port
 * @property int $server_port
 * @property string $cipher
 * @property int $show
 * @property int|null $sort
 * @property int $created_at
 * @property int $updated_at
 * @method static Builder|ServerShadowsocks newModelQuery()
 * @method static Builder|ServerShadowsocks newQuery()
 * @method static Builder|ServerShadowsocks query()
 * @method static Builder|ServerShadowsocks whereCipher($value)
 * @method static Builder|ServerShadowsocks whereCreatedAt($value)
 * @method static Builder|ServerShadowsocks whereGroupId($value)
 * @method static Builder|ServerShadowsocks whereHost($value)
 * @method static Builder|ServerShadowsocks whereId($value)
 * @method static Builder|ServerShadowsocks whereName($value)
 * @method static Builder|ServerShadowsocks whereParentId($value)
 * @method static Builder|ServerShadowsocks wherePort($value)
 * @method static Builder|ServerShadowsocks whereRate($value)
 * @method static Builder|ServerShadowsocks whereServerPort($value)
 * @method static Builder|ServerShadowsocks whereShow($value)
 * @method static Builder|ServerShadowsocks whereSort($value)
 * @method static Builder|ServerShadowsocks whereTags($value)
 * @method static Builder|ServerShadowsocks whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ServerShadowsocks extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_GROUP_ID = "group_id";
    const FIELD_PARENT_ID = "parent_id";
    const FIELD_TAGS = "tags";
    const FIELD_NAME = "name";
    const FIELD_HOST = "host";
    const FIELD_PORT = "port";
    const FIELD_SERVER_PORT = "server_port";
    const FIELD_CIPHER = "cipher"; //密文
    const FIELD_RATE = "rate";
    const FIELD_SHOW = "show";
    const FIELD_SORT = "sort";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";
    const METHOD = "shadowsocks";

    const SHOW_ON = 1;
    const SHOW_OFF = 0;

    const TYPE = "shadowsocks";
    protected $table = 'server_shadowsocks';
    protected $dateFormat = 'U';


    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp',
        self::FIELD_GROUP_ID => 'array',
        self::FIELD_TAGS => 'array'
    ];


    /**
     * check show
     *
     * @return bool
     */
    public function isShow(): bool
    {
        return (bool)$this->getAttribute(self::FIELD_SHOW);
    }


    /**
     * find available users
     *
     * @return array|Collection
     */
    public function findAvailableUsers()
    {
        $server = new Server();
        $server->setAttribute(Server::FIELD_GROUP_ID, $this->getAttribute(Server::FIELD_GROUP_ID));
        return $server->findAvailableUsers();
    }

    /**
     * nodes
     *
     * @return Collection
     */
    public static function nodes(): Collection
    {
        $servers = self::orderBy('sort', "ASC")->get();
        foreach ($servers as $server) {
            /**
             * @var ServerShadowsocks $server
             */
            $parentId = $server->getAttribute(ServerShadowsocks::FIELD_PARENT_ID);
            $nodeId = $parentId > 0 ? $parentId : $server->getKey();
            $cacheKeyOnline = CacheKey::get(CacheKey::SERVER_SHADOWSOCKS_ONLINE_USER, $nodeId);
            $lastCheckAt = Cache::get(CacheKey::get(CacheKey::SERVER_SHADOWSOCKS_LAST_CHECK_AT, $server['id']));
            $lastPushAt = Cache::get(CacheKey::get(CacheKey::SERVER_SHADOWSOCKS_LAST_PUSH_AT, $server['id']));
            $online = Cache::get($cacheKeyOnline) ?? 0;

            if ((time() - 300) >= $lastCheckAt) {
                $availableStatus = 0;
            } else if ((time() - 300) >= $lastPushAt) {
                $availableStatus = 1;
            } else {
                $availableStatus = 2;
            }

            $server->setAttribute('type', self::TYPE);
            $server->setAttribute('online', $online);
            $server->setAttribute('available_status',  $availableStatus);
            $server->setAttribute('last_check_at', $lastCheckAt);
            $server->setAttribute('last_push_at', $lastCheckAt);
        }
        return $servers;
    }


    /**
     * configs
     *
     * @param User $user
     * @param bool $show
     * @return Collection
     */
    public static function configs(User $user, bool $show = true): Collection
    {
        $servers = self::orderBy(self::FIELD_SORT, "ASC")->where(self::FIELD_SHOW, (int)$show)->get();

        foreach ($servers as $key => $server) {
            /**
             * @var ServerShadowsocks $server
             */
            $groupIds = $server->getAttribute(Server::FIELD_GROUP_ID);
            if (!in_array($user->getAttribute(User::FIELD_GROUP_ID), $groupIds)) {
                unset($servers[$key]);
                continue;
            }

            $server->setAttribute("type", self::TYPE);
            if ($server->getAttribute(self::FIELD_PARENT_ID) > 0) {
                $server->setAttribute('last_check_at', Cache::get(CacheKey::get(CacheKey::SERVER_SHADOWSOCKS_LAST_CHECK_AT,
                    $server->getAttribute(self::FIELD_PARENT_ID))));
            } else {
                $server->setAttribute('last_check_at', Cache::get(CacheKey::get(CacheKey::SERVER_SHADOWSOCKS_LAST_CHECK_AT,
                    $server->getKey())));
            }
        }
        return $servers;
    }

}
