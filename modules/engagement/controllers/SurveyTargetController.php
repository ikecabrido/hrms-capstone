<?php
namespace App\Controllers;

use App\Models\SurveyTarget;

class SurveyTargetController
{
    private $surveyTarget;

    public function __construct()
    {
        $this->surveyTarget = new SurveyTarget();
    }

    public function index()
    {
        return $this->surveyTarget->all();
    }

    public function show($id)
    {
        return $this->surveyTarget->find($id);
    }

    public function store($data)
    {
        return $this->surveyTarget->create($data);
    }
}
