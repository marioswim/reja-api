<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
require 'Slim/Slim.php';
require_once './include/DbHandler.php';

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */

// GET route
$app->get(
    '/',
    function () {
      
        echo "mamsam";
    }
);
/*
* logea a un usuario en el sistema.
*/
// POST route
$app->post(
    '/login',
    function () use ($app) {

        $response=array();
        $status_code=500;
        $db= new DbHandler();
        $email = $app->request->post('email');  

        
        $userId=$db->getUserByEmail($email);
        if($userId!=NUll)
        {
            $response["userId"]=$userId;
            $response["message"]="User login";
            $status_code=200;
        }
        else
        {   

            $res=$db->createUser($email);

            if($res==USER_CREATED_SUCCESSFULLY)
            {
                $response["userId"]=$db->getUserByEmail($email);
                $response["message"]="Create successfully";
                $status_code=201;
            }
            else if ($res==USER_CREATE_FAILED)
            {
                $response["message"]="Error while creating";
                $status_code=500;
            }
            else if ($res==USER_ALREADY_EXISTED)
            {
                $response["userId"]=$db->getUserByEmail($email);
                $response["message"]="User already exists";
                $status_code=500;
            }
        }
        $response["status_code"]=$status_code;
        echo_response($status_code,$response);
    }

);

/*
* Devuelve la recomendacion para un usuario.
* @id el identificador del usuario correspondiente.
* @return un JSONObject con la lista de restaurantes recomendados
*/
$app->get('/recommendations/:id',function($id)
{   
    //$id=$app->request->get('id');  
    $response=array();
    $db=new DbHandler();
    $recommendation=$db->getRecommendations($id);
    $response["recommendation"]=$recommendation;
    echo_response(200,$response);   
});
/*
* busca los detalles de un restaurante dado.
* @param idItem el identificador del restaurante.
* @param idUser el identificador del usuario.
* @return un JSONOBject con la informacion del restaurante.
*/
$app->post('/details/',function() use($app)
{
    $response=array();
    $db=new DbHandler();
    $idItem=$app->request->post('idItem');
    $idUser=$app->request->post('idUser');

    $response=$db->getItemById($idItem,$idUser);//<-_---------------------
    echo_response(200,$response);
});
/*
* buscar los restuarantes segun la cadena de texto dada,ç
* @Param restaurant la cadena de texto a buscar
* @return el codigo de la operacion, y un JSONObject con todos los items encontrados
*/
$app->post('/search/',function() use($app)
{
    $response=array();
    $db=new DbHandler();
    
    $restaurant=$app->request->post('restaurant');
    $response["restaurants"]=$db->getItemByName($restaurant);
    echo_response(200,$response);
});

/*
* punta o modifica un restaurante.
* @return el codigo de estado de la peticion
*/
$app->post('/rating/',function() use($app)
{
    $response=array();
    $db=new DbHandler();
    
    $itemID=$app->request->post("idItem");
    $userID=$app->request->post("idUser");
    $rating=$app->request->post("rating");
    /*$response["Item"]=$itemID;
    $response["user"]=$userID;
    $response["rating"]=$rating;*/
    $code=$db->setRating($userID,$itemID,$rating);      
    echo_response($code,$response);
    
});
/*
* Crea un grupo,
* @param id_admin identificador de usuario del administrador
* @param id_group idfentificador del grupo.
*/
$app->post('/addGroup',function() use($app)
{
    
    $response=array();
    $db= new DbHandler();
    $id_admin=$app->request->post("adminId");
    $id_group=$app->request->post("groupId"); 
    $adminId=$db->getAdminGroup($id_group);  
    if($id_admin==$adminId)
    {
        $response["status_code"]=200;
        $response["code_error"]=0;
        $response["message"]="Ya es administrador de este grupo";
    }
    else    
    {

        $response=$db->createGroup($id_admin,$id_group);
    }

    
    
    echo_response($response["status_code"],$response);

});
/*
* Añade a un usuario a la lista de pendientes de un grupo
* @param groupId identificador de grupo al que se desea pertenecer
*

$app->get('/pending/:groupdId',function($groupId)
{
    
    $db=new DbHandler();
    $response=$db->list_temp($groupId);
    echo_response($response["status_code"],$response);
});*/
/*
* Acepta un usuario en la lista de pendientes, en el grupo.
* @param groupId identificador del grupo
* @para userId identificador del usuario
*/
$app->post("/accept",function () use($app)
{
    
    $groupId=$app->request->post("groupId");
    $userId=$app->request->post("userId");
    $db=new DbHandler();
    $response=$db->insertIntoGroup($userId,$groupId);
    echo_response($response["status_code"],$response);
});
/*
* Da de baja un usuario.
* @param groupId identificador del grupo
* @param userId identificador del usuario.
*/
$app->post("/deny",function() use($app)
{
    $groupId=$app->request->post("groupId");
    $userId=$app->request->post("userId");
    $db=new DbHandler();
    $response=$db->deleteFromGroup($userId,$groupId);
    echo_response($response["status_code"],$response);
});
/*
*   Obtiene los miembros pertenecientes al grupo
*   @param groupId identificador del grupo
*/
$app->post("/:groupId/members", function($groupId) use($app)
{
    $db=new DbHandler();
    $response=array();
    $userId=$app->request->post("userId");
    $adminId=$db->getAdminGroup($groupId);
    if($userId==$adminId)
    {
        $response1=$db->list_members($groupId);
        $response2=$db->list_temp($groupId);
        if($response1["status_code"]==$response2["status_code"])
            $response["status_code"]=$response1["status_code"];
        else
            $response["status_code"]=500;
        $response["miembros"]=$response1;
        $response["pendientes"]=$response2;
    }
    else
    {
        $response["status_code"]=500;
        $response["message"]="No eres el administrador de este grupo";
    }
    echo_response($response["status_code"],$response);
});

