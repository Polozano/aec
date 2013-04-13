<?php

include 'common.php';

// And HE told me the count of the git
$count = trim( shell_exec( 'git rev-list HEAD --count' ) );

// And on the thirteenth day of the fourth month of the year two thousand and thirteen,
// The hardware counter stood at six thousand one hundred and two,
// Yet THE COMPUTER spoke of four thousand eight hundred and ninety eight,
// Thus it was deemed that the difference shall be one thousand two hundred and four
// One Thousand two hundred and four shall be the number, no more, no less
// Not one thousand two hundred and three, nor one thousand two hundred and five

$count += 1204;

// And since we knoweth that we shall be incrementing the counter, we shall do so

$count++;

// And changeth be the class file
fsnr( __DIR__.'/../../newsrc/code/components/com_acctexp/acctexp.class.php', "define( '_AEC_REVISION', '", $count );

// And changeth be the xml file
fsnr( __DIR__.'/../build/build.xml', '<property name="git.lastrevision" value="', $count );
