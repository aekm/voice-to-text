<?php

class index extends Controller{
    function __construct()
    {
        parent::__construct();
    }

    function index(){
        $data = [];
        $this->view('index/index',$data);
    }

    function save(){
        $this->model->save_voice();
    }

    // To connect JSON request from client to server --- index.php To model_index.php
    function get_data(){

        // will get audio file that has been saved from user's voice
        $result = $this->model->Get_Data();
        echo json_encode($result);
    }

    // To connect JSON request from client to server --- index.php To model_index.php
    function login_neivsa(){

        // will get response from Login_Neivsa function in model_index.php
        $result = $this->model->Login_Neivsa();
        echo json_encode($result);
    }

    // To connect JSON request from client to server --- index.php To model_index.php
    function ariana(){
        $data = $_POST;
        // will get response from ariana function in model_index.php
        $result = $this->model->ariana($data);
        echo json_encode($result);
    }


    function nevisa(){
        $data = $_POST;
        $res = $this->model->nevisa_voice($data);
        echo json_encode($res);
    }


}