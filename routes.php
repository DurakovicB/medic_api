<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
include('Config.class.php');

//$conn = new mysqli($servername, $mysql_username, $mysql_password, $dbname);
$conn = new mysqli(Config::DB_HOST(), Config::DB_USERNAME(), Config::DB_PASSWORD(), Config::DB_SCHEME());

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

Flight::route('/', function(){
    print("Hello!");
    $response = Flight::request()->post('/users/block/1', $data);

    echo 'Response: ' . $response->data;
});

Flight::route('GET /users', function(){
    global $conn;

    $sql = "SELECT * FROM users";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // User data found
        $users = array();
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        Flight::json($users);
    } else {
        // No user data found
        Flight::json(array('message' => 'No user data found.'));
    }
});

Flight::route('GET /users/details/@id', function($id) {
    global $conn;

        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $result=$result->fetch_assoc();
            Flight::json($result);
        }
        else
            Flight::json(["message" =>"User not found!"]);
});

Flight::route('PUT /users/update/@id', function($id) {
    global $conn;

    $data = Flight::request()->data->getData();
    $name = $data['name'];
    $username = $data['username'];
    $orders = $data['orders'];
    $image = $data['image'];
    $status = $data['status'];
    $date_of_birth = $data['date_of_birth'];
    $last_login_date = $data['last_login_date'];

    $sql = "UPDATE users SET name = ?, username = ?, orders = ?, last_login_date = ?, image = ?, status = ?, date_of_birth = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssissssi', $name, $username, $orders, $last_login_date, $image, $status, $date_of_birth, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        Flight::json(["message" => "Updated user with id " . $id]);
    } else {
        Flight::json(["message" => "No user found with id " . $id]);
    }
});


Flight::route('POST /users/block/@id', function($id) {
    global $conn;

        $sql = "UPDATE users SET status = 'blocked'  WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($stmt->affected_rows>0) {
            Flight::json(["message" => "Blocked user with id " . $id]);
        }
        else{
            Flight::json(["message" => "Found no active user with id " . $id]);
        }
    });

    Flight::route('POST /login', function(){
        global $conn;
    
        // Retrieve user inputs from the request
        $username = Flight::request()->data['username'];
        $password = Flight::request()->data['password'];
    
        // Retrieve the user data from the database
        $sql = "SELECT * FROM admin_info WHERE username = ? ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc(); 
        if(!$row){
            Flight::halt(401, json_encode(array('status' => 'error', 'message' => 'Invalid username!')));
        }
        $dbPassword = $row['password'];

        if ($password==$dbPassword) {
            // User login successful
            $row['rand'] = rand(100000, 999999);
            $jwt = JWT::encode($row, Config::JWT_SECRET(), 'HS256');
            $sql = "UPDATE admin_info SET jwt= ? WHERE username = ? ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $jwt, $username);
            $stmt->execute();

            Flight::json(array('username' => $row['username'], 'status' => 'success', 'message' => 'User logged in successfully.','token' => $jwt));
        }
        else {
            // User login failed
            Flight::halt(401, json_encode(array('status' => 'error', 'message' => 'Invalid password!')));
        }
    });

    Flight::route('POST /logout',function(){
        global $conn;
        $username = Flight::request()->data["username"];

        $sql = "UPDATE admin_info SET jwt= '' WHERE username =  ? ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            Flight::json(array('status' => 'success', 'message' => 'User logged out successfully.'));
        }
        else{ 
            $debug_sql = "UPDATE admin_info SET jwt= '' WHERE username = '$username'";
            Flight::halt(401, json_encode(array(
                'status' => 'error',
                'message' => 'Logout failed!',
                'debug_sql' => $debug_sql
            )));}
    });

    Flight::route('POST /register', function(){
        global $conn;
    
        $username = Flight::request()->data['username'];
        $name = Flight::request()->data['name'];
        $orders = Flight::request()->data['orders'];
        $image = Flight::request()->data['image'];
        $status = Flight::request()->data['status'];
        $date_of_birth = Flight::request()->data['date_of_birth'];
        $last_login_date = date('Y-m-d H:i:s');

        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            Flight::halt(401,json_encode(array('status' => 'error', 'message' => 'Username already taken.')));
            return;
        }

        $sql = "INSERT INTO users (username, name, orders, image, status, date_of_birth, last_login_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssss', $username, $name, $orders, $image, $status, $date_of_birth, $last_login_date);
        $stmt->execute();   

        Flight::json(array('status' => 'success', 'message' => 'User registered successfully'));
    });

Flight::start();