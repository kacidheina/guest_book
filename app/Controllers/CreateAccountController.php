<?php
/**
 * Created by PhpStorm.
 * User: ina
 * Date: 5/7/19
 * Time: 2:00 PM
 */


require_once (__DIR__.'/../../app/Entity/User.php');
require_once (__DIR__.'/../../app/Repository/UserRepository.php');
require_once (__DIR__.'/../../tools/Sanitaze.php');
require_once (__DIR__.'/../../tools/RenderView.php');


class CreateAccountController extends User
{

    public function showCreateAccount($foundErrors = [],$msg = ""){
        RenderView::render_php('create_account.php',array('foundErrors' => $foundErrors,"msg" => $msg));
    }

    public function redirectTo($url){
        header("Location: ".$url);
        die();
    }

    public function createUser()
    {
        //make some input data validation
        $foundErrors = $this->validateDatas();
        if (count($foundErrors)> 0){
            $this->showCreateAccount($foundErrors);
        }
        //check if user already exist
        $userRepository = new UserRepository();
        $checkIfUserExist =$userRepository->findOneByEmail(Sanitaze::sanitazeInput($_POST['email']));

        if (count($checkIfUserExist) > 0){
            $foundErrors[]=["error" => "This user already exist"];
            $this->redirectTo("auth/register");
        }else{
            $newUser = new User();
            $newUser->setName(Sanitaze::sanitazeInput($_POST['name']));
            $newUser->setSurname(Sanitaze::sanitazeInput($_POST['surname']));
            $newUser->setEmail(Sanitaze::sanitazeInput($_POST['email']));
            $newUser->setPassword(password_hash(Sanitaze::sanitazeInput($_POST['password']), PASSWORD_ARGON2I, Sanitaze::getHashOptions()));
            $newUser->setCreatedAt(date('Y-m-d H:i:s'));
            if ($newUser->save($newUser) == null){
                RenderView::render_php('login.php',array('msg' => "User inserted successfully"));
            }else{
                $this->showCreateAccount(["error" => "Insert failed"]);
            }
        }

    }

    public function validateDatas()
    {

        $errors = [];
        $nameClean = filter_var($_POST["name"], FILTER_SANITIZE_STRING);
        if (strlen($nameClean) < 2) {
            $errors[] = ["field" => "name", "error" => "Name is too short."];
        } elseif (!preg_match("/^[a-zA-Z ]*$/", $nameClean)) {
            $errors[] = ["field" => "name", "error" => "Name is not valid. Only letters and white space allowed"];
        }

        $surnameClean = filter_var($_POST["surname"], FILTER_SANITIZE_STRING);
        if (strlen($surnameClean) < 2) {
            $errors[] = ["field" => "surname", "error" => "Surname is too short."];
        } elseif (!preg_match("/^[a-zA-Z'-]+$/", $surnameClean)) {
            $errors[] = ["field" => "surname", "error" => "Surname is not valid."];
        }

        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)){
            $errors[] = ["field" => "email", "error" => "Invalid email format."];
        }elseif (strlen($_POST["email"]) < 2) {
            $errors[] = ["field" => "email", "error" => "Email is required."];
        }
        $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
        $passwordValidationErrors = $this->validatePassword($password);
        if (count($passwordValidationErrors) > 0) {
            $errors[] = ["field" => "password", "error" => implode(" ", $passwordValidationErrors)];
        }

        return $errors;
    }


}
