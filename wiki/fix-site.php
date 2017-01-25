#!/usr/bin/env php
<?php

$srcDir = 'websites/dominion.lykanthropos.com/wiki/index.php';
$dstDir = 'build';

if (!is_dir($dstDir)) {
	mkdir($dstDir);
}

// Copy css
copy('main.css', "{$dstDir}/main.css");

// Process downloaded files
foreach (glob("{$srcDir}/*") as $filename) {
	if (!file_exists("{$filename}/index.html")) {
		continue;
	}

	$html = file_get_contents("{$filename}/index.html");
	
	$dstFilename = str_replace("{$srcDir}/", '', $filename);
	$dstFilename = slugify($dstFilename);
	$dstFilename .= '.html';
	
	// Rewrite valid links
	$html = preg_replace_callback(
		'~href="/wiki/index\.php/([^"\#]+)~',
		function ($matches) {
			$path = slugify($matches[1]);
			return "href=\"{$path}.html";
		},
		$html
	);
	
	// Rewrite invalid links
	$html = preg_replace(
		'~href="/wiki/index\.php\?title=([^"]+)"~', 
		'href="#"',
		$html
	);

	// Rewrite CSS links
	$html = preg_replace(
		'~/wiki/skins//monobook/main\.css(?:\?\d+)?~',
		'main.css',
		$html
	);

	file_put_contents("{$dstDir}/{$dstFilename}", $html);
	
	echo "Done {$dstFilename}\n";
}

function slugify($string) {
	$replace = [
		'/' => '-',
		'_' => '-',
		':' => '-',
		'x3a' => '-',
	];

	$string = strtolower($string);
	$string = str_replace(array_keys($replace), array_values($replace), $string);

	return $string;
}
