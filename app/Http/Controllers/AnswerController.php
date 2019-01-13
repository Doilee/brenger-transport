<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnswerController extends Controller
{
    /**
     * Collection $jobs
     */
    protected $jobs;

    public function __construct()
    {
        $this->jobs = collect(json_decode(file_get_contents("https://hgjn.nl/cc.php"), true));
    }

    public function one()
    {
        $result = $this->jobs->map(function($job) {
            $job['difference'] = $job['delivery'] - $job['pickup'];
            return $job;
        })->sortByDesc('difference')->first();

        return $result;
    }

    public function two()
    {
        $result = $this->jobs->map(function($job) {
            $job['pickup_date'] = date('Y-m-d', $job['pickup'] / 1000);
            return $job;
        })->groupBy('pickup_date')->map(function($pickup) {
            return count($pickup);
        })->sort();

        return [
            'date' => $result->keys()->last(),
            'count' => $result->last(),
        ];
    }

    public function three()
    {
        $result = array_sort(array_count_values($this->jobs->map(function($job) {
            $job['pickup_date'] = date('Y-m-d', $job['pickup'] / 1000);
            $job['delivery_date'] = date('Y-m-d', $job['delivery'] / 1000);

            $period = CarbonPeriod::create($job['pickup_date'], $job['delivery_date']);

            foreach($period as $date) {
                $job['transit_dates'][] = $date->toDateString();
            }

            return $job;
        })->pluck('transit_dates')->collapse()->toArray()));

        return [
            'date' => array_last(array_keys($result)),
            'count' => array_last($result)
        ];
    }
}
