<?php

namespace App\Models;

use App\Models\Traits\Serialize;
use App\Utils\CacheKey;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;


/**
 * App\Models\ServerTrojan
 *
 * @property int $id 节点ID
 * @property array $group_id 节点组
 * @property int|null $parent_id 父节点
 * @property array|null $tags 节点标签
 * @property string $name 节点名称
 * @property string $rate 倍率
 * @property string $host 主机名
 * @property int $port 连接端口
 * @property int $server_port 服务端口
 * @property int $allow_insecure 是否允许不安全
 * @property string|null $server_name
 * @property int $show 是否显示
 * @property int|null $sort
 * @property int $created_at
 * @property int $updated_at
 * @method static Builder|ServerTrojan newModelQuery()
 * @method static Builder|ServerTrojan newQuery()
 * @method static Builder|ServerTrojan query()
 * @method static Builder|ServerTrojan whereAllowInsecure($value)
 * @method static Builder|ServerTrojan whereCreatedAt($value)
 * @method static Builder|ServerTrojan whereGroupId($value)
 * @method static Builder|ServerTrojan whereHost($value)
 * @method static Builder|ServerTrojan whereId($value)
 * @method static Builder|ServerTrojan whereName($value)
 * @method static Builder|ServerTrojan whereParentId($value)
 * @method static Builder|ServerTrojan wherePort($value)
 * @method static Builder|ServerTrojan whereRate($value)
 * @method static Builder|ServerTrojan whereServerName($value)
 * @method static Builder|ServerTrojan whereServerPort($value)
 * @method static Builder|ServerTrojan whereShow($value)
 * @method static Builder|ServerTrojan whereSort($value)
 * @method static Builder|ServerTrojan whereTags($value)
 * @method static Builder|ServerTrojan whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ServerTrojan extends Model
{
    use Serialize;

    const FIELD_ID = "id";
    const FIELD_GROUP_ID = "group_id";
    const FIELD_PARENT_ID = "parent_id";
    const FIELD_TAGS = "tags";
    const FIELD_NAME = "name";
    const FIELD_RATE = "rate";
    const FIELD_HOST = "host";
    const FIELD_PORT = "port";
    const FIELD_SERVER_PORT = "server_port";
    const FIELD_ALLOW_INSECURE = "allow_insecure";
    const FIELD_SERVER_NAME = "server_name";
    const FIELD_SHOW = "show";
    const FIELD_SORT = "sort";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";
    const CONFIG = '{"run_type":"server","local_addr":"0.0.0.0","local_port":443,"remote_addr":"www.taobao.com","remote_port":80,"password":[],"ssl":{"cert":"server.crt","key":"server.key","sni":"domain.com"},"api":{"enabled":true,"api_addr":"127.0.0.1","api_port":10000}}';
    const METHOD = 'trojan';

    const SHOW_ON = 1;
    const SHOW_OFF = 0;

    const TYPE = "trojan";

    protected $table = 'server_trojan';
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
     * @return Collection
     */
    public static function nodes(): Collection
    {
        $servers = self::orderBy('sort', "ASC")->get();
        foreach ($servers as $server) {
            /**
             * @var ServerTrojan $server
             */
            $parentId = $server->getAttribute(ServerTrojan::FIELD_PARENT_ID);
            $nodeId = $parentId > 0 ? $parentId : $server->getKey();
            $cacheKeyOnline = CacheKey::get(CacheKey::SERVER_TROJAN_ONLINE_USER, $nodeId);
            $lastCheckAt = Cache::get(CacheKey::get(CacheKey::SERVER_TROJAN_LAST_CHECK_AT, $nodeId));
            $lastPushAt = Cache::get(CacheKey::get(CacheKey::SERVER_TROJAN_LAST_PUSH_AT, $nodeId));
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
     * fault nodes
     *
     * @return array
     */
    public static function faultNodeNames(): array
    {
        $result = [];
        $servers = self::where(ServerShadowsocks::FIELD_SHOW, self::SHOW_ON)->get();
        foreach ($servers as $server) {
            $parentId = $server->getAttribute(ServerTrojan::FIELD_PARENT_ID);
            $nodeId = $parentId > 0 ? $server->getAttribute(ServerTrojan::FIELD_PARENT_ID) : $server->getKey();
            $lastCheckAt = Cache::get(CacheKey::get(CacheKey::SERVER_TROJAN_LAST_CHECK_AT, $nodeId));

            if ($lastCheckAt < (time() - 300)) {
                array_push($result, $server->getAttribute(ServerTrojan::FIELD_NAME));
            }
        }
        return $result;
    }


    /**
     * config
     *
     * @param int $localPort
     * @return mixed
     */
    public function config(int $localPort)
    {
        $json = json_decode(self::CONFIG);
        $json->local_port = $this->getAttribute(self::FIELD_SERVER_PORT);
        $json->ssl->sni = $this->getAttribute(self::FIELD_SERVER_NAME) ?: $this->getAttribute(self::FIELD_HOST);
        $json->ssl->cert = "/root/.cert/server.crt";
        $json->ssl->key = "/root/.cert/server.key";
        $json->api->api_port = $localPort;
        return $json;
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

            /**
             * @var ServerTrojan $server
             */
            $server->setAttribute("type", self::TYPE);
            if ($server->getAttribute(self::FIELD_PARENT_ID) > 0) {
                $server->setAttribute('last_check_at', Cache::get(CacheKey::get(CacheKey::SERVER_TROJAN_LAST_CHECK_AT,
                    $server->getAttribute(self::FIELD_PARENT_ID))));
            } else {
                $server->setAttribute('last_check_at', Cache::get(CacheKey::get(CacheKey::SERVER_TROJAN_LAST_CHECK_AT,
                    $server->getKey())));
            }
        }
        return $servers;
    }

}
