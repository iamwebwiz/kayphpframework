<?php

namespace App\Controllers;

use App\Systems\Request;
use App\Controllers\Controller;
use App\Systems\Hash;
use App\Systems\Migration;
use App\Systems\Session;
use App\Systems\Logger;
use App\Systems\Mail;
use App\Systems\Redis;
use App\Models\User;
use Exception;
use Kaythinks\Goodthoughts;
use App\Systems\Queues\MailQueueClient;

class HomeController extends Controller{

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * This method is for the welcome page view
	 * 
	 * @return Response
	 */
	public function index(){
		
		$quote = Goodthoughts::generateThought();
		echo $this->twig->render('welcome.php', ["quote" => $quote ]);
	}

	/**
	 * This method is for the register page view
	 * 
	 * @return Response
	 */
	public function register(){

		if (Session::exists('error')) {

			$error = Session::get('error');
			//Unset the session
			Session::destroy('error');
		}else{
			$error = false;
		}
		
		echo $this->twig->render('register.php', ['error' => $error ]);
	}

	/**
	 *  This method is for the login page view
	 * 
	 * @return Response
	 */
	public function login(){

		if (Session::exists('error')) {

			$error = Session::get('error');
			//Unset the session
			Session::destroy('error');
		}else{
			$error = false;
		}

		echo $this->twig->render('login.php', ['error' => $error ]);
	}

	/**
	 *  This method is for the forgot password page view
	 * 
	 * @return Response
	 */
	public function forgotPassword(){

		if (!Session::exists('error')) $error = false;
		
		if (Session::exists('error')) {
			$error = Session::get('error');
			Session::destroy('error');
		}

		if (!Session::exists('info')) $info = false;

		if (Session::exists('info')){
			$info = Session::get('info');
			Session::destroy('info');
		}

		echo $this->twig->render('forgotpassword.php', ['error' => $error , 'info' => $info ]);
	}

	/**
	 * This method is for recovering forgotten passwords
	 * 
	 * @param  Request $request 
	 * @return Response
	 */
	public function postForgotPassword(Request $request)
	{
		$data = User::where('email',$request->get('email'));
		
		if (!empty($data)){

			//Generate a new password for the user and send an email
			$newPassword = base64_encode(random_bytes(4));

			$recipientEmail = $data['email'];
			$recipientFullName = $data['username'];
			$subject = "This is your new Password";

			$body = $this->twig->render('email/forgotpassword.php', ['newpassword' => $newPassword, 'data' => $data]);

			Mail::sendEmail($recipientEmail, $recipientFullName, $subject, $body);
			
			//Push the Data into the Request object
			$request->push($data);

			$request->put('password',Hash::make($newPassword));

			User::update($request);

			Session::put('info','Email Successfully Sent !');

			return $this->redirect('/forgotpassword');	

		}else{
			Session::put("error","Email Does'nt exist in our Database !");

			return $this->redirect('/forgotpassword');
		}
	}

	/**
	 * This method is an API End point
	 * 
	 * @throws \Exception 
	 * @return JSON Response
	 */
	public function getResponse(){

		try {
			
			enable_cors();
			http_response_code(200);

			$data = [
				success => "Successfully Touched this API Endpoint",
				random_quote => Goodthoughts::generateThought()
			];

			echo json_encode($data);
		} catch (\Exception $e) {
			
			Logger::error($e);
			echo json_encode($e->getMessage());
		}
	}

	/**
	 * This method is for the docs page view
	 * 
	 * @return Response
	 */
	public function docs()
	{
		echo $this->twig->render('docs.php');
	}

	/**
	 * This method is for sending email 
	 * 
	 * @return Response
	 */
	public function sendMail()
	{
		$recipientEmail = "kaythinks@gmail.com";
		$recipientFullName = "Kay Odole";
		$subject = "This is a KayPHP Demo Email";
		$body = file_get_contents('app/views/email/demo.php');

		Mail::sendEmail($recipientEmail, $recipientFullName, $subject, $body);

		return $this->redirect('/');
	}

	/**
	 * This method is for queueing email messages
	 * 
	 * @param  Request $request 
	 * @return Response
	 */
	public function queueEmails(Request $request)
	{
		$email = $request->get('email');

		for ($i=0; $i < 10; $i++) { 

			MailQueueClient::attach($email);
		}
		
		echo "done";
	}

	/**
	 * This method is for getting a value in the Redis Database
	 * 
	 * @return Response
	 */
	public function getRedisValue()
	{
		$value = (new Redis())->getValue('KayPHP');

		echo $value;
	}

	/**
	 * This method is for setting a value in the Redis Database
	 *
	 * @return Response
	 */
	public function setRedisValue()
	{
		$value = (new Redis())->setValue('Kay', 'Freaking Genius');
		
		if ($value) return "done";
	}
}