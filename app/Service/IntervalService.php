<?php

declare(strict_types=1);

namespace App\Service;

use Carbon\CarbonInterval;

class IntervalService
{
    public function format(CarbonInterval $interval): string
    {
        $interval->cascade();

        return ((int) floor($interval->totalHours)).':'.$interval->format('%I:%S');
    }
    function roundTime($diffInSeconds, string $mode = 'nearest', int $intervalMinutes = 15): CarbonInterval
    {
//        $diffInSeconds = $interval;
//        dd($diffInSeconds);
        $intervalInSeconds = $intervalMinutes * 60;

        switch ($mode) {
            case 'up':
                $roundedSeconds = ceil($diffInSeconds / $intervalInSeconds) * $intervalInSeconds;
                break;
            case 'down':
                $roundedSeconds = floor($diffInSeconds / $intervalInSeconds) * $intervalInSeconds;
                break;
            case 'nearest':
            default:
                $roundedSeconds = round($diffInSeconds / $intervalInSeconds) * $intervalInSeconds;
                break;
        }

        return CarbonInterval::seconds($roundedSeconds)->cascade();
    }
}
