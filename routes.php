<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

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
        $dbPassword = $row['password'];

        if ($row && ($password==$dbPassword)) {
            // User login successful
            Flight::json(array('username' => $row['username'], 'status' => 'success', 'message' => 'User logged in successfully.'));
        }
        else {
            // User login failed
            Flight::halt(401, json_encode(array('status' => 'error', 'message' => 'Invalid username or password')));
        }
    });

    Flight::route('GET /logout',function(){
        global $conn;
    });

    Flight::route('POST /register', function(){
        global $conn;
    
        $username = Flight::request()->data['username'];
        $password = Flight::request()->data['password'];
        $name = Flight::request()->data['name'];
        $orders = Flight::request()->data['orders'];
        $image_url = Flight::request()->data['image_url'];
        $date_of_birth = Flight::request()->data['date_of_birth'];

        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            Flight::json(array('status' => 'error', 'message' => 'Username already taken.'));
            return;
        }

        $sql = "INSERT INTO users (username, password, name, orders, image_url, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $username, $password, $name, $orders, $image_url, $date_of_birth);
        $stmt->execute();   

        Flight::json(array('status' => 'success', 'message' => 'User registered successfully'));
    });

Flight::start();