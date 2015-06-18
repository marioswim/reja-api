<?php
 
/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Mario Quesada
 */
class DbHandler
{
 
    private $conn;
    private $path; 

    function __construct() 
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        
        $db = new DbConnect();
        $this->conn = $db->connect();
        $this->conn->set_charset("utf8");
        date_default_timezone_set('Europe/Madrid');
        $this->path="../recommenderLib/"; 
    }
    
    private function salt()
    {
        $length=15;
        $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        return substr(str_shuffle($characters),0,$length);
    }
    function test()
    {

        
        $stmt=$this->conn->prepare(
            "SELECT id, username 
            FROM reja1526.jos1524_users");

        $stmt->execute();
        $stmt->bind_result($id,$username);
        $users=array();
        while($stmt->fetch())
        {
            $tmp=array();
            $tmp["id"]=$id;
            $tmp["username"]=$username;
            array_push($users, $tmp);
        }

        $stmt->close();

        return $users;
    }
/*-----------------Funciones para el login-------------*/
    function getUserByEmail($email)
    {
        $stmt=$this->conn->prepare(
            "SELECT id 
            FROM reja1526.jos1524_users 
            WHERE email=?");

        $stmt->bind_param("s",$email);
        if($stmt->execute())
        {
            $stmt->bind_result($id);
            $stmt->fetch();
            $stmt->close();
            return $id;
        }
        return NULL;
    }
  
    function createUser($email)
    {
       
        if(!$this->isUserExists($email))
        {
            //inserta en jos1524_users
            $stmt=$this->conn->prepare(
                "INSERT INTO reja1526.jos1524_users ( username, email, password, usertype, gid,registerDate)  
                VALUES (?,?,?,'Registered',18,?)");
            
            $aux=explode("@", $email);
            $username=$aux[0];
            $salt=$this->salt();
            $password=md5($username.$salt).":".$salt;
            $date=date("Y-m-d H:i:s");

            $stmt->bind_param('ssss',$username,$email,$password,$date);
            $result=$stmt->execute();

            if($result) 
                $stmt->close();    
            else
                return USER_CREATE_FAILED;
            
            
            //seleccion el id del nuevo usuario
            $stmt=$this->conn->prepare(
                "SELECT id 
                FROM reja1526.jos1524_users 
                WHERE username=?");

            $stmt->bind_param('s',$username);
            
            if($stmt->execute())
            {
                $stmt->bind_result($id);
                $stmt->fetch();
                $stmt->close();
            }
            else
                return USER_CREATE_FAILED;
            //inserta el nuevo usuario en el core de joomla.
           
            $stmt=$this->conn->prepare(
                "INSERT INTO reja1526.jos1524_core_acl_aro( section_value, value, name) 
                VALUES ('users',?,?)");

            $stmt->bind_param('ss',$id,$username);

            if($stmt->execute())
                $stmt->close();    
            else
                return USER_CREATE_FAILED;   
            
            //selecciona el id de la nueva tupla.
            $stmt=$this->conn->prepare(
                "SELECT id 
                FROM reja1526.jos1524_core_acl_aro 
                WHERE value=?");

            $stmt->bind_param('i',$id);

            if($stmt->execute())
            {
                $stmt->bind_result($id_aro);
                $stmt->fetch();
                $stmt->close();
            }
            else
                return USER_CREATE_FAILED;
            //inserta el usuario nuevo en el grupo correspondiente.
           
            $stmt=$this->conn->prepare(
                "INSERT INTO reja1526.jos1524_core_acl_groups_aro_map( group_id, aro_id ) 
                VALUES (18,?)");

            $stmt->bind_param('i',$id_aro);

            if($stmt->execute())
            {
                $stmt->close(); 
                exec("java -jar ".$this->path."recommenderLib.jar -manageRatingDatabase ".$this->path."mysqlDatasetConfiguration.xml -addUser ".$id);   
                return USER_CREATED_SUCCESSFULLY;
            }
            else
                return USER_CREATE_FAILED;  
        }
        else            
            return USER_ALREADY_EXISTED;
    }

    
    private function isUserExists($email)
    {
        $stmt=$this->conn->prepare(
            "SELECT id 
            FROM reja1526.jos1524_users 
            WHERE email=?");

        $stmt->bind_param('s',$email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows=$stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /*------------Funciones para la recomendacion-----------*/

    public function getRecommendations($userId)
    {

        exec("java -jar ".$this->path."recommenderLib.jar -u ".$userId." -configFile ".$this->path."mysqlRecommendationConfiguration.xml");
        $stmt=$this->conn->prepare(
            "SELECT r.iditem, r.preference, i.nombre, i.direccion
            FROM sad_reja.reja_recomendaciones r, reja1526.item_table_full i
            WHERE i.idItem=r.iditem AND idUser=? 
            ORDER BY r.preference DESC ");

        $stmt->bind_param('i',$userId);
        $stmt->execute();
        $stmt->store_result();
        $num_rows=$stmt->num_rows;
        if($num_rows>0)
        {
            $stmt->bind_result($iditem,$preference,$name,$dir);
            $tmp=array();
            while($stmt->fetch())
                {
                    $aux=array();       
                    $aux["id"]=$iditem;
                    $aux["Name"]=$name;
                    $aux["preference"]=$preference; 
                    $aux["address"]=$dir;               
                    array_push($tmp, $aux);
                }
            $stmt->close();
            return $tmp;
        }
        else
        {

            $tmp=array();
            $stmt=$this->conn->prepare(
                "SELECT np.iditem, np.rating,i.nombre,i.direccion
                FROM sad_reja.reja_no_personalizado np, reja1526.item_table_full i 
                where i.idItem=np.iditem
                ORDER BY np.rating DESC ");

            if($stmt->execute())
            {    
                $stmt->bind_result($iditem,$rating,$name,$dir);   
                while($stmt->fetch())
                {
                    $aux=array();       
                    $aux["id"]=$iditem;
                    $aux["Name"]=$name;
                    $aux["preference"]=$rating;  
                    $aux["address"]=$dir;              
                    array_push($tmp, $aux);
                }            
            }

            $stmt->close();
            return $tmp;
        }
    }

    function getItemById($idItem,$idUser)
    {
        $item=array();
        $stmt=$this->conn->prepare(
            "SELECT nombre,telefono,direccion,terraza
            FROM reja1526.item_table_full 
            WHERE idItem=?");
        
        $stmt->bind_param('i',$idItem);
        $res=$stmt->execute();
        if($res)
        {
            $stmt->bind_result($name,$phoneNumber,$address,$terrace);
            while($stmt->fetch())
            {
                $item["id"]=$idItem;
                $item["Name"]=$name;                
                $item["Phone_Number"]=$phoneNumber;
                $item["addres"]=$address;
		if($terrace !=null && $terrace != 0)
	        	$item["terrace"]=$terrace;
		else
		{
			$item["terrace"]=0;
		}
            }

        }
        $stmt->close();
        $stmt=$this->conn->prepare(
            "SELECT rating
            FROM sad_reja.ratings
            WHERE iduser=? AND iditem=?");
        $stmt->bind_param('ii',$idUser,$idItem);
        $res=$stmt->execute();
        if($res)
        {
            $stmt->bind_result($rating);
            if($stmt->fetch()!=null)
                $item["rating"]=$rating;
            else 
                $item["rating"]=0;
        }
        $stmt->close();

        $stmt=$this->conn->prepare(
            "SELECT SUM(rating)/COUNT(*) AS Average
            FROM sad_reja.ratings
            WHERE  iditem=? ");
        $stmt->bind_param('i',$idItem);
        $res=$stmt->execute();

        if($res)
        {
            $stmt->bind_result($Average);
            if($stmt->fetch()!=null)
                $item["Average"]=$Average;
            else 
                $item["Average"]=0;
        }
        $stmt->close();
        //obtiene el tipo de cocina, 
        /*$stmt=$this->conn->prepare(
            "SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name='item_table_full' 
            AND column_name LIKE 'Cocina%'");
        $res=$stmt->execute();
        if($res)
        {
            $stmt->bind_result($type);
            $aux=array();

            for($i=0;$stmt->fetch();$i++)
            {
                $aux[$i]=$type;
            } 
        }
        $stmt->close();
        $length=count($aux);
        for($i=0;$i<$length;$i++)
        {
            $stmt=$this->conn->prepare(
            "SELECT ?
            FROM reja1526.item_table_full 
            WHERE idItem=?");

            $stmt->bind_param('si',$aux[$i],$idItem);
            $res=$stmt->execute();
            if($res)
            {
                $stmt->bind_result($type2);
                
                while($stmt->fetch())
                {
                    $item[$aux[$i]]=$type2;
                }
            }
            $stmt->close();
        }*/          
        
/*        //array_push($item, $aux);
	$aux=array();
	array_push($aux,$item);
	$item=$aux;*/
        return $item;
    }

    function getItemByName($name)
    {
        $stmt=$this->conn->prepare(
            "SELECT idItem,nombre,direccion
            FROM reja1526.item_table_full
            WHERE nombre LIKE ?");
        $search='%'.$name.'%';
        $stmt->bind_param('s',$search);
        $res=$stmt->execute();
        if($res)
        {
            
            $stmt->bind_result($idItem,$realName,$address);
            $item=array();
            while($stmt->fetch())
            {
                $aux=array();
                $aux["id"]=$idItem;
                $aux["Name"]=$realName;
                $aux["Address"]=$address;
                array_push($item, $aux);
            }
        }
        $stmt->close();
        return $item;
    }

    function setRating($userID,$itemID,$rating)
    {
          //exec('java -jar '.$this->path.'recommenderLib.jar -manageRatingDatabase '.$this->path.'mysqlRecommendationConfiguration.xml -addRating -idUser '.$userID.' -idItem '.$itemID.' -ratingValue '.$rating);
        $stmt=$this->conn->prepare(
            "REPLACE INTO sad_reja.ratings SET idUser=?,idItem=?,rating=?");
        $stmt->bind_param('iid',$userID,$itemID,$rating);
        $res=$stmt->execute();
        if($res)
        {
            $stmt->close();
            
            return 200;
        }
        else
        {
            $stmt->close();
            return 304;
        }
    }
    function createGroup($adminID,$groupID)
    {
        //echo $adminID."<br>".$groupID;

        $stmt=$this->conn->prepare(
            "INSERT INTO sad_reja.grupos (admin,id) values (?,?)");
        $stmt->bind_param('is',$adminID,$groupID);
        $res=$stmt->execute();

        if($res)
        {
            $stmt->close();
            $aux=array();
            $aux["status_code"]=201;
            $aux["message"]="Creado Correctamente";

        }
        else
        {
            $stmt->close();
            $aux=array();
            $aux["status_code"]= 500;
            $aux["message"]="Este grupo ya existe";
        }
        return $aux;

    }





}
?>
