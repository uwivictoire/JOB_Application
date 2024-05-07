<?php
include("connection.php");

class Menu {
    private $text;
    private $sessionId;
    private $currentStep;
    private $pdo; 

    public function isRegistered($phoneNumber){
        
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone = :phoneNumber");
      
        $stmt->bindParam(':phoneNumber', $phoneNumber);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result){
            return $result;  
        }else{
            return false; 
        }
    }
    
    public function __construct($text, $sessionId, $pdo) {
        $this->text = $text;
        $this->sessionId = $sessionId;
        $this->pdo = $pdo; 
        $this->currentStep = count(explode("*", $text));
    }
    public function mainMenuUnregistered() {
        $response = "CON Welcome to PRO Company Job Application \n";
        $response .= "1. Register\n";
        echo $response;
    }
    public function menuRegister($textArray,$phoneNumber) {
        $level = count($textArray);
    
        if ($level == 1) {
            echo "CON Enter your full name:\n";
        }  elseif ($level == 2) {
            echo "CON Enter your email address:\n";
        } elseif ($level == 3) {
            echo "CON Select your certificate level:\n";
            echo "1. PHD\n";
            echo "2. A0\n";
            echo "3. A1\n";
            echo "4. Cancel\n";
        } elseif ($level == 4) {
            if ($textArray[3] == 4) {
                echo "END Registration canceled.\n";
            } else {
                $certificateLevels = ["1" => "PHD", "2" => "A0", "3" => "A1"];
                $certificate = $certificateLevels[$textArray[3]];
    
                echo "CON Select your gender:\n";
                echo "1. Male\n";
                echo "2. Female\n";
            }
        } elseif ($level == 5) {
            $certificateLevels = ["1" => "PHD", "2" => "A0", "3" => "A1"];
            $certificate = $certificateLevels[$textArray[3]];
            $gender = ($textArray[4] == 1) ? "Male" : "Female";
    
            echo "CON Enter your password:\n";
        } elseif ($level == 6) {
            $certificateLevels = ["1" => "PHD", "2" => "A0", "3" => "A1"];
            $certificate = $certificateLevels[$textArray[3]];
            $gender = ($textArray[4] == 1) ? "Male" : "Female";
    
            $nameParts = explode(" ", $textArray[1]);
            $username = strtolower($nameParts[0]) . rand(100, 999);
    
            $hashedPassword = password_hash($textArray[5], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (name,phone, email, job, certificate, sex, username, password) VALUES (?, ?,?, ?, ?, ?, ?, ?)");
            $stmt->execute([$textArray[1],$phoneNumber, $textArray[2], 'Unknown', $certificate, $gender, $username, $hashedPassword]);
            $userId = $this->pdo->lastInsertId();
            $inser=$this->pdo->prepare("INSERT INTO `balance` (`b_id`, `st_id`, `amount`, `status`) VALUES (NULL, '$userId ', '10000', 'Active')");
            $inser->execute();
            echo "END Registration successful. Your username is: $textArray[1]";
        }
    }
    



    public function menuApplyForJob($textArray,$phoneNumber) {
        $userData = $this->isRegistered($phoneNumber);
    
        if ($userData) {
            $userId = $userData['ID'];
            $stmt = $this->pdo->prepare("SELECT * FROM jobs");
            $stmt->execute();
            $availableJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($availableJobs) {
                if (isset($textArray[1])) {
                    $jobIndex = (int)$textArray[1] - 1; 

                    if ($jobIndex >= 0 && $jobIndex < count($availableJobs)) {
                        $selectedJob = $availableJobs[$jobIndex];
                        $jobId = $selectedJob['id'];
                        $stmt = $this->pdo->prepare("UPDATE users SET job = ? WHERE ID = ?");
                        $stmt->execute([$selectedJob['job_title'], $userId]);

                        $sele=$this->pdo->prepare("SELECT * FROM applicant_details WHERE user_id='$userId' and job_id='$jobId'");
                        $sele->execute();
                        if($sele->rowCount()>0){
                            echo "END Already apply this jobe";
                        }else{
                           
                            $stmt = $this->pdo->prepare("INSERT INTO applicant_details (user_id, job_id, application_status) VALUES (?, ?, ?)");
                            $stmt->execute([$userId, $jobId, 'Pending']);
        
                            echo "END Application for " . $selectedJob['job_title'] . " at " . $selectedJob['company_name'] . " submitted successfully.\n";
                        }
                       
                    } else {
                        echo "END Invalid job selection. Please try again.\n";
                    }
                } else {
                    $response = "CON Available Jobs for Application:\n";
                    foreach ($availableJobs as $index => $job) {
                        $response .= ($index + 1) . ". " . $job['job_title'] . " at " . $job['company_name'] . "\n";
                    }
                    $response .= "Enter the number of the job you want to apply for:\n";
                    echo $response;
                }
            } else {
                echo "END No jobs available at the moment. Please try again later.\n";
            }
        } else {
            echo "END User not found. Please try again later.\n";
        }
    }
public function menuPay($textArray, $phoneNumber) {
    $level = count($textArray);

    if ($level == 1) {
        echo "CON Enter amount\n";
    } else if ($level == 2) {
        echo "CON Enter PIN\n";
    } else if ($level == 3) {
        $response = "CON Payment process can continue...\n";
        $response .= "1. Confirm\n";
        $response .= "2. Cancel\n";
        $response .= Util::$GO_BACK . " Back\n";
        $response .= Util::$GO_TO_MAIN_MENU .  " Main menu\n";
        echo $response;
    } else if ($level == 4 && $textArray[3] == 1) {
        $amount = $textArray[1];
        $pin = $textArray[2];
        $userData = $this->isRegistered($phoneNumber);
        if ($userData) {
            $userId = $userData['ID'];
            $passwordHash = $userData['password'];
            if (password_verify($pin, $passwordHash)) {
                $stmtBalance = $this->pdo->prepare("SELECT amount FROM balance WHERE st_id = ?");
                $stmtBalance->execute([$userId]);
                $balanceRow = $stmtBalance->fetch(PDO::FETCH_ASSOC);
                if ($balanceRow && $balanceRow['amount'] >= $amount) {
                    $stmtBalancee = $this->pdo->prepare("SELECT * FROM payments WHERE user_id = ?");
                    $stmtBalancee->execute([$userId]);
                    if($stmtBalancee->rowCount()>0){
                        echo "END Already paid before";
                    }else{
                        $stmtUpdateBalance = $this->pdo->prepare("UPDATE balance SET amount = amount - ? WHERE st_id = ?");
                        if ($stmtUpdateBalance->execute([$amount, $userId])) {

                            $stmtInsertPayment = $this->pdo->prepare("INSERT INTO payments (user_id, amount) VALUES (?, ?)");
                            if ($stmtInsertPayment->execute([$userId, $amount])) {
                                
                            echo "END Your payment of $amount RWF has been processed successfully.\n";
                                
                            } else {
                                echo "END Error inserting payment details.\n";
                            }
                        } else {
                            echo "END Error deducting balance.\n";
                        }
                    }
                } else {
                    echo "END Insufficient balance.\n";
                }
            } else {
                echo "END Incorrect PIN.\n";
            }
        } else {
            echo "END User not found.\n";
        }
    }else{
        echo "Invalid option";
    }
}

public function processInput($textArray,$phoneNumber) {
    $this->currentStep = count($textArray);
    if (!$this->isRegistered($phoneNumber)) {
        switch ($textArray[0]) {
            case 1:
                $this->menuRegister($textArray,$phoneNumber);
                break;
            case Util::$GO_BACK:

                $this->goBack($textArray,$phoneNumber);
                break;
            case Util::$GO_TO_MAIN_MENU:
                $this->goToMainMenu($textArray,$phoneNumber);
                break;
            default:

                echo "END Invalid option, please retry";
        }

    } else {
        switch ($textArray[0]) {
            case 1:
                $this->menuApplyForJob($textArray,$phoneNumber);
                break;
            case 2:
                $this->menuPay($textArray,$phoneNumber);
                break;
            case 3:
                $this->menuViewProfile($textArray,$phoneNumber);
                break;
            case 4:
                $this->menuTrackApplications($textArray,$phoneNumber);
                break;
            case 5:
                $this->menuViewApplicationsAvailable($textArray,$phoneNumber);
                break;
            case 6:
                $this->menuViewApplicationsPaymentHistory($textArray,$phoneNumber);
                break;
            case 7:
                $this->menuUpdatePassword($textArray,$phoneNumber);
                break;
            case Util::$GO_BACK:
                $this->goBack($textArray,$phoneNumber);
                break;
            case Util::$GO_TO_MAIN_MENU:

                $this->goToMainMenu($textArray,$phoneNumber);
                break;
            default:
                echo "END Invalid choice\n";
        }
    }
}
public function goBack($textArray,$phoneNumber) {

    $level = count($textArray);
    if ($level > 2) {
        array_pop($textArray);
        array_pop($textArray);
        $this->currentStep = count($textArray);
        $this->processInput($textArray,$phoneNumber);
    } else {
        echo "END There are not enough previous steps to go back to\n";
    }
}
public function goToMainMenu($textArray,$phoneNumber) {
    $response = "END Redirecting to Main Menu\n";
    echo $response;
    if ($this->isRegistered($phoneNumber)) {
        $this->mainMenuRegistered();
    } else {
        $this->mainMenuUnregistered();
    }
}
public function menuViewProfile($textArray,$phoneNumber) {
    
    $userData = $this->isRegistered($phoneNumber);

    if ($userData) {
        $response = "END Profile Details:\n";
        $response .= "Name: " . $userData['name'] . "\n";
        $response .= "Email: " . $userData['email'] . "\n";
        $response .= "Phone: " . $userData['phone'] . "\n";
        echo $response;
    } else {
        echo "END User not found. Please try again later.\n";
    }
}
public function menuTrackApplications($textArray,$phoneNumber) {
    $userData = $this->isRegistered($phoneNumber);

    if ($userData) {
        $userId = $userData['ID'];
        $stmt = $this->pdo->prepare("
            SELECT j.*,a.*, j.job_title, j.company_name, a.application_status 
            FROM applicant_details a
            INNER JOIN jobs j ON a.job_id = j.id
            WHERE a.user_id = ?
        ");
        $stmt->execute([$userId]);
        $applicationHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($applicationHistory) {
            $stmtBalancee = $this->pdo->prepare("SELECT * FROM payments WHERE user_id = ?");
            $stmtBalancee->execute([$userId]);
            if($stmtBalancee->rowCount()>0){
                $response = "END Application History:\n";
                foreach ($applicationHistory as $index => $application) {
                    $response .= ($index + 1) . ". Job Title: " . $application['job_title'] . "\n";
                    $response .= "   Company: " . $application['company_name'] . "\n";
                    $response .= "   Status: " . $application['application_status'] . "\n";
                }
                echo $response;
            }else{
                echo "CON Please pay the registration fees to  see the status of your application";
            }
        } else {
           
            echo "END No application history available.\n";
        }
        
    } else {
        echo "END User not found. Please try again later.\n";
    }
}
public function menuViewApplicationsAvailable($textArray,$phoneNumber) {
    
    $userData = $this->isRegistered($phoneNumber);

    if ($userData) {
        $userId = $userData['ID'];
        $stmt = $this->pdo->prepare("
            SELECT j.job_title, j.company_name 
            FROM jobs j
            LEFT JOIN applicant_details ad ON j.id = ad.job_id AND ad.user_id = ?
            WHERE ad.job_id IS NULL
        ");
        $stmt->execute([$userId]);
        $availableJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($availableJobs) {
            $response = "END Available Jobs for Application:\n";
            foreach ($availableJobs as $index => $job) {
                $response .= ($index + 1) . ". " . $job['job_title'] . " at " . $job['company_name'] . "\n";
            }
            echo $response;
        } else {
        
            echo "END No available jobs for application.\n";
        }
    } else {
        echo "END User not found. Please try again later.\n";
    }
}
public function menuUpdatePassword($textArray,$phoneNumber) {
   
    $userData =$this->isRegistered($phoneNumber);

    if ($userData) {
        $userId = $userData['ID'];
        $hashedPasswordFromDB = $userData['password'];
        
        if (count($textArray) == 1) {
          
            echo "CON Enter old password:\n";
        } elseif (count($textArray) == 2) {
            $enteredOldPassword = $textArray[1];
            if (!password_verify($enteredOldPassword, $hashedPasswordFromDB)) {
                echo "END Incorrect old password. Please retry.\n";
            } else {
    
                echo "CON Enter new password:\n";
            }
        } elseif (count($textArray) == 3) {
        
            echo "CON Confirm new password:\n";
        } elseif (count($textArray) == 4) {
            $newPassword = $textArray[2];
            $confirmedPassword = $textArray[3];

            if ($newPassword !== $confirmedPassword) {
        
                echo "END Passwords do not match. Please retry.\n";
            } else {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("UPDATE users SET Password = ? WHERE ID = ?");
                $stmt->execute([$hashedNewPassword, $userId]);
                echo "END Password updated successfully.\n";
            }
        } else {
            echo "END Unexpected error. Please retry.\n";
        }
    } else {
        echo "END User not found. Please try again later.\n";
    }
}

public function menuViewApplicationsPaymentHistory($textArray,$phoneNumber) {
    
    $userData = $this->isRegistered($phoneNumber);

    if ($userData) {
        $userId = $userData['ID'];
        $stmt = $this->pdo->prepare("
            SELECT p.payment_id, p.amount, p.payment_date, j.job_title, j.company_name 
            FROM payments p 
            LEFT JOIN jobs j ON p.payment_id = j.id
             WHERE p.user_id = ?
        ");
        $stmt->execute([$userId]);
        $paymentHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($paymentHistory) {
            $response = "END Payment History:\n";
            foreach ($paymentHistory as $index => $payment) {
                $response .= "Payment ID: " . $payment['payment_id'] . "\n";
                $response .= "Job Title: " . $payment['job_title'] . "\n";
                $response .= "Company: " . $payment['company_name'] . "\n";
                $response .= "Amount: " . $payment['amount'] . "\n";
                $response .= "Date: " . $payment['payment_date'] . "\n";
                $response .= "---------------------\n";
            }
            echo $response;
        } else {
            echo "END No payment history available for this user.\n";
        }
    } else {
        echo "END Error: User ID not found.\n";
    }
}

public function middleware($text){
    return $this->goBack2($this->goToMainMenu2($text));
}

public function goBack2($text){
    $explodedText = explode("*",$text);
    while(array_search(Util::$GO_BACK, $explodedText) != false){
        $firstIndex = array_search(Util::$GO_BACK, $explodedText);
        array_splice($explodedText, $firstIndex-1, 2);
    }
    return join("*", $explodedText);
}

public function goToMainMenu2($text){
    $explodedText = explode("*",$text);
    while(array_search(Util::$GO_TO_MAIN_MENU, $explodedText) != false){
        $firstIndex = array_search(Util::$GO_TO_MAIN_MENU, $explodedText);
        $explodedText = array_slice($explodedText, $firstIndex + 1);
    }
    return join("*",$explodedText);
}
    public function mainMenuRegistered() {
        echo "CON Welcome To YYY Application Portal:\n";
        echo "1. Apply For Job\n";
        echo "2. Pay Application\n";
        echo "3. View Profile\n";
        echo "4. Track Applications\n";
        echo "5. View Available Applications\n";
        echo "6. View Applications Payment History\n";
        echo "7. Update Password\n";
    }
}


?>
