<?php
namespace App\Controllers;

use App\Models\AwardHistory;

class AwardHistoryController
{
    private $awardHistory;

    public function __construct()
    {
        $this->awardHistory = new AwardHistory();
    }

    public function index()
    {
        return $this->awardHistory->all();
    }

    public function show($id)
    {
        return $this->awardHistory->find($id);
    }

    public function store($data)
    {
        return $this->awardHistory->create($data);
    }

    public function incrementVoteCount($awardHistoryId)
    {
        return $this->awardHistory->incrementVoteCount($awardHistoryId);
    }

}
