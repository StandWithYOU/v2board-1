<?php


namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;


/**
 * App\Models\Plan
 *
 * @property int $id
 * @property int $group_id
 * @property int $transfer_enable
 * @property string $name
 * @property int $show
 * @property int|null $sort
 * @property int $renew
 * @property string|null $content
 * @property int|null $month_price
 * @property int|null $quarter_price
 * @property int|null $half_year_price
 * @property int|null $year_price
 * @property int|null $two_year_price
 * @property int|null $three_year_price
 * @property int|null $onetime_price
 * @property int|null $reset_price
 * @property int $created_at
 * @property int $updated_at
 * @method static Builder|Plan newModelQuery()
 * @method static Builder|Plan newQuery()
 * @method static Builder|Plan query()
 * @method static Builder|Plan whereContent($value)
 * @method static Builder|Plan whereCreatedAt($value)
 * @method static Builder|Plan whereGroupId($value)
 * @method static Builder|Plan whereHalfYearPrice($value)
 * @method static Builder|Plan whereId($value)
 * @method static Builder|Plan whereMonthPrice($value)
 * @method static Builder|Plan whereName($value)
 * @method static Builder|Plan whereOnetimePrice($value)
 * @method static Builder|Plan whereQuarterPrice($value)
 * @method static Builder|Plan whereRenew($value)
 * @method static Builder|Plan whereResetPrice($value)
 * @method static Builder|Plan whereShow($value)
 * @method static Builder|Plan whereSort($value)
 * @method static Builder|Plan whereThreeYearPrice($value)
 * @method static Builder|Plan whereTransferEnable($value)
 * @method static Builder|Plan whereTwoYearPrice($value)
 * @method static Builder|Plan whereUpdatedAt($value)
 * @method static Builder|Plan whereYearPrice($value)
 * @mixin Eloquent
 * @property int|null $reset_traffic_method
 * @method static Builder|Plan whereResetTrafficMethod($value)
 */
class Plan extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_GROUP_ID = "group_id";
    const FIELD_TRANSFER_ENABLE = "transfer_enable";
    const FIELD_NAME = "name";
    const FIELD_SHOW = "show"; //销售状态
    const FIELD_SORT = "sort";
    const FIELD_RENEW = "renew";  //是否可续费
    const FIELD_CONTENT = "content";
    const FIELD_MONTH_PRICE = "month_price";
    const FIELD_QUARTER_PRICE = "quarter_price";
    const FIELD_HALF_YEAR_PRICE = "half_year_price";
    const FIELD_YEAR_PRICE = "year_price";
    const FIELD_TWO_YEAR_PRICE = "two_year_price";
    const FIELD_THREE_YEAR_PRICE = "three_year_price";
    const FIELD_ONETIME_PRICE = "onetime_price";  //一次性价格
    const FIELD_RESET_PRICE = "reset_price";   //重置流量价格
    const FIELD_RESET_TRAFFIC_METHOD = 'reset_traffic_method';
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const STR_TO_TIME = [
        self::FIELD_MONTH_PRICE => 1,
        self::FIELD_QUARTER_PRICE => 3,
        self::FIELD_HALF_YEAR_PRICE => 6,
        self::FIELD_YEAR_PRICE => 12,
        self::FIELD_TWO_YEAR_PRICE => 24,
        self::FIELD_THREE_YEAR_PRICE => 36
    ];

    const SHOW_OFF = 0;
    const SHOW_ON = 1;

    const RENEW_OFF = 0;
    const RENEW_ON = 1;

    const RESET_TRAFFIC_METHOD_SYSTEM = null;
    const RESET_TRAFFIC_METHOD_MONTH_FIRST_DAY = 0;
    const RESET_TRAFFIC_METHOD_ORDER_DAY = 1;
    const RESET_TRAFFIC_METHOD_NOT_RESET = 2;

    protected $table = 'plan';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];

    /**
     * users
     *
     * @return Collection
     */
    public function users(): Collection
    {
        return $this->hasMany("App\Models\User")->get();
    }

    /**
     * check show
     *
     * @return bool
     */
    public function isShowOn(): bool
    {
        return $this->getAttribute(self::FIELD_SHOW) == self::SHOW_ON;
    }


    /**
     * check renew
     *
     * @return bool
     */
    public function isRenewOn(): bool
    {
        return $this->getAttribute(self::FIELD_RENEW) == self::RENEW_ON;
    }


    /**
     * get show plans
     *
     * @return Builder[]|Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|Plan[]
     */
    public static function getShowPlans()
    {
        return self::where(self::FIELD_SHOW, self::SHOW_ON)->orderBy(self::FIELD_SORT, "ASC")->get();
    }


    /**
     * 格式化时间
     *
     * @param string $cycle
     * @param mixed  $timestamp
     * @return false|int
     */
    public static function expiredTime(string $cycle, $timestamp = null)
    {
        if ($timestamp === null || $timestamp < time() ) {
            $timestamp = time();
        }
        switch ($cycle) {
            case Plan::FIELD_MONTH_PRICE:
                $time = strtotime('+1 month', $timestamp);
                break;
            case Plan::FIELD_QUARTER_PRICE:
                $time = strtotime('+3 month', $timestamp);
                break;
            case Plan::FIELD_HALF_YEAR_PRICE:
                $time = strtotime('+6 month', $timestamp);
                break;
            case Plan::FIELD_YEAR_PRICE:
                $time = strtotime('+12 month', $timestamp);
                break;
            case Plan::FIELD_TWO_YEAR_PRICE:
                $time = strtotime('+24 month', $timestamp);
                break;
            case Plan::FIELD_THREE_YEAR_PRICE:
                $time = strtotime('+36 month', $timestamp);
                break;
            default:
                $time = null;
        }
        return $time;
    }


}
