<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;


/**
 * App\Models\Payment
 *
 * @property int $id
 * @property string $uuid
 * @property string $payment
 * @property string $name
 * @property array $config
 * @property int $enable
 * @property int|null $sort
 * @property int $created_at
 * @property int $updated_at
 * @method static Builder|Payment newModelQuery()
 * @method static Builder|Payment newQuery()
 * @method static Builder|Payment query()
 * @method static Builder|Payment whereConfig($value)
 * @method static Builder|Payment whereCreatedAt($value)
 * @method static Builder|Payment whereEnable($value)
 * @method static Builder|Payment whereId($value)
 * @method static Builder|Payment whereName($value)
 * @method static Builder|Payment wherePayment($value)
 * @method static Builder|Payment whereSort($value)
 * @method static Builder|Payment whereUpdatedAt($value)
 * @method static Builder|Payment whereUuid($value)
 * @mixin \Eloquent
 */
class Payment extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_UUID = "uuid";
    const FIELD_PAYMENT = "payment";
    const FIELD_NAME = "name";
    const FIELD_CONFIG = "config";
    const FIELD_ENABLE = "enable";
    const FIELD_SORT = "sort";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const PAYMENT_ON = 1;
    const PAYMENT_OFF = 0;

    protected $table = 'payment';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT=> 'timestamp',
        self::FIELD_CONFIG => 'array'
    ];


    /**
     * check enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->getAttribute(self::FIELD_ENABLE);
    }

    /**
     * find by uuid
     *
     * @param string $uuid
     * @return Builder|Model|object|Payment|null
     */
    public static function findByUUID(string $uuid)
    {
        return self::where(self::FIELD_UUID, $uuid)->first();
    }
}
