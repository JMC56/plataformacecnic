<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

$dataFile = __DIR__ . "/data.json";

function readData($file){
  if(!file_exists($file)){
    return ["announcements"=>[], "courses"=>[]];
  }
  $raw = file_get_contents($file);
  $json = json_decode($raw, true);
  if(!is_array($json)) $json = ["announcements"=>[], "courses"=>[]];
  if(!isset($json["announcements"])) $json["announcements"] = [];
  if(!isset($json["courses"])) $json["courses"] = [];
  return $json;
}

function writeData($file, $data){
  $tmp = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  file_put_contents($file, $tmp, LOCK_EX);
}

$action = $_GET["action"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
  if($action === "get"){
    echo json_encode(readData($dataFile));
    exit;
  }
  echo json_encode(["ok"=>false, "error"=>"Invalid action"]);
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $body = json_decode(file_get_contents("php://input"), true);
  if(!is_array($body)) $body = [];

  $db = readData($dataFile);

  if($action === "createCourse"){
    $course = $body["course"] ?? null;
    if(!is_array($course) || empty($course["id"]) || empty($course["name"])){
      echo json_encode(["ok"=>false, "error"=>"Missing course"]);
      exit;
    }
    array_unshift($db["courses"], $course);
    writeData($dataFile, $db);
    echo json_encode(["ok"=>true, "data"=>$db]);
    exit;
  }

  if($action === "postAnnouncement"){
    $ann = $body["announcement"] ?? null;
    if(!is_array($ann) || empty($ann["id"]) || empty($ann["title"])){
      echo json_encode(["ok"=>false, "error"=>"Missing announcement"]);
      exit;
    }
    array_unshift($db["announcements"], $ann);
    writeData($dataFile, $db);
    echo json_encode(["ok"=>true, "data"=>$db]);
    exit;
  }

  if($action === "updateCourse"){
    $id = $body["id"] ?? "";
    $patch = $body["patch"] ?? null;
    if(!$id || !is_array($patch)){
      echo json_encode(["ok"=>false, "error"=>"Missing id/patch"]);
      exit;
    }
    foreach($db["courses"] as &$c){
      if(($c["id"] ?? "") === $id){
        foreach($patch as $k=>$v){
          $c[$k] = $v;
        }
        break;
      }
    }
    writeData($dataFile, $db);
    echo json_encode(["ok"=>true, "data"=>$db]);
    exit;
  }

  echo json_encode(["ok"=>false, "error"=>"Invalid action"]);
  exit;
}
