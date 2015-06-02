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
       
        echo_response($status_code,$response);
    }

);


$app->get('/recommendations/:id',function($id)
{   
    //$id=$app->request->get('id');  
    $response=array();
    $db=new DbHandler();
    $recommendation=$db->getRecommendations($id);
    $response["recommendation"]=$recommendation;
    echo_response(200,$response);
}   
    );

$app->post('/details/',function() use($app)
    {
        $response=array();
        $db=new DbHandler();
        $idItem=$app->request->post('idItem');
        $idUser=$app->request->post('idUser');

        $response=$db->getItemById($idItem,$idUser);//<-_---------------------
        echo_response(200,$response);
    });

$app->post('/search/',function() use($app)
    {
        $response=array();
        $db=new DbHandler();
        
        $restaurant=$app->request->post('restaurant');
        $response["restaurants"]=$db->getItemByName($restaurant);
        echo_response(200,$response);
    });
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
