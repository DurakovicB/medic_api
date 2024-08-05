<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
//include('Config.class.php');

//$conn = new mysqli($servername, $mysql_username, $mysql_password, $dbname);
$uri = "mysql://doadmin:AVNS_OhWgXwUUN5lOe1rTkod@dbmedic-do-user-17420875-0.i.db.ondigitalocean.com:25060/defaultdb?ssl-mode=REQUIRED";

$fields = parse_url($uri);

// build the DSN including SSL settings
$conn = "mysql:";
$conn .= "host=" . $fields["host"];
$conn .= ";port=" . $fields["port"];;
$conn .= ";dbname=defaultdb";
$conn .= ";sslmode=verify-ca;sslrootcert=ca.pem";

try {
  $db = new PDO($conn, $fields["user"], $fields["pass"]);

} catch (Exception $e) {
  echo "Error: " . $e->getMessage();
}

Flight::route('/', function(){
    print("Hello!");

});

Flight::route('GET /users', function(){
    global $db;

    $sql = "SELECT * FROM users";
    $stmt = $db->query($sql);

    if ($stmt) {
        // User data found
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Flight::json($users);
    } else {
        // No user data found
        Flight::json(array('message' => 'No user data found.'));
    }
});
Flight::route('GET /users/details/@id', function($id) {
    global $db;

    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute(['id' => $id]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        Flight::json($result);
    } else {
        Flight::json(["message" => "User not found!"]);
    }
});

Flight::route('PUT /users/update/@id', function($id) {
    global $db;

    $data = Flight::request()->data->getData();
    $sql = "UPDATE users SET name = :name, username = :username, orders = :orders, last_login_date = :last_login_date, image = :image, status = :status, date_of_birth = :date_of_birth WHERE id = :id";
    $stmt = $db->prepare($sql);

    $stmt->execute([
        'name' => $data['name'],
        'username' => $data['username'],
        'orders' => $data['orders'],
        'last_login_date' => $data['last_login_date'],
        'image' => $data['image'],
        'status' => $data['status'],
        'date_of_birth' => $data['date_of_birth'],
        'id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        Flight::json(["message" => "Updated user with id " . $id]);
    } else {
        Flight::json(["message" => "No user found with id " . $id]);
    }
});


Flight::route('POST /users/block/@id', function($id) {
    global $db;

    $sql = "UPDATE users SET status = 'blocked' WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() > 0) {
        Flight::json(["message" => "Blocked user with id " . $id]);
    } else {
        Flight::json(["message" => "Found no active user with id " . $id]);
    }
});

Flight::route('POST /login', function(){
    global $db;

    $username = Flight::request()->data['username'];
    $password = Flight::request()->data['password'];

    $sql = "SELECT * FROM admin_info WHERE username = :username";
    $stmt = $db->prepare($sql);
    $stmt->execute(['username' => $username]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        Flight::halt(401, json_encode(['status' => 'error', 'message' => 'Invalid username!']));
    }

    $dbPassword = $row['password'];

    if ($password == $dbPassword) {
        // User login successful
        $payload = [
            'iss' => 'e.com',
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => $row['username']
        ];

        $jwt = JWT::encode($payload, "ezcb9s", 'HS256');
        $sql = "UPDATE admin_info SET jwt = :jwt WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->execute(['jwt' => $jwt, 'username' => $username]);
        Flight::json(['username' => $row['username'], 'status' => 'success', 'message' => 'User logged in successfully.', 'token' => $jwt]);
    } else {
        // User login failed
        Flight::halt(401, json_encode(['status' => 'error', 'message' => 'Invalid password!']));
    }
});

Flight::route('POST /logout', function(){
    global $db;

    $username = Flight::request()->data["username"];

    $sql = "UPDATE admin_info SET jwt = '' WHERE username = :username";
    $stmt = $db->prepare($sql);
    $stmt->execute(['username' => $username]);

    if ($stmt->rowCount() > 0) {
        Flight::json(['status' => 'success', 'message' => 'User logged out successfully.']);
    } else {
        $debug_sql = "UPDATE admin_info SET jwt = '' WHERE username = '$username'";
        Flight::halt(401, json_encode([
            'status' => 'error',
            'message' => 'Logout failed!',
            'debug_sql' => $debug_sql
        ]));
    }
});

Flight::route('POST /register', function(){
    global $db;

    $username = Flight::request()->data['username'];
    $name = Flight::request()->data['name'];
    $orders = Flight::request()->data['orders'];
    $image = Flight::request()->data['image'];
    $status = Flight::request()->data['status'];
    $date_of_birth = Flight::request()->data['date_of_birth'];
    $last_login_date = date('Y-m-d H:i:s');

    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $db->prepare($sql);
    $stmt->execute(['username' => $username]);

    if ($stmt->rowCount() > 0) {
        Flight::halt(401, json_encode(['status' => 'error', 'message' => 'Username already taken.']));
        return;
    }

    $sql = "INSERT INTO users (username, name, orders, image, status, date_of_birth, last_login_date) VALUES (:username, :name, :orders, :image, :status, :date_of_birth, :last_login_date)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'username' => $username,
        'name' => $name,
        'orders' => $orders,
        'image' => $image,
        'status' => $status,
        'date_of_birth' => $date_of_birth,
        'last_login_date' => $last_login_date
    ]);

    Flight::json(['status' => 'success', 'message' => 'User registered successfully']);
});

Flight::start();