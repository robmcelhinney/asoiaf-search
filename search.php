<html>
	<head>
		<title>Search ASOIAF</title>
		<link rel="stylesheet" type="text/css" href="search.css">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" type="image/png" href="favicon-32x32.png" sizes="32x32" />
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
	</head>
	<body>
		<?php
			function boldText($strSubject, $arrWords)
				{
				    foreach ($arrWords as $s)
				    {
				        $strSubject = preg_replace('/\b' . preg_quote($s, "/") . '\b/i', "<b>\$0</b>", $strSubject);
				    }
				    return $strSubject;
				}

			$conn_id = @mysql_connect ("USERNAME.cbtou5c3lpu1.us-west-2.rds.amazonaws.com:3306", "root", "PASSWORD")
				or exit ();
			mysql_select_db ("databasename", $conn_id);

			$query = $_GET['query'];
			$query = stripslashes($query);

			$query = preg_replace('!\s+!', ' ', $query); // remove multiple empty spaces
			$query = trim($query); // removes first and last whitespace
			$queryarray = explode(" ", str_replace(' OR ', ' ', $query)); // makes words into a keyword array to bold text later.

			if(isset($_GET['book'])) {
				$book = $_GET['book'];
				$bookquery = "AND bookname IN('".implode("','",$book)."') ";
			}
			else{
				$bookquery = '';
			}

			$query = mysql_real_escape_string($query); // safety to protect database

			if (strpos($query, 'OR') == false) {
				$symbol = '+';
				$query = $symbol . str_replace(' ', " $symbol", $query);
			}
			else {
				
				$query1half = strtok($query, ' OR ');
				$query2half = substr($query, strpos($query, " OR ") + 1);
				$query = $query1half . ' ' . $query2half;

				}

			$sqlcount =  "SELECT COUNT(*) ".
			"FROM chapters ".
			"WHERE MATCH(text) AGAINST('$query' IN BOOLEAN MODE) ".
			"$bookquery ";
			$resultcount = mysql_query( $sqlcount, $conn_id );

			if ($row = mysql_fetch_row ($resultcount))
				print ("<section class='card resultsfound'> " .htmlspecialchars($row[0]). " results found.");
			mysql_free_result ($resultcount);

			$bookcountsql =  "SELECT COUNT(paranum) as paracount, bookname ".
			"FROM chapters ".
			"WHERE MATCH(text) AGAINST('$query' IN BOOLEAN MODE) ".
			"$bookquery ".
			"GROUP BY booknumber ";
			$resultbookcountsql = mysql_query( $bookcountsql, $conn_id );

			printf ("<p><table id='countbook'><tr>");
			while ($row = mysql_fetch_assoc ($resultbookcountsql))
			{
				printf ("<td>%s - %s</td>", htmlspecialchars ($row['paracount']), htmlspecialchars ($row['bookname']));
			}
			printf ("</tr></table></p></section>");

			$sql =  "SELECT bookname, chaptername, paranum, chapternum ".
			"FROM chapters ".
			"WHERE MATCH(text) AGAINST('$query' IN BOOLEAN MODE) ".
			"$bookquery ".
			"ORDER BY booknumber ";
			$result = mysql_query( $sql, $conn_id );

			while ($row = mysql_fetch_assoc ($result))
			{	
				$paragraphnum = ($row['paranum']);
				$paragraphnumplus = $paragraphnum + 1;
				$paragraphnumminus = $paragraphnum - 1;
				$bookname = ($row['bookname']);
				$chapternum = ($row['chapternum']);

				printf ("<section class='card'><p><p class='bookchap'>%s - %s</p>", htmlspecialchars ($row['bookname']), htmlspecialchars ($row['chaptername']));

				$othersql =  "SELECT text ".
				"FROM chapters ".
				"WHERE paranum in ('$paragraphnumminus', '$paragraphnum', '$paragraphnumplus') ".
				"AND chapternum = '$chapternum' ".
				"AND bookname = '$bookname' ";

				$otherresult = mysql_query( $othersql, $conn_id );

				while ($row = mysql_fetch_assoc ($otherresult))
				{
					for ($i = 0; $i < mysql_num_fields ($otherresult); $i++)
					{

						$finaltext = boldText($row['text'], $queryarray);
						
						printf ("<div class='result'>$finaltext</div><br>");
						
					}
				}
				printf ("</p></section>");
			}

			mysql_close($conn_id);
		?>

	</body>
</html>