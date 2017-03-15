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
			ini_set('display_errors', 1);
			$conn_id = mysqli_connect ("DATABASE URL:PORT NO", "USERNAME", "PASSWORD", "DATABASE NAME")
				or exit ();
			$query = $_GET['query'];
			$query = stripslashes($query);

			// $keywordarray = array('a','about','an','are','as','at','be', 'by','com','for','from','how','i','in','is','it','la','of','on','or', 'that','the','this','to','was','what','when','where', 'who','will','with');

			// $query = preg_replace('/\b('. implode('|',$keywordarray) .')\b/i', '', $query);

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

			$query = mysqli_real_escape_string($conn_id, $query); // safety to protect database

			if (strpos($query, 'OR') == false) {
				$symbol = '+';
				$query = $symbol . str_replace(' ', " $symbol", $query);
			}
			else {
				$query1half = strtok($query, ' OR ');
				$query2half = substr($query, strpos($query, " OR ") + 1);
				$query = $query1half . ' ' . $query2half;

				}

			// $query = preg_replace("/s\b/", "*", $query); //removes 's' from as last letter of word and replaces with '*'

			$sqlcount =  "SELECT COUNT(*) ".
			"FROM chapters ".
			"WHERE MATCH(text) AGAINST('$query' IN BOOLEAN MODE) ".
			"$bookquery ";
			$resultcount = mysqli_query( $conn_id, $sqlcount);

			if ($row = mysqli_fetch_row ($resultcount))
				print ("<section class='card resultsfound'> " .htmlspecialchars($row[0]). " results found.");
			mysqli_free_result ($resultcount);

			$bookcountsql =  "SELECT COUNT(paranum) as paracount, bookname ".
			"FROM chapters ".
			"WHERE MATCH(text) AGAINST('$query' IN BOOLEAN MODE) ".
			"$bookquery ".
			"GROUP BY booknumber ";
			$resultbookcountsql = mysqli_query( $conn_id, $bookcountsql);

			printf ("<p><table id='countbook'><tr>");
			while ($row = mysqli_fetch_assoc ($resultbookcountsql))
			{
				printf ("<td>%s - %s</td>", htmlspecialchars ($row['paracount']), htmlspecialchars ($row['bookname']));
			}
			printf ("</tr></table></p></section>");

			$sql =  "SELECT bookname, chaptername, paranum, chapternum ".
			"FROM chapters ".
			"WHERE MATCH(text) AGAINST('$query' IN BOOLEAN MODE) ".
			"$bookquery ".
			"ORDER BY booknumber ";
			$result = mysqli_query( $conn_id, $sql);

			// echo $sql;

			while ($row = mysqli_fetch_assoc ($result))
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

				$otherresult = mysqli_query( $conn_id, $othersql);

				while ($row = mysqli_fetch_assoc ($otherresult))
				{
					for ($i = 0; $i < mysqli_num_fields ($otherresult); $i++)
					{

						$finaltext = boldText($row['text'], $queryarray);

						// printf ("<div class='middle'>$finaltext</div><br>");
						printf ("<div class='result'>$finaltext</div><br>");

					}
				}
				printf ("</p></section>");
			}

			mysqli_close($conn_id);
		?>

	</body>
</html>
