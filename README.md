# OpenDominion Resources

This branch serves to hold resources to help develop OpenDominion.

## Dominion

A mirror of the original Dominion website I scaped off the web archive.

## Blackreign's OOP simulator

This spreadsheet contains a load of macros and won't properly work on LibreOffice and possibly versions of Excel newer than 2003.

I recommend opening this on Excel 2003 on a Windows XP virtual machine.

## Wiki

A mirror of the unofficial Dominion wiki I scraped off the web archive.

Steps to reproduce (roughly):

Make sure you have the [Wayback Machine Downloader](https://github.com/hartator/wayback-machine-downloader) gem installed.

```bash
$ wayback_machine_downloader http://dominion.lykanthropos.com/wiki/index.php/ --from 20110508222237 --to 20141017235815
$ ./fix-site.php
```

fix-site.php:
```php
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
```

On Windows I had to edit the Wayback Machine Downloader source code to change ':' to 'x3a' so URLs with a ':' in the
filename could be saved on a Windows filesystem at `gems\2.3.0\gems\wayback_machine_downloader-1.1.4\lib`.

Sadly, I lost these changes and specific implementation.
