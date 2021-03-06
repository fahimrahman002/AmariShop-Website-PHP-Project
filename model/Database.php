<?php
include_once "Bcrypt.php";
include_once __DIR__ . "/../lib/vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

define('HOST', $_ENV['DB_HOST']);
define('USER_NAME', $_ENV['DB_USERNAME']);
define('PASSWORD', $_ENV['DB_PASSWORD']);
define('DB_NAME', $_ENV['DB_NAME']);

// class DB start
class Database
{
    private $connection;
    private $user;

    // Constructor
    public function __construct()
    {
        $this->open_db_connection();
    }

    // Creating connection with db
    public function open_db_connection()
    {
        $this->connection = mysqli_connect(HOST, USER_NAME, PASSWORD, DB_NAME);

        if (mysqli_connect_error()) {
            die('Connection Error: ' . mysqli_connect_error());
        }
    }

    // Running SQL query on db
    public function query($sql)
    {
        $result = $this->connection->query($sql);
        $result_array = [];
        if (!$result) {
            die('Query fails : ' . $sql);
        }
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $result_array = $row;
            }
            return $result_array;
        } else {
            return $result_array;
        }
    }

    // Checking if string is in proper format
    public function escape_value($value)
    {
        $value = $this->connection->real_escape_string($value);
        return $value;
    }

    // Closing connection
    public function close_connection()
    {
        $this->connection->close();
    }
    //User Sign Up 
    public function create_user($email, $pass)
    {
        $bcrypt = new Bcrypt();
        $hashed_pass = $bcrypt->hashPassword($pass);
        $sql = "INSERT INTO `users`(`email`, `password`) VALUES ('$email','$hashed_pass')";
        $result = $this->connection->query($sql);
        return $result;
    }
    public function validate_user()
    {
        $email = &$_SESSION['email'];
        $pass = &$_SESSION['pass'];
        if ($email && $pass) {
            $res = $this->login($email, $pass);
            return $res;
        } else {
            return 0;
        }
    }
    public function login_with_google()
    {
        $google_client = new Google_Client();

        $google_client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    }
    public function login($email, $pass)
    {
        $bcrypt = new Bcrypt();
        $sql = "SELECT `email`, `password`,`user_id` FROM `users` WHERE `email`='$email'";
        $result = $this->query($sql);
        $res = 0; //0 for Invalid user
        if (sizeof($result) > 0) {
            if ($bcrypt->checkPassword($pass, $result['password'])) {
                $res = 1; //1 for valid user
                $_SESSION['email'] = $email;
                $_SESSION['pass'] = $pass;
                $_SESSION['user_id'] = $result['user_id'];
            } else {
                $res = -1; //-1 for Wrong password
            }
        } else {
            $res = 2; //2 for Account not created
        }
        return $res;
    }
    public function logout()
    {
        session_destroy();
    }

    public function get_user_data()
    {
        $bcrypt = new Bcrypt();
        $email = $_SESSION['email'];
        $pass = $_SESSION['pass'];
        $sql = "SELECT `user_id`,`full_name`, `phone`, `address`, `email`, `password` FROM `users` WHERE `email`='$email'";
        $result = $this->query($sql);
        $user_data = [];
        if (sizeof($result) > 0) {
            if ($bcrypt->checkPassword($pass, $result['password'])) {
                $user_data['user_id'] = $result['user_id'];
                $user_data['full_name'] = $result['full_name'];
                $user_data['phone'] = $result['phone'];
                $user_data['address'] = $result['address'];
                $user_data['email'] = $result['email'];
                return $user_data;
            }
        }
        return $user_data;
    }
    public function update_profile($name, $phone, $address)
    {
        $result = false;
        if ($this->validate_user() == 1) {
            $user_id = $_SESSION['user_id'];
            $sql = "UPDATE `users` SET `full_name`='" . $name . "',`phone`='" . $phone . "',`address`='" . $address . "' WHERE user_id=" . $user_id . "";
            $result = $this->connection->query($sql);
        }
        return $result;
    }

    public function post_contact_message($name, $email, $subject, $message)
    {
        $sql = "INSERT INTO `contact`(`name`, `email`, `subject`, `message`) VALUES ('" . $name . "','" . $email . "','" . $subject . "','" . $message . "')";
        $result = $this->connection->query($sql);
        return $result;
    }
    public function get_products()
    {
        $sql = "SELECT * FROM `product` ORDER BY created_at DESC";
        $product_list = [];
        $products = $this->connection->query($sql);
        while ($row = $products->fetch_assoc()) {
            array_push($product_list, $row);
        }

        return $product_list;
    }
    public function get_featured_products()
    {
        $sql = "SELECT * FROM product WHERE rating in (4,5) ORDER BY created_at DESC LIMIT 10;";
        $product_list = [];
        $products = $this->connection->query($sql);
        while ($row = $products->fetch_assoc()) {
            array_push($product_list, $row);
        }

        return $product_list;
    }
    public function get_latest_products()
    {
        $sql = "SELECT * FROM product ORDER BY created_at DESC LIMIT 10;";
        $product_list = [];
        $products = $this->connection->query($sql);
        while ($row = $products->fetch_assoc()) {
            array_push($product_list, $row);
        }

        return $product_list;
    }
    function get_product_details($product_id)
    {
        $sql = "SELECT p.product_id, p.name, p.description, p.img_url, p.model, p.brand, p.price, p.rating, p.product_discount, p.promo_code, p.created_at ,c.name as category_name FROM `product` p 
        inner join `category` c
        on p.category_id=c.category_id
        WHERE p.product_id=" . $product_id;

        $data = $this->query($sql);
        return $data;
    }

    function team_members()
    {
        $sql = "SELECT * FROM `our_team`";
        $team_member_list = [];
        $team = $this->connection->query($sql);
        while ($row = $team->fetch_assoc()) {
            array_push($team_member_list, $row);
        }
        return $team_member_list;
    }
    function get_testimonials(){
        $sql = "SELECT * FROM `testimonials`";
        $testimonial_list = [];
        $testimonials = $this->connection->query($sql);
        while ($row = $testimonials->fetch_assoc()) {
            array_push($testimonial_list, $row);
        }
        return $testimonial_list;
    }
    function get_advertisements(){
        $sql = "SELECT * FROM `advertise`";
        $advertisement_list = [];
        $advertisements = $this->connection->query($sql);
        while ($row = $advertisements->fetch_assoc()) {
            array_push($advertisement_list, $row);
        }
        return $advertisement_list;
    }
    function get_offer(){
        $sql="SELECT * FROM `offer`";
        return $this->query($sql);
    }
}

$database = new Database();
