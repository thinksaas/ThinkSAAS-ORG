<?php 
defined('IN_TS') or die('Access Denied.');



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


 
class mail extends tsApp{
	
	//构造函数
	public function __construct($db){
        $tsAppDb = array();
		include 'app/mail/config.php';
		//判断APP是否采用独立数据库
		if($tsAppDb){
			$db = new MySql($tsAppDb);
		}
	
		parent::__construct($db);
	}
	
	//发送邮件
	function postMail($sendmail,$subject,$content){
	
		global $TS_SITE,$tsMySqlCache;
	
		$options = fileRead('data/mail_options.php');
		if($options==''){
			$options = $tsMySqlCache->get('mail_options');
		}



        $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
        try {
            //Server settings
            $mail->SMTPDebug = 0;                                 // Enable verbose debug output
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = $options['mailhost'];  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = $options['mailuser'];                 // SMTP username
            $mail->Password = $options['mailpwd'];                           // SMTP password


            if($options['ssl']){
                $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
            }


            $mail->Port = $options['mailport'];                                    // TCP port to connect to

            //Recipients
            $mail->setFrom($options['mailuser'], $TS_SITE['site_title']);
            $mail->addAddress($sendmail, '');     // Add a recipient
            //$mail->addAddress($sendmail);               // Name is optional
            $mail->addReplyTo($options['mailuser'], $TS_SITE['site_title']);

            /*
            $mail->addCC('cc@example.com');
            $mail->addBCC('bcc@example.com');
            */

            //Attachments
            /*
            $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
            */

            //Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $content;
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            //echo 'Message has been sent';

            return 1;

        } catch (Exception $e) {
            //echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;

            return 0;
        }




		//date_default_timezone_set('Asia/Shanghai');

		/*
		require_once 'PHPMailer/PHPMailerAutoload.php';
		$mail = new PHPMailer();

		//邮件配置
		$mail->CharSet = "UTF-8";
		$mail->IsSMTP(); 
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug  	= 0;
		
		$mail->Debugoutput = 'html';
		
		if($options['ssl']){
			$mail->SMTPSecure = 'ssl';
		}
		$mail->SMTPAuth   	= true;
		
		$mail->Host       		= $options['mailhost']; 
		$mail->Port       		= $options['mailport']; 
		$mail->Username   	= $options['mailuser']; 
		$mail->Password   	= $options['mailpwd']; 

		//POST过来的信息
		$frommail		= $options['mailuser'];
		$fromname	= $TS_SITE['site_title'];
		$replymail		= $options['mailuser'];
		$replyname	= $TS_SITE['site_title'];
		$sendname	= '';

		if(empty($frommail) || empty($subject) || empty($content) || empty($sendmail)){
			return '0';
		}else{

			//邮件发送
			$mail->SetFrom($frommail, $fromname);
			$mail->AddReplyTo($replymail,$replyname);
			$mail->Subject    = $subject;
			$mail->AltBody    = "要查看邮件，请使用HTML兼容的电子邮件阅读器!"; 
			//$mail->MsgHTML(eregi_replace("[\]",'',$content));
			$mail->MsgHTML(strtr($content,'[\]',''));
			$mail->AddAddress($sendmail, $sendname);
			$mail->send();
			
			return '1';
			
		}
		*/









	}
	
	
	//析构函数
	public function __destruct(){
		
	}

}