<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;

/**
 * App\Models\ServerStat
 *
 * @property int $id
 * @property int $server_id 节点id
 * @property string $server_type 节点类型
 * @property string $u
 * @property string $d
 * @property string $record_type d day m month
 * @property int $record_at 记录时间
 * @property int $created_at
 * @property int $updated_at
 * @method static Builder|ServerStat newModelQuery()
 * @method static Builder|ServerStat newQuery()
 * @method static Builder|ServerStat query()
 * @method static Builder|ServerStat whereCreatedAt($value)
 * @method static Builder|ServerStat whereD($value)
 * @method static Builder|ServerStat whereId($value)
 * @method static Builder|ServerStat whereRecordAt($value)
 * @method static Builder|ServerStat whereRecordType($value)
 * @method static Builder|ServerStat whereServerId($value)
 * @method static Builder|ServerStat whereServerType($value)
 * @method static Builder|ServerStat whereU($value)
 * @method static Builder|ServerStat whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ServerStat extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_SERVER_ID = "server_id";
    const FIELD_SERVER_TYPE = "server_type";
    const FIELD_U = "u";
    const FIELD_D = "d";
    const FIELD_RECORD_TYPE = "record_type";
    const FIELD_RECORD_AT = "record_at";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const RECORD_TYPE_DAY = 'd';
    const RECORD_TYPE_MONTH = 'm';

    protected $table = 'server_stat';
    protected $dateFormat = 'U';


    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];
}
