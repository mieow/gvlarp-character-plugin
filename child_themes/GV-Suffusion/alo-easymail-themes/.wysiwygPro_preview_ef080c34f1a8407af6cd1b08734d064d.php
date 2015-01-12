<?php
if ($_GET['randomId'] != "vt14JJRuKNmd9K8Y9BbsLqTXVa4orn0ltyNdX2bHEaqg1vnLHpXnUWeynB6XMFsn") {
    echo "Access Denied";
    exit();
}

// display the HTML code:
echo stripslashes($_POST['wproPreviewHTML']);

?>  
