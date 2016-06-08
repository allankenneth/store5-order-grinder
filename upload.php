<?php
define("UPLOAD_DIR", "/home/s5/html/wp-content/plugins/store5/files/");
if (!empty($_FILES["myFile"])) {
    $myFile = $_FILES["myFile"];
    if ($myFile["error"] !== UPLOAD_ERR_OK) {
        echo "<p>A fucking error occurred.</p>";
        exit;
    }
	// We're going to delete the existing transaction journal and 
	//replace it with the one we just uploaded
	unlink('files/tj.csv');
	$name = 'tj.csv';
	$success = move_uploaded_file($myFile["tmp_name"], UPLOAD_DIR . $name);
	if (!$success) { 
		echo "<p>Unable to save file.</p>";
		exit;
	} else {
		$goto = "Location: /wp-admin/admin.php?page=store5";
		header($goto);
	}
	chmod(UPLOAD_DIR . $name, 0644);
}
