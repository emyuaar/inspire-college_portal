<?php

namespace App\Traits;

use Carbon\Carbon;

trait SupportTimezoneTrait
{
    protected function localizeDate($date, $format = 'd M, H:i')
    {
        if (!$date) return '';
        
        $tz = 'Asia/Karachi';
        
        $carbon = Carbon::parse($date);
        return $carbon->setTimezone($tz)->format($format);
    }

    protected function localizeRelative($date)
    {
        if (!$date) return '';
        
        $tz = 'Asia/Karachi';
        
        $carbon = Carbon::parse($date);
        return $carbon->diffForHumans();
    }
}
