<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;


/**
 * App\Models\ServerLog
 *
 * @property int $id
 * @property int $user_id
 * @property int $server_id
 * @property string $u
 * @property string $d
 * @property string $rate
 * @property string $method
 * @property int $log_at
 * @property int $created_at
 * @property int $updated_at
 * @method static Builder|ServerLog newModelQuery()
 * @method static Builder|ServerLog newQuery()
 * @method static Builder|ServerLog query()
 * @method static Builder|ServerLog whereCreatedAt($value)
 * @method static Builder|ServerLog whereD($value)
 * @method static Builder|ServerLog whereId($value)
 * @method static Builder|ServerLog whereLogAt($value)
 * @method static Builder|ServerLog whereMethod($value)
 * @method static Builder|ServerLog whereRate($value)
 * @method static Builder|ServerLog whereServerId($value)
 * @method static Builder|ServerLog whereU($value)
 * @method static Builder|ServerLog whereUpdatedAt($value)
 * @method static Builder|ServerLog whereUserId($value)
 * @mixin Eloquent
 */
class ServerLog extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_USER_ID = "user_id";
    const FIELD_SERVER_ID = "server_id";
    const FIELD_U = "u";
    const FIELD_D = "d";
    const FIELD_RATE = "rate";
    const FIELD_METHOD = "method";
    const FIELD_LOG_AT = "log_at";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    protected $table = 'server_log';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];


    /**
     * add traffic
     *
     * @param int $u
     * @param int $d
     * @return bool
     */
    public function addTraffic(int $u, int $d): bool
    {
        $this->setAttribute(User::FIELD_U, $this->getAttribute(User::FIELD_U) + $u);
        $this->setAttribute(User::FIELD_D, $this->getAttribute(User::FIELD_D) + $d);
        return true;
    }

}
