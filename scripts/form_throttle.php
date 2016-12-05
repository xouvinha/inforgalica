<?php 
/* 	
If you see this text in your browser, PHP is not configured correctly on this hosting provider. 
Contact your hosting provider regarding PHP configuration for your site.

PHP file generated by Adobe Muse CC 2017.0.0.363
*/

function formthrottle_check()
{
	if (!is_writable('.'))
	{
		return '8';
	}

	try
	{
	    if (in_array("sqlite",PDO::getAvailableDrivers(),TRUE))
		{
			$db = new PDO('sqlite:muse-throttle-db.sqlite3');
			if ( file_exists('muse-throttle-db') )
			{
				unlink('muse-throttle-db');
			}
		}
		else if (function_exists("sqlite_open")) 
		{
			$db = new PDO('sqlite2:muse-throttle-db');
			if ( file_exists('muse-throttle-db.sqlite3') )
			{
				unlink('muse-throttle-db.sqlite3');
			}
		} else {
			return '4';
		}
	}
	catch( PDOException $Exception ) {
		return '9';
	}

	$retCode ='5';
	if ($db) 
	{
		$res = $db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='Submission_History';");
		if (!$res or $res->fetchColumn() == 0)
	    {
			$created = $db->exec("CREATE TABLE Submission_History (IP VARCHAR(39), Submission_Date TIMESTAMP)");

			if($created == 0)
			{
				$created = $db->exec("INSERT INTO Submission_History (IP,Submission_Date) VALUES ('256.256.256.256', DATETIME('now'))");
			}
			
			if ($created != 1)
			{
				$retCode = '2';
			}
		}
		if($retCode == '5')
		{
			$res = $db->query("SELECT COUNT(1) FROM Submission_History;");
			if ($res && $res->fetchColumn() > 0)
			{
				$retCode = '0';
			}
			else
				$retCode = '3';
		}

		// Close file db connection
 		$db = null;
	} 
	else
		$retCode = '4';
		
	return $retCode;
}	

function formthrottle_too_many_submissions($ip)
{
	$tooManySubmissions = false;

	try
	{
		if (in_array("sqlite",PDO::getAvailableDrivers(),TRUE))
		{
			$db = new PDO('sqlite:muse-throttle-db.sqlite3');
		}
		else if (function_exists("sqlite_open")) 
		{
			$db = new PDO('sqlite2:muse-throttle-db');
		} else {
			return false;
		}
	}
	catch( PDOException $Exception ) {
		return $tooManySubmissions;
	}

	if ($db) 
	{
		$res = $db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='Submission_History';");
		if (!$res or $res->fetchColumn() == 0)
	    {
			$db->exec("CREATE TABLE Submission_History (IP VARCHAR(39), Submission_Date TIMESTAMP)");
		}
		$db->exec("DELETE FROM Submission_History WHERE Submission_Date < DATETIME('now','-2 hours')");

		$stmt = $db->prepare("INSERT INTO Submission_History (IP,Submission_Date) VALUES (:ip, DATETIME('now'))");
		$stmt->bindParam(':ip', $ip);
		$stmt->execute();
		$stmt->closeCursor();

		$stmt = $db->prepare("SELECT COUNT(1) FROM Submission_History WHERE IP = :ip;");
		$stmt->bindParam(':ip', $ip);
		$stmt->execute();
		if ($stmt->fetchColumn() > 25) 
			$tooManySubmissions = true;
		// Close file db connection
 		$db = null;
	}
	return $tooManySubmissions;
}
?>
