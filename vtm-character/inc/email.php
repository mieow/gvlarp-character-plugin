<?php

add_action( 'phpmailer_init', 'vtm_phpmailer_setup' );
function vtm_phpmailer_setup( $phpmailer ) {
	
	$method   = get_option( 'vtm_method', 'mail' );
	$fromname = get_option( 'vtm_replyto_name', "Website");
	$replyto  = get_option( 'vtm_replyto_address', get_option( 'vtm_chargen_email_from_address', get_bloginfo('admin_email') ) );

	if ($method == 'mail') {
		$phpmailer->isMail();   
		$phpmailer->SetFrom($replyto, $fromname);
	}
	elseif ($method == 'smtp') {
		$smtphost   = get_option( 'vtm_smtp_host',     '' );
		$smtpport   = get_option( 'vtm_smtp_port',     '25' );
		$smtpuser   = get_option( 'vtm_smtp_username', '' );
		$smtpauth   = get_option( 'vtm_smtp_auth',     'true' );
		$smtpsecure = get_option( 'vtm_smtp_secure',   'ssl' );
		$smtppw     = get_option( 'vtm_smtp_pw',       '' );
		
		$smtpauth = $smtpauth == 'true' ? true : false;
		
		/*echo "<li>smtphost $smtphost</li>";
		echo "<li>smtpport $smtpport</li>";
		echo "<li>smtpuser $smtpuser</li>";
		echo "<li>smtpauth $smtpauth</li>";
		echo "<li>smtpsecure $smtpsecure</li>";
		echo "<li>smtppw $smtppw</li>"; */

		$phpmailer->isSMTP();     
		$phpmailer->Host = $smtphost;
		$phpmailer->SMTPAuth = $smtpauth; // Force it to use Username and Password to authenticate
		$phpmailer->Port = $smtpport;
		$phpmailer->Username = $smtpuser;
		$phpmailer->Password = $smtppw;
		$phpmailer->SMTPSecure = $smtpsecure;                 // sets the prefix to the server
		
		$phpmailer->SetFrom($smtpuser, $fromname);

		}
	else {
		echo "<p>Unknown mail transport method '$method'</p>";
	}
	

}
function vtm_test_email($email) {
	
	$tag      = get_option( 'vtm_emailtag' );
	$fromname = get_option( 'vtm_replyto_name', "Website");
	$replyto  = get_option( 'vtm_replyto_address', get_option( 'vtm_chargen_email_from_address', get_bloginfo('admin_email') ) );
		
	$subject = "$tag Test Email";
	//$headers[] = "From: \"$fromname\" <$replyto>";
	$headers[] = "Reply-To: \"$fromname\" <$replyto>";
	
	$body = "This is a test email\n";
	
	$result = wp_mail($email, $subject, $body, $headers);
	
	if ($result)
		echo "<p style='color:green;'>Email sent to $email</p>";
	else
		echo "<p style='color:red;'>Failed to send email to $email</p>\n";
	
}

?>