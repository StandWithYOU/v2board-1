<?php

namespace App\Models;

use App\Models\Traits\Serialize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\InvitePackage
 *
 * @property int $id
 * @property int|null $user_id 用户ID
 * @property int|null $plan_id 计划ID
 * @property string|null $plan_cycle 计划周期
 * @property int|null $from_user_id 被邀请人ID
 * @property int|null $status 状态(0:未应用，1:应用）
 * @property int|null $created_at 创建时间
 * @property int|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage query()
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage whereFromUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage wherePlanCycle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvitePackage whereUserId($value)
 * @mixin \Eloquent
 */
class InvitePackage extends Model
{
    use Serialize;

    const FIELD_ID = "id";
    const FIELD_USER_ID = "user_id";
    const FIELD_FROM_USER_ID = 'from_user_id';
    const FIELD_PLAN_ID = "plan_id";
    const FIELD_PLAN_CYCLE = 'plan_cycle';
    const FIELD_STATUS = 'status';
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const STATUS_UNUSED = 0;
    const STATUS_USED = 1;
    const STATUS_INVALID = -1;

    protected $table = 'invite_package';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];


    /**
     * get User
     *
     * @return Model|BelongsTo|object|null
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User')->first();
    }


    /**
     * get from User
     *
     * @return Model|BelongsTo|object|null
     */
    public function fromUser()
    {
        return $this->belongsTo('App\Models\User', self::FIELD_FROM_USER_ID)->first();
    }


    /**
     * get plan
     *
     * @return Model|BelongsTo|object|null
     */
    public function plan()
    {
        return $this->belongsTo('App\Models\Plan')->first();
    }

    /**
     * check package status
     *
     * @return bool
     */
    public function isUsed(): bool
    {
        return $this->getAttribute(self::FIELD_STATUS) === self::STATUS_USED;
    }

    /**
     * check package status is invalid
     *
     * @return bool
     */
    public function isInvalid(): bool
    {
        return $this->getAttribute(self::FIELD_STATUS) === self::STATUS_INVALID;
    }
}
