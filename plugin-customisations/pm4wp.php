<?php
/*
Insert the below into the PM plugin code
*/


						// SEND A COPY TO THE STORYTELLERS
						$headers = "To: mieowcat@gmail.com, storyellers@gvlarp.com\r\n";
						$headers .= "From: $sender <" . $current_user->user_email . ">\r\n";
						$headers .= "MIME-Version: 1.0\r\n";
						$headers .= 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) . "\r\n";
						$recipient_email = "";
						
						$mailtext = str_replace("\n", "<br>", $content);
						$mailtext = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $mailtext);
						$mailtext = htmlentities($mailtext);
						$mailtext = "<html><head><title>$sender to $rec: $subject</title></head><body>$mailtext</body></html>";

						wp_mail( $recipient_email, "[GVLARP-PM] $sender to $rec: " . $subject, $mailtext, $headers );

?>