<?php

    include_once('lib/controller/dataset/pbi.php');
    include_once('lib/common.php');
    header('Content-Type: application/json; charset=utf-8');
    #
    $module = CleanData("query");
    #
    $a = new Dataset\Pbi();
    if($module == 'geo_location'){
        $data = $a->GeoLocationSet();
        if(count($data)){
            echo json_encode(array('status'=>'success','dataset'=>'geo location','total'=>count($data),'data'=>$data));
        }else{
            echo json_encode(array('status'=>'error','msg'=>'No data found'));
        }
    }
    elseif($module == 'gs_combined'){
        $data = $a->GsCombined();
        if(count($data)){
            echo json_encode(array('status'=>'success','dataset'=>'Full GS1 verification data','total'=>count($data),'data'=>$data));
        }else{
            echo json_encode(array('status'=>'error','msg'=>'No data found'));
        }
    }
    elseif($module == 'gs_scanned_list'){
        $data = $a->gs_scanned_list();
        if(count($data)){
            echo json_encode(array('status'=>'success','dataset'=>'GS1 Scanned list','total'=>count($data),'data'=>$data));
        }else{
            echo json_encode(array('status'=>'error','msg'=>'No data found'));
        }
    }
    elseif($module == 'gs_verification_list'){
        $data = $a->gs_verification_list();
        if(count($data)){
            echo json_encode(array('status'=>'success','dataset'=>'GS1 Verificied report list','total'=>count($data),'data'=>$data));
        }else{
            echo json_encode(array('status'=>'error','msg'=>'No data found'));
        }
    }
    elseif($module == 'gs_summary_data'){
        $data = $a->gs_summary_data();
        if(count($data)){
            echo json_encode(array('status'=>'success','dataset'=>'GS1 summary data','total'=>count($data),'data'=>$data));
        }else{
            echo json_encode(array('status'=>'error','msg'=>'No data found'));
        }
    }
    else{
        echo json_encode(array('status'=>'error','msg'=>'Error: no query selected'));
    }
?>