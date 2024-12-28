<?php

namespace App\Rules;

use App\ReviewPointManagement;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class AfterExistingStartDate  implements Rule
{
    protected $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    public function passes($attribute, $value)
    {
        if (is_null($this->id)) {
            $latestStartDate = ReviewPointManagement::orderBy('start_at', 'desc')->value('start_at');
        } else {
            $latestStartDate = ReviewPointManagement::whereNotIn('id', [$this->id])->orderBy('start_at', 'desc')->value('start_at');
        }

        $dayAfterLatestStartDate = Carbon::parse($latestStartDate);
        
        // データがない場合比較対象がないため通す
        if (!$latestStartDate) {
            return true;
        }

        if (Carbon::parse($value)->isSameDay($dayAfterLatestStartDate)) {
            return false;
        } elseif (Carbon::parse($value)->lt($dayAfterLatestStartDate)) {
            return false;
        } else {
            return true;
        }
    }

    public function message()
    {
        return '指定された開始日は、最新のスケジュールの開始日より後でなければなりません。';
    }
}
