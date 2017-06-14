<?php
namespace website;
class Controller_Dashboard extends \Controller_App
{
    public function index()
    {

    }

    public function beforeFilter()
    {
        $this->Auth->allow('index');
        parent::beforeFilter(); /// SIEMPRE DEJAR ESTO AL FINAL
    }
}
