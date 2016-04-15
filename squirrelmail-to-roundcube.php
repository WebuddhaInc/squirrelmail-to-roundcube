<?php

/**
 * This script will import SquirrelMail .abook files into Roundcube contact db
 */

/**
 * Configuration
 */

	$folder 	= 'abooks'; // place .abook files in here
	$db_host  = 'localhost';
	$db_name 	= 'roundcube';
	$db_user  = 'roundcube';
	$db_pass  = 'xxxxxxxxx'; // find this in your roundcube config file

/**
 * Connect to DB
 */

	$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
	if ($mysqli->connect_error) {
		die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
	}
	echo 'Success... ' . $mysqli->host_info . "\n";

/**
 * Scan Folder, Look in Roundcube, Insert those not present
 */

	if ($handle = opendir($folder))
	{

		while (false !== ($entry = readdir($handle)))
		{
				if (is_file($folder.'/'.$entry) && strpos($entry, '.abook') !== false)
				{

	      	$user = preg_replace('/.abook$/', '', $entry);
		      echo "Looking for $user \n";

		      if ($result = $mysqli->query("
		      	SELECT user_id
		      	FROM users
		      	WHERE username='". $mysqli->real_escape_string($user) ."'
		      	", MYSQLI_USE_RESULT)) {

							if ($obj = $result->fetch_object())
							{

								echo "Processing $user \n";
								$user_id = $obj->user_id;
						    $result->close();

								if($fh = fopen( $folder.'/'.$entry, 'r' )){

									while ($line = fgets( $fh ))
									{

										$line = (object)array_combine(
											array(
												'nickname',
												'firstname',
												'lastname',
												'email',
												'info'
												),
											explode('|', $line)
											);

										if ($line->email)
										{

											$result = $mysqli->query("
								      	SELECT *
								      	FROM contacts
								      	WHERE email='". $mysqli->real_escape_string($line->email) ."'
								      		AND user_id='". (int)$user_id ."'
								      	");

								      if (!$result->num_rows)
								      {

												echo "Inserting $line->email for $user \n";
												$result = $mysqli->query("
									      	INSERT INTO contacts (
										      	`contact_id`,
										      	`changed`,
										      	`del`,
										      	`name`,
										      	`email`,
										      	`firstname`,
										      	`surname`,
										      	`vcard`,
										      	`user_id`
									      	) VALUES (
									      	NULL,
										      	'". date('Y-m-d H:i:s') ."',
										      	0,
										      	'". $mysqli->real_escape_string($line->nickname) ."',
										      	'". $mysqli->real_escape_string($line->email) ."',
										      	'". $mysqli->real_escape_string($line->firstname) ."',
										      	'". $mysqli->real_escape_string($line->lastname) ."',
										      	NULL,
										      	'". (int)$user_id ."'
									      	)
									      	");
												if (!$result) {
												    echo "Error: " . $mysqli->error;
												}

											}
											else
											{

												echo "Skipping $line->email for $user_id \n";

											}

										}

									}

								}

							}

					}

				}
	  }

	}

	$mysqli->close();

