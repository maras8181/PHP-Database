<!DOCTYPE html>
<html lang="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="">
</head>

<body>

     <?php
    
     class Database {
        private $localhost = "";
        private $username = "";
        private $password = "";
        private $db_name = "";
        public $conn = null;
        public function setConn() {
            $this->localhost = "localhost";
            $this->username = "root";
            $this->password = "";
            $this->db_name = "feo_database";
        }
        public function getConn(){
            $this->conn = new mysqli($this->localhost, $this->username, $this->password, $this->db_name);
            if ($this->conn->connect_error){
                die("Connection failed: " . $this->conn->connect_error);
            } else {
                return $this->conn;
            }
            
        }    
    }
    
    class Date {
        private $sql = "";
        private $result = null;
        public function getDate($connection){
            $this->sql = "SELECT created_at from transactions ORDER BY created_at DESC LIMIT 1";
            $this->result = $connection->query($this->sql);
            $this->row = $this->result->fetch_assoc();
            return date("Y-m-d", strtotime('-1 month', strtotime($this->row["created_at"])));
        }
    }
    
    class Users {
        private $sql = "";
        private $result = null;
        private $row = null;
        public $users = array();
        public $key = "";
        public function fetchData($date, $connection){
            $this->sql = "SELECT user_id, name, address, phone, email, password, SUM(price), currency FROM users JOIN transactions ON users.id=transactions.user_id WHERE transactions.created_at >= '$date' GROUP BY user_id, currency";
            $this->result = $connection->query($this->sql);
            if ($this->result->num_rows > 0){
                while ($this->row = $this->result->fetch_assoc()){
                    $key = $this->row["user_id"];
                    $user_data = ["name"=>$this->row["name"], "address"=>$this->row["address"], "phone"=>$this->row["phone"], "email"=>$this->row["email"], "password"=>$this->row["password"], "price_czk"=>"0,00", "price_eur"=>"0,00"];
                    if (!array_key_exists($key, $this->users)){
                        $this->users[$key] = $user_data;
                    }
                    $this->users[$key]["price_".$this->row["currency"]] = $this->row["SUM(price)"];
                }
            }
            return $this->users;
        }
    }
    
    class Csv {
        private $delimiter = "";
        private $filename = "";
        private $f = null;
        private $fields = [];
        private $csv_fields = [];
        private $id = 1;   
        public function save_to_csv($users){
            $this->delimiter = ";";
            $this->filename = "users.csv";
            $this->f = fopen($this->filename, "w");
            array_push($this->fields, "ID", "NAME", "ADDRESS", "PHONE", "EMAIL", "PASSWORD", "PRICE OF ALL TRANSACTIONS (CZK)", "PRICE OF ALL TRANSACTIONS (EUR)");
            fputcsv($this->f, $this->fields, $this->delimiter);
            foreach($users as $user){
                array_unshift($user, $this->id++);
                fputcsv($this->f, $user, $this->delimiter);
             }
        }
    }
    
    $database = new Database();
    $database->setConn();
    $connection = $database->getConn();
        
    $datum = new Date();        
    $usersinfo = new Users();
    $usersinfo = $usersinfo->fetchData($datum->getDate($connection), $connection);

    $csv_file = new Csv();
    $csv_file->save_to_csv($usersinfo);
    
?>

</body>
</html>
