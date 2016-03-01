<?php 


function getItemContext($address)
{
	$address=urlencode($address);
	$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false&key=AIzaSyDL7k89KHP34AMUtJSUUwoqG1KG0ZkImWs";
    //echo "<b>".$url."</b><br>";
    $response = file_get_contents($url);
    $json = json_decode($response,true);
    //var_dump($json);
    if($json["status"] =="OK")
    {
        

        
        $lat = $json['results'][0]['geometry']['location']['lat'];
        $lng = $json['results'][0]['geometry']['location']['lng'];
        //echo "entra ";
        return array(
        	"latitude" => $lat,
        	"longitude" => $lng,
        	);
    }
    else
    {
        return null;
    }
}

/*

if  (!$con) {
    die('No pudo conectarse: ' . mysql_error());
}
else
    echo"conectado <br>";
mysql_set_charset('utf8',$con);
mysql_select_db("reja1526");

$query="SELECT idItem,direccion FROM item_table_full order by idItem asc";

$res=mysql_query($query);
//echo mysql_num_rows($res);

while($item=mysql_fetch_assoc($res))
{
    
    echo "<b>".$item["idItem"].", ".$item["direccion"]."</b><br>";
    $direccion=$item["direccion"];
    $context=getItemContext($direccion);
    
    if($context)
    {
    echo $context["latitude"].", ".$context["longitude"]."<br>";
        $query2=
        "   UPDATE 
                item_table_full 
            set 
                latitude=".$context["latitude"].", 
                longitude=".$context["longitude"]."
            WHERE idItem=".$item["idItem"];
        mysql_query($query2);
    }
}*/

