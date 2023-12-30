#!/usr/bin/php
<?php

// this script turns quotes from books into images for use in a Kindle clock.
// Jaap Meijers, 2018

# originally written by Jaap Meijers and published at 
# https://www.instructables.com/Literary-Clock-Made-From-E-reader/
# licensed under CC-BY-NC-SA https://creativecommons.org/licenses/by-nc-sa/4.0/
# (see licenses folder)

error_reporting(E_ALL);
ini_set("display_errors", 1);

//image dimensions
$width = 600;
$height = 800;
//text margin
$margin = 26;

$imagenumber = 0;
$previoustime = 0;

// pad naar font file
putenv('GDFONTPATH=' . realpath('../fonts/LinLibertine'));
$font_path = "LinLibertine_RZah.ttf";
$font_path_bold = "LinLibertine_RBah.ttf";
$creditFont = "LinLibertine_RZIah.ttf";


// get the quotes (including title and author) from a CSV file, 
// and create unique images for them, one without and one with title and author
$row = 0;
$filename = "litclock_annotated.csv";
$count = 0;
$gaps = 0;

if (($handle = fopen($filename, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, "|")) !== FALSE) {
        $row++;
        
        if (substr($data[0], 0, 1) == '#') {
            # ignore comments
            continue;
        }
        
        $cols = count($data);
        if ($cols < 5) {
            echo "Warning: invalid data in $filename, line $row (expected at least 5 columns, found $cols)\n";
            continue;
        }
        
        $time = $data[0];
        $timestring = trim($data[1]);
        $quote = $data[2];
        $quote = trim(preg_replace('/\s+/', ' ', $quote));
        $title = trim($data[3]);
        $author = trim($data[4]);
        
        if (isset($last_time) && $last_time == $time) {
            $count++;
            echo ".";
        } else {
            echo "\n";
            
            # check for gaps
            if (isset($last_time)) {
                $new_gaps = time_to_minute_of_day($time) - time_to_minute_of_day($last_time) - 1;
                if ($new_gaps > 0) {
                    fwrite(STDERR, "WARNING: $new_gaps gap(s) detected between $last_time and $time!\n");
                    $gaps += $new_gaps;
                }
            }

            # new time
            echo "$time: .";
            $last_time = $time;
            $count = 1;
        }

        $png_image = TurnQuoteIntoImage($quote, $timestring);
        
        // serial number for when there is more than one quote for a certain minute 
        $time = substr($time, 0, 2).substr($time, 3, 2);
        if ($time == $previoustime) {
            $imagenumber++;
        } else {
            $imagenumber = 0;
        }
        $previoustime = $time;

        // Save the image
        imagepng($png_image, 'images/quote_'.$time.'_'.$imagenumber.'.png');
        
        add_metadata($png_image, $title, $author);

       // Save the image with metadata
        imagepng($png_image, 'images/metadata/quote_'.$time.'_'.$imagenumber.'_credits.png');

        // Free up memory
        imagedestroy($png_image);

        // convert the image we made to greyscale
        $im = new Imagick();
        $im->readImage('images/quote_'.$time.'_'.$imagenumber.'.png');
        $im->setImageType(Imagick::IMGTYPE_GRAYSCALE);
        unlink('images/quote_'.$time.'_'.$imagenumber.'.png');
        $im->writeImage('images/quote_'.$time.'_'.$imagenumber.'.png');

        // convert the image we made to greyscale 
        $im = new Imagick();
        $im->readImage('images/metadata/quote_'.$time.'_'.$imagenumber.'_credits.png');
        $im->setImageType(Imagick::IMGTYPE_GRAYSCALE);
        unlink('images/metadata/quote_'.$time.'_'.$imagenumber.'_credits.png');
        $im->writeImage('images/metadata/quote_'.$time.'_'.$imagenumber.'_credits.png');


    }
    echo "\n";
    fclose($handle);
    
    if ($gaps > 0) {
        fwrite(STDERR, "WARNING: $gaps gap(s) in total!\n");
    }
}

function time_to_minute_of_day($time) {
    [$hour, $minute] = explode(':', $time);
    return $hour * 60 + $minute;
}

function TurnQuoteIntoImage($quote, $timestring) {

    global $font_path, $font_path_bold, $width, $height, $margin;
    
    // preprocess line breaks by adding newline to the following word
    foreach (['<br>', '<br/>', '<br />'] as $break) {
        $quote = str_replace($break, " \n", $quote);
    }

    // first, find the timestring to be highlighted in the quote
    // determine the position of the timestring in the quote (so after how many words it appears)
    $timestringStarts = count(explode(' ', stristr($quote, $timestring, true)))-1;
    // how many words long the timestring is
    $timestring_wordcount = count(explode(' ', $timestring))-1;

    // divide text in an array of words, based on spaces
    $quote_array = explode(' ', $quote);

    // font size to start with looking for a fit. a long quote of 125 words or 700 characters gives us a font size of 23, so 18 is a safe start.
    $font_size = 18;

    ///// QUOTE /////
    // find the font size (recursively) for an optimal fit of the text in the bounding box
    // and create the image.
    list($png_image) = fitText($quote_array, $width, $height, $font_size, $timestringStarts, $timestring_wordcount, $margin);
    
    return $png_image;
}


