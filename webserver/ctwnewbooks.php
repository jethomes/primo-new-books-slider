<?php

/*

To customize this code for your institution with minimal editing, look for comments with *** at the start of them. Of course, feel free to edit as much as you like!

If you see bugs or a better way to code any of this, your feedback would be extremely welcome. I will eventually set up a GitHub for this.

*/

//***Make sure you've set up PhpSpreadsheet!

require './vendor/autoload.php'; //use PhpOffice\PhpSpreadsheet\IOFactory;

//***Change $file and $pfile to your output file names. For a basic implementation, all you need is one file, so you can delete $pfile.

$file = 'newbooks.html'; //file for standalone
$pfile = 'pnewbooks.html'; //file for primo embed

//***Replace the relative link to "bookstyle.css" if you named your CSS file something different.

$contents = '<!doctype html><html lang="en" /><head><title>New Books for CTW</title><meta charset="UTF-8"><link rel="stylesheet" type="text/css" href="bookstyle.css"><link rel="stylesheet" type ="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.1/tiny-slider.css"></head><body><ul class="booklist">'; //begin html file with metadata and stylesheet links

//***Change the spreadsheet links below to your own FTP'ed spreadsheets. Add or delete them as needed. For a basic setup, you'll need only spreadsheet1. If your links cause issues, make sure they're on the same server as this file.

$spreadsheet1 = "joelle.domains.trincoll.edu/wesana/New Books for Trinitys feed.xls"; 
$spreadsheet2 = "joelle.domains.trincoll.edu/trinana/New Books.xls";
$spreadsheet3 = "joelle.domains.trincoll.edu/connana/CC New Books for Trinity.xls";

function sheetGet($spreadsheet) { //process xls file content
	
	//I don't understand all of how PhpSpreadsheet works but this does what I need it to
	$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($spreadsheet); //  Identify the type
	$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType); //  Create a new Reader of the type that has been identified  
	$spreadsheet = $reader->load($spreadsheet); //  Load to a Spreadsheet Object  
	$books = $spreadsheet->getActiveSheet()->toArray(); //  Convert Spreadsheet Object to an Array for ease of use 
	
	array_unique($books); //no dupes
	shuffle($books); //keep it interesting, especially if you intend to limit the number of processed results below

    //*** To change the number of results returned from each spreadsheet, adjust the final parameter in array_slice to another number. By default it takes 51 results. If you want to keep all results, Delete line 43 and change 44 to return($books);

	$bookSlice = array_slice($books, 0, 50); //limit to 51 entries per sheet or else this is enormous
	return($bookSlice);
}


function makeDisplay($books) { //generate title and cover art with link for each book that has cover art available

	foreach ($books as $result) {

		global $contents;
		
		$mms = $result[1]; //get the mmsid for use below
		$title = (string) rtrim($result[2], " /"); //take the title, ditch anything after a space followed by a slash
		$isbns = explode(';', $result[7]); //so we can loop through all the isbns
		
		if (is_numeric($mms)) { //skip the blanks and header row and errors { //skip the blanks and header row
		
			echo $title . ": "; //if you run this file from your browser, this will print out titles as they're processed
			
			//***Change the apikey parameter placeholder below to your own Primo API key	
			$linkson = file_get_contents('https://api-na.hosted.exlibrisgroup.com/primo/v1/search?vid=CTWTC&tab=default_tab&scope=default_scope&q=any%2Cexact%2C' . $mms . '&lang=eng&offset=0&limit=10&sort=rank&pcAvailability=true&getMore=0&conVoc=true&inst=CTWTC&apikey=YOURAPIKEY');
			$cleanson = json_decode($linkson, false);
			$reallink = (string) $cleanson->docs[0]->pnx->control->recordid[0];
			
			foreach ($isbns as $value) { 
				$isbn = trim($value); //clean it up
				echo $isbn . ": ";
				
				$image = 'https://proxy-na.hosted.exlibrisgroup.com/exl_rewrite/syndetics.com/index.aspx?isbn=' . $isbn . '/MC.JPG'; //medium size thumbnail plz
				$fileSize = strlen(file_get_contents($image)); //so far this is somehow the least stupid way to weed out 1x1 nonsense covers
				
				if ($fileSize > 200) { //no scrubs
				//***Replace the Primo URL and view id below with yours
					$contents .= '<li class="newbooks"> <a target="_blank" rel="noopener noreferrer" href="https://trincoll-primo.hosted.exlibrisgroup.com/primo-explore/fulldisplay?docid=' . $reallink . '&context=L&vid=CTWTC">';
					$contents .= '<img class ="centered" src="' . 'https://proxy-na.hosted.exlibrisgroup.com/exl_rewrite/syndetics.com/index.aspx?isbn=' . $isbn . '/MC.JPG&client=primo' . '"  alt="' . $title . '"></a></li>';
					echo $image . "<br />";
					continue 2;
				}
				//maybe come back and put a check in here to loop through every Syndetics option first?	nah that's work brah
				
				else {
				    //***Replace the Google Books API placeholder with your own
					$googson = file_get_contents('https://www.googleapis.com/books/v1/volumes?q=' . $isbn . '&fields=items(volumeInfo(imageLinks%2Ctitle))&key=YOURAPIKEY'); //ughhh, google books has some issues, primarily that it might find a book by isbn and then not have a cover for it, so it goes somewhere weird. Also some bad isbn metadata in there I think. Need to go back to Syndetics with GB as a fallback I think
					$googparse = json_decode($googson, false);
					$image = (string)$googparse->items[0]->volumeInfo->imageLinks->thumbnail;
						$googtitle = (string)$googparse->items[0]->volumeInfo->title; //trying to compare the beginning of the titles to avoid chaos
						$stitle = substr($title,0,5);
						$sgtitle = substr($googtitle,0,5);
				
					if ($image != "" && $stitle == $sgtitle) { //no blanks, no nonsense
					    //***Replace the Primo URL and view id below with yours
						$contents .= '<li class="newbooks"> <a target="_blank" rel="noopener noreferrer" href="https://trincoll-primo.hosted.exlibrisgroup.com/primo-explore/fulldisplay?docid=' . $reallink . '&context=L&vid=CTWTC">';
						$contents .= '<img class ="centered" src="' . $image . '"  alt="' . $title . '"></a></li>';
						echo $image . "<br />";
						continue 2;
					}
				}
			}

		}

	}

}

//***If you changed the number of spreadsheets above, delete or add them below. For a basic implementation, you can delete lines 113 and 114.

makeDisplay(sheetget($spreadsheet1));
makeDisplay(sheetget($spreadsheet2));
makeDisplay(sheetget($spreadsheet3));


//***You can delete line 118 if you're only aiming to produce one file. Change primostyle.css if you have a second css file intended for Primo display and changed the name of it.
$primoContents = str_replace("bookstyle.css", "primostyle.css", $contents); //replace css for primo


//***Make sure shuffle.js is in the same folder as this file
$contents.= '</ul><script src="shuffle.js"></script></body></html>'; //slap the shuffle on

//***Make sure tinyslide.js is in the same folder as this file.
$primoContents = str_replace('</body></html>', '<script src="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.1/min/tiny-slider.js"></script><script src="tinyslide.js"></script></body></html>', $contents); //slap the slider on primo

//***If you only need to produce one file, delete line 125 and change line 122 to $contents.= '</ul><script src="shuffle.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.1/min/tiny-slider.js"></script><script src="tinyslide.js"></script></body></html>';

//***Again, if you need only one file, delete line 131
file_put_contents ($file, $contents);
file_put_contents ($pfile, $primoContents);

?>
