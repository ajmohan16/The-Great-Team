<?php
	if($_SERVER["REQUEST_METHOD"] == "POST"){
		$username = $_POST['username'];
		$password = $_POST['password'];

		$host = "172.26.233.84";
		$dbusername = "admin";
		$dbpassword = "Admin123!";
		$dbname = "testdb";

		$conn = new mysqli($host, $dbusername, $dbpassword, $dbname);

		if($conn->connect_error){
			die("Connection failed: ". $conn->connect_error);
		}

		$query = "SELECT * FROM allusers WHERE username='$username' AND password='$password'";
		$result = $conn->query($query);

		if($result->num_rows == 1){
			include("welcome.html");
		}
		else{
			echo("Invalid credentials");
			include("test.html");
		}

		$conn->close();

	}
?>