// create another version, with title and author in the image
function add_metadata($png_image, $title, $author) {
    global $creditFont, $width, $height, $margin;

    // define text color
    $grey = imagecolorallocate($png_image, 125, 125, 125);
    $black = imagecolorallocate($png_image, 0, 0, 0);

    $dash = "—";

    $credits = $title . ", " . $author;
    $creditFont_size = 18;

    // if the metadata are longer than 45 characters, replace a space by a newline from the end,
    // just as long the paragraph is getting smaller. stop when the box gets wider again.
    list($metawidth, $metaheight, $metaleft, $metatop) = measureSizeOfTextbox($creditFont_size, $creditFont, $dash . $credits);
    
    if ( $metawidth > 500 ) {

        $newCredits = array();

        $creditsArray = explode(" ", $credits);
        
        $i = 1;

        while ( True ) {

            // cut the metadata in two lines
            $tmp0 = implode(" ", array_slice($creditsArray, 0, count($creditsArray)-$i));
            $tmp1 = implode(" ", array_slice($creditsArray, 0-$i));

            // once the second line is (almost) longer than the first line, stop
            if ( strlen($tmp1)+5 > strlen($tmp0) ) {
                break;
            } else { 
                // if the second line is still shorter than the first, save it to a new string, but continue to look at a new fit.
                $newCredits[0] = $tmp0;
                $newCredits[1] = $tmp1;
            }

            $i++;

        }

        list($textWidth1, $textheight1) = measureSizeOfTextbox($creditFont_size, $creditFont, $dash . $newCredits[0]);
        list($textWidth2, $textheight2) = measureSizeOfTextbox($creditFont_size, $creditFont, $newCredits[1]);

        $metadataX1 = $width-($textWidth1+$margin);
        $metadataX2 = $width-($textWidth2+$margin);
        $metadataY = $height-$margin;

        imagettftext($png_image, $creditFont_size, 0, $metadataX1, floor($metadataY-($textheight1*1.1)), $black, $creditFont, $dash . $newCredits[0]);
        imagettftext($png_image, $creditFont_size, 0, $metadataX2, $metadataY, $black, $creditFont, $newCredits[1]);
        
    } else {

        // position of single line metadata
        $metadataX = ($width-$metaleft)-$margin;
        $metadataY = $height-$margin;

        imagettftext($png_image, $creditFont_size, 0, $metadataX, $metadataY, $black, $creditFont, $dash . $credits);

    }
}


function fitText($quote_array, $width, $height, $font_size, $timestringStarts, $timestring_wordcount, $margin) {

    global $font_path_bold;
    global $font_path;

    // create image
    $png_image = imagecreate($width, $height)
        or die("Cannot Initialize new GD image stream");
    $background_color = imagecolorallocate($png_image, 255, 255, 255);

    // define text color
    $grey = imagecolorallocate($png_image, 125, 125, 125);
    $black = imagecolorallocate($png_image, 0, 0, 0);

    $timeLocation = 0;
    $lineWidth = 0;

    // variable to hold the x and y position of words
    $position = array($margin,$margin+$font_size);

    // echo "try " . $font_size . ", ";

    foreach($quote_array as $key => $word) {
        $force_newline = false;
        if (substr($word, 0, 1) == "\n") {
            $force_newline = true;
            $word = substr($word, 1);
        }
        
        # change the look of the text if it is part of the time string
        if ( in_array($key, range($timestringStarts, $timestringStarts+$timestring_wordcount)) ) {
            $font = $font_path_bold;
            $textcolor = $black;
        } else {
            $font = $font_path;
            $textcolor = $grey;
        }

        // measure the word's width
        list($textwidth, $textheight) = measureSizeOfTextbox($font_size, $font, $word . " ");

        //// write every word to image, and record its position for the next word ////

        // if one word exceeds the width of the image (this sometimes happens when the quote is very short),
        // then stop trying to make the font size even bigger.
        if ( $textwidth > ($width - $margin) ) {
            return False;
        }

        // if the line plus the extra word is too wide for the specified width, then write the word one the next line. 
        if ( $force_newline || ($position[0] + $textwidth) >= ($width - $margin)) {
            
            # 'carriage return':
            # reset x to the beginning of the line and push y down a line 
            $position[0] = $margin;
            $position[1] = $position[1] + round($font_size*1.618); // 'golden ratio' line height

            # write the word to the image
            imagettftext($png_image, $font_size, 0, $position[0], $position[1], $textcolor, $font, $word);
           
        // if the line isn't too long, just add it.
        } else {

            # write the word to the image
            imagettftext($png_image, $font_size, 0, $position[0], $position[1], $textcolor, $font, $word);

        }
        
        # add the word's width
        $position[0] += $textwidth;

    }

    // if the height of the whole text is smaller than the height of the image, then call this same function again
    $paragraphHeight = $position[1];
    if ( $paragraphHeight < $height-100 ) { // leaving room for the credits below
        $result = fitText($quote_array, $width, $height, $font_size+1, $timestringStarts, $timestring_wordcount, $margin);
        if ( $result !== False ) {
            list($png_image, $paragraphHeight, $font_size, $timeLocation) = $result;
        };
    } else {
        // if this call to fitText returned a paragraph that is in fact higher than the height of the image,
        // then return without those values
        return False;
    }

    return array($png_image, $paragraphHeight, $font_size, $timeLocation);

}

function measureSizeOfTextbox($font_size, $font_path, $text) {

    $box = imagettfbbox($font_size, 0, $font_path, $text);

    $min_x = min( array($box[0], $box[2], $box[4], $box[6]) );
    $max_x = max( array($box[0], $box[2], $box[4], $box[6]) );
    $min_y = min( array($box[1], $box[3], $box[5], $box[7]) );
    $max_y = max( array($box[1], $box[3], $box[5], $box[7]) );

    $width  = ( $max_x - $min_x );
    $height = ( $max_y - $min_y );
    $left   = abs( $min_x ) + $width;
    $top    = abs( $min_y ) + $height;

    return array($width, $height, $left, $top);

}


?>
