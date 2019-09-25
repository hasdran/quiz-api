<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/', function (Request $request, Response $response, array $args) use ($container) {
        // Sample log message
        $stmtEntrada = $container->get('db')->prepare("SELECT * FROM players");
     
        if($stmtEntrada->execute()) {
            $result = $stmtEntrada->fetchAll(PDO::FETCH_ASSOC);            
        }
        echo json_encode($result);  
    });

    $app->get('/player/find/{id}', function (Request $request, Response $response, array $args) use ($container) {
        // Sample log message
        $stmtEntrada = $container->get('db')->prepare("SELECT * FROM players WHERE id = :id");
        $stmtEntrada->bindParam("id", $args['id']);

        if($stmtEntrada->execute()) {
            $result = $stmtEntrada->fetchAll(PDO::FETCH_ASSOC)[0];
        }
        echo json_encode($result);  
    });     

    $app->post('/player', function(Request $request) use ($container) {

        $dadoJson = json_decode($request->getBody());        
        $stmt = $container->get('db')->prepare("INSERT INTO players (name, login, password) VALUES (:name, :login, :password)");    
        $stmt->bindParam("name", $dadoJson->name);
        $stmt->bindParam("login", $dadoJson->login);
        $stmt->bindParam("password", $dadoJson->password);

        if($stmt->execute()) {

            $stmtItemInserted = $container->get('db')->prepare("SELECT * FROM players ORDER BY id DESC LIMIT 1");
         
            if($stmtItemInserted->execute()) {
                $resultItemInserted = $stmtItemInserted->fetchAll(PDO::FETCH_ASSOC);                
            }
            $obj = (object)$resultItemInserted[0];
            echo json_encode($obj);  
      
        }   
    });

    $app->post('/player/validate', function(Request $request) use ($container) {

        $dadoJson = json_decode($request->getBody());        
        $stmt = $container->get('db')->prepare("SELECT * FROM players WHERE login = :login");    
        $stmt->bindParam("login", $dadoJson->login);

        if($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
            if ($result.is_null()) {
                echo json_encode(false);  
            }else{
                echo json_encode(true);
            }
        }  
    });    

    $app->post('/player/auth', function(Request $request) use ($container) {

        $dadoJson = json_decode($request->getBody());        
        $stmt = $container->get('db')->prepare("SELECT * FROM players WHERE login = :login AND password = :password LIMIT 1");    

        $stmt->bindParam("login", $dadoJson->login);
        $stmt->bindParam("password", $dadoJson->password);

        if($stmt->execute()) {

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
            echo json_encode($result);        
        }   
    });   
 
    $app->get('/questions', function (Request $request, Response $response, array $args) use ($container) {
        // Sample log message 
        $stmtEntrada = $container->get('db')->prepare("SELECT * FROM questions ORDER BY RAND() LIMIT 10");
     
        if($stmtEntrada->execute()) {
            $result = $stmtEntrada->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode($result);  
    });

    $app->get('/ranking', function (Request $request, Response $response, array $args) use ($container) {
        // Sample log message
        $stmtEntrada = $container->get('db')->prepare("SELECT ranking.idRanking, ranking.punctuation, players.* FROM ranking JOIN players on players.id = ranking.playerId");
     
        if($stmtEntrada->execute()) {
            $result = $stmtEntrada->fetchAll(PDO::FETCH_ASSOC);

            $array = Array();
            foreach ($result as $valor) {
                
                $player["id"] = $valor["id"];
                $player["name"] = $valor["name"];
                $player["login"] = $valor["login"];                
                $ranking["playerId"] = $player; 
                $ranking["punctuation"] = $valor["punctuation"];
                $ranking["idRanking"] = $valor["idRanking"];   

                array_push($array, $ranking);
            }
            echo json_encode($array);  
        }        
    });

    $app->get('/ranking/find/{id}', function (Request $request, Response $response, array $args) use ($container) {
        // Sample log message
        $stmtEntrada = $container->get('db')->prepare("SELECT ranking.idRanking, ranking.punctuation, players.* FROM ranking JOIN players on players.id = ranking.playerId AND ranking.playerId = :playerId");
        $stmtEntrada->bindParam("playerId", $args['id']);

        if($stmtEntrada->execute()) {
            $result = $stmtEntrada->fetchAll(PDO::FETCH_ASSOC)[0];                
            $player["id"] = $result["id"];
            $player["name"] = $result["name"];
            $player["login"] = $result["login"];                
            $ranking["playerId"] = $player; 
            $ranking["punctuation"] = $result["punctuation"];
            $ranking["idRanking"] = $result["idRanking"];   
            array_push($array, $ranking);            
        }
        // if ($array.count() !=0) {
            echo json_encode($ranking);
        // }
                        
    }); 

    $app->put('/ranking/{id}', function(Request $request, Response $response, array $args) use ($container) {
        $dadoJson = json_decode($request->getBody());        

        $stmtPut = $container->get('db')->prepare("UPDATE ranking SET playerId=:playerId, punctuation=:punctuation WHERE idRanking=:id"); 

        $stmtPut->bindParam("playerId", $dadoJson->playerId->id);
        $stmtPut->bindParam("punctuation", $dadoJson->punctuation);
        $stmtPut->bindParam("id", $args['id']);

        if($stmtPut->execute()){
            $stmtFind = $container->get('db')->prepare("SELECT ranking.idRanking, ranking.punctuation, players.* FROM ranking JOIN players on players.id = ranking.playerId AND ranking.idRanking = :id");   
            $stmtFind->bindParam("id", $args['id']);     
            if ($stmtFind->execute()) {
                $result = $stmtFind->fetchAll(PDO::FETCH_ASSOC)[0];                
                $player["id"] = $result["id"];
                $player["name"] = $result["name"];
                $player["login"] = $result["login"];                
                $ranking["playerId"] = $player; 
                $ranking["punctuation"] = $result["punctuation"];
                $ranking["idRanking"] = $result["idRanking"];   
                array_push($array, $ranking); 
                echo json_encode($ranking);                
            }
        }else{
            echo json_encode(array(['msg' => "[ERRO] Entrada não alterada!"]));                 
        }
    });

    $app->post('/ranking', function(Request $request) use ($container) {
        $dadoJson = json_decode($request->getBody());        
        $stmt = $container->get('db')->prepare("INSERT INTO ranking (playerId, punctuation) VALUES (:playerId, :punctuation)");    
        $stmt->bindParam("playerId", $dadoJson->playerId->id);
        $stmt->bindParam("punctuation", $dadoJson->punctuation);            
        if($stmt->execute()) {
            $stmtFind = $container->get('db')->prepare("SELECT ranking.idRanking, ranking.punctuation, players.* FROM ranking JOIN players on players.id = ranking.playerId AND players.id = :playerId");   
            $stmtFind->bindParam("playerId", $dadoJson->playerId->id);     
            if ($stmtFind->execute()) {
                $result = $stmtFind->fetchAll(PDO::FETCH_ASSOC)[0];                
                $player["id"] = $result["id"];
                $player["name"] = $result["name"];
                $player["login"] = $result["login"];                
                $ranking["playerId"] = $player; 
                $ranking["punctuation"] = $result["punctuation"];
                $ranking["idRanking"] = $result["idRanking"];   
                array_push($array, $ranking); 
                echo json_encode($ranking);               
            }                   
        }                           
    });  
};