$app->post("/searchGroup",function()use($app)
{
    $text=$app->request->post("textGroup");
    $db=new DbHandler();

    $response=$db->searchGroup($text);
    echo_response($response["status_code"],$response);
});
$app->post("/join",function()use($app)
{
    $groupId=$app->request->post("groupId");
    $userId=$app->request->post("userId");
    $db=new DbHandler();
    $response=$db->insertIntoTemp($userId,$groupId);
    echo_response($response["status_code"],$response);
});


$app->post("/context",function()use($app)
{
    $params=array();
    $userId = $app->request->post("idUser");
    $params["my_lat"] = $app->request->post("my_lat");
    $params["my_long"] =$app->request->post("my_long");
    $params["maxDist"] =$app->request->post("maxDist");
    $db=new DbHandler();
    $recommendation=$db->getRecommendations($userId);
    
    $result=filterBydist($recommendation,$params);
    $response["recommendation"]=$result;

    echo_response(200,$response);

});
$app->post("/groupRecommendation",function()use($app)
{
    $params=array();
    $name = $app->request->post("groupId");
    $adminId = $app->request->post("adminId");

    $db=new DbHandler();

    $Gid=$db->getGroupGID($name); // obtiene el id numero del grupo
    $tempGidInRatingTable=$db->addGidInUsers($Gid); // inserta el id numerico del grupo en la tabla user
    $recommendation=$db->getRatingsUsersGroup($name); // obtiene todos los ratings de los usuarios del grupo
    $average=average($recommendation); // calcula la media de cada item

    foreach ($average as $iditem => $rating) 
    {
        $db->setRating($tempGidInRatingTable,$iditem,$rating);//añade el rating medio de cada item, en el grupo
    }

    $recommendation=$db->getRecommendations($tempGidInRatingTable); //obtiene la recomendacion del grupo.
    $db->removeGroupRecomendation($tempGidInRatingTable);//elimina las recomendaciones del grupo
    $db->removeGroupUser($tempGidInRatingTable);// elimina el id numerico del grupo de la tabla user
    $response["recommendation"]=$recommendation;
    

    echo_response(200,$response);

});

function average($items)
{
    $average=array();
    $i=0;
    foreach ($items as $iduser => $item) 
    {
        foreach ($item as $iditem => $rating) 
        {
            if(isset($average[$iditem]))
            {
                $average[$iditem]+=$rating;
            }
            else
            {
                $average[$iditem]= $rating;
            }
        }
        $i++;
    }
    foreach ($average as $key => $rating) 
    {
        $average[$key]=$rating/$i;
    }
    
    return $average;
}
function filterByDist($recommendation,$params)
{
    $lat1=$params["my_lat"];
    $lon1=$params["my_long"];
    $result=array();
    foreach($recommendation as $item)
    {   

        $lat2=$item["latitude"];
        $lon2=$item["longitude"];
        
        $radius = 6378.137;
        $dlon=$params["my_long"]-$item["longitude"];
        $distancia=acos(sin(deg2rad($params["my_lat"])) * sin(deg2rad($item["latitude"])) +  cos(deg2rad($params["my_lat"])) * cos(deg2rad($item["latitude"])) * cos(deg2rad($dlon)))*$radius; 
        $aux=$item;
        if($distancia<=$params["maxDist"])
        //$aux2=array("distancia" => $distancia, "lat1" => $lat1, "lon1" => $lon1, "lat2" => $lat2,"lon2" => $lon2,);
                //array_push($aux, $aux2);
                array_push($result, $item);
    }
    return $result;
}
function echo_response($status_code,$response)
{
     $app=\Slim\Slim::getInstance();
     $app->status($status_code);
     $app->contentType('application/json');
     echo json_encode($response);
}
/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
