<?php
include_once 'menu2.php';
include_once("connection.php");
include_once ('util.php');

function isNotRegistered($phoneNumber, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone= ?");
    $stmt->execute([$phoneNumber]); // Bind the parameter directly in execute
    $count = $stmt->fetchColumn(); // Fetch the count directly
    return $count == 0; 
}

// Check if 'from' and 'text' keys exist in the $_POST array
if(isset($_POST['from']) && isset($_POST['text'])) {
    $phoneNumber = $_POST['from'];
    $text = $_POST['text']; 

    $textArray = explode(" ", $text);

    if(count($textArray) >= 8) { // Check if there are enough elements in the array
        $name = $textArray[0];
        $phone = $textArray[1];
        $email = $textArray[2];
        $job = $textArray[3];
        $sex= $textArray[4];
        $certificate = $textArray[5];
        $username = $textArray[6];
        $password = $textArray[7];

        // Create PDO instance
        $dsn='mysql:host=localhost;dbname=jobapp';
        $user='root';
        $password='';
        try {
            $pdo = new PDO($dsn, $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if(isNotRegistered($phoneNumber, $pdo)) {
                $stmt = $pdo->prepare("INSERT INTO users (name, phone,email,job,sex,certificate,username,password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $phone, $email, $job, $sex, $certificate, $username, $password]);

                $result = $pdo->query("SELECT * FROM users WHERE name = '$name'");
                while($value = $result->fetch(PDO::FETCH_ASSOC)) {
                    echo "END Thank you {$value['name']}, you have been successfully registered!";
                }
            } else {
                echo "END User is already registered.";
            }
        } catch (PDOException $e) {
            echo "END Registration failed. Error: " . $e->getMessage();
        }
    } else {
        // If parameters are missing in the SMS, prompt the user to provide them
        echo "END Your SMS must contain all required information.";
    }
} else {
    // If 'from' or 'text' keys are missing in the $_POST array, prompt the user to provide them
    echo "END Please provide 'from' and 'text' parameters.";
}
?>
