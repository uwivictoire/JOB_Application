<?php
include("connection.php");
include_once 'menu2.php';
include_once 'util.php';
$sessionId = $_POST['sessionId'];
$phoneNumber = $_POST['phoneNumber'];
$serviceCode = $_POST['serviceCode'];
$text = $_POST['text'];
$menu = new Menu($text, $sessionId, $pdo);
$text = $menu->middleware($text);
$result=$menu->isRegistered($phoneNumber);
if($result){
    $status=true;
}else{
    $status=false;
}
$isRegistered=$status;

if($text == "" && !$isRegistered){
    $menu -> mainMenuUnregistered();
}
else if($text == "" && $isRegistered){
    $menu -> mainMenuRegistered();
 
}
else if(!$isRegistered){
    $textArray = explode("*", $text);
    switch($textArray[0]){
        case 1:
            $menu->menuRegister($textArray,$phoneNumber);
            break;
        default:
            echo "END Invalid option, retry";
    }
} else {
  
    $textArray = explode("*", $text);

   
    if ($isRegistered) {
        switch ($textArray[0]) {
            case 1:
           
                $menu->menuApplyForJob($textArray,$phoneNumber);
                break;
            case 2:
               
                $menu->menuPay($textArray,$phoneNumber);
                break;
            case 3:
               
                $menu->menuViewProfile($textArray,$phoneNumber);
                break;
            case 4:
             
                $menu->menuTrackApplications($textArray,$phoneNumber);
                break;
            case 5:
               
                $menu->menuViewApplicationsAvailable($textArray,$phoneNumber);
                break;
            case 6:
        
                $menu->menuViewApplicationsPaymentHistory($textArray,$phoneNumber);
                break;
            case 7:
             
                $menu->menuUpdatePassword($textArray,$phoneNumber);
                break;
            default:
             
                echo "END Invalid choice\n";
        }
    }
}
?>
